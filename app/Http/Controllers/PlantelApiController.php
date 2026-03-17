<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlantelApiController extends Controller
{
    private function metaInt(string $key, int $default): int
    {
        if (! Schema::hasTable('meta')) {
            return $default;
        }

        $raw = DB::table('meta')->where('chave', $key)->value('valor');
        if ($raw === null || trim((string) $raw) === '') {
            return $default;
        }

        $n = (int) $raw;

        return $n < 0 ? $default : $n;
    }

    public function femeas()
    {
        if (! Schema::hasTable('femea')) {
            return response()->json([]);
        }

        $includeTodas = request()->boolean('all');
        $includePrevisao = request()->boolean('previsao_cio');

        $select = [
            'id',
            'id_primaria',
            'id_secundaria',
            'localizacao',
            'baia',
            'tipo_compra as tipo',
        ];

        if (Schema::hasColumn('femea', 'data_nascimento')) {
            $select[] = 'data_nascimento';
        } else {
            $select[] = DB::raw('NULL as data_nascimento');
        }

        $query = DB::table('femea')->orderBy('id_primaria')->select($select);

        if (! $includeTodas && Schema::hasTable('femea_movimento')) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('femea_movimento as fm')
                    ->whereColumn('fm.femea_id', 'femea.id')
                    ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
            });
        }

        $rows = $query->limit(5000)->get();

        if ($includePrevisao) {
            $cfg = [
                'dias_ate_cio' => $this->metaInt('criterio_dias_ate_cio', 21),
                'cio_dias' => $this->metaInt('criterio_dias_cio', 3),
                'gestacao_dias' => $this->metaInt('criterio_dias_gestacao', 114),
                'lactacao_min_dias' => $this->metaInt('criterio_dias_lactacao_min', 21),
                'intervalo_desmame_cio_dias' => $this->metaInt('criterio_dias_intervalo_desmame_cio', 5),
                'maturidade_min_dias' => $this->metaInt('criterio_maturidade_idade_min_dias', 151),
            ];

            $ids = $rows->pluck('id')->toArray();

            $lastCoberturas = Schema::hasTable('gestacao_cobertura')
                ? DB::table('gestacao_cobertura')->whereIn('femea_id', $ids)->selectRaw('femea_id, MAX(data) as last_data')->groupBy('femea_id')->pluck('last_data', 'femea_id')->toArray()
                : [];

            $lastCios = Schema::hasTable('gestacao_cio')
                ? DB::table('gestacao_cio')->whereIn('femea_id', $ids)->selectRaw('femea_id, MAX(data) as last_data')->groupBy('femea_id')->pluck('last_data', 'femea_id')->toArray()
                : [];

            $lastSaltas = Schema::hasTable('gestacao_salta_cio')
                ? DB::table('gestacao_salta_cio')->whereIn('femea_id', $ids)->selectRaw('femea_id, MAX(data) as last_data')->groupBy('femea_id')->pluck('last_data', 'femea_id')->toArray()
                : [];

            foreach ($rows as $row) {
                $fId = $row->id;
                $lastCob = isset($lastCoberturas[$fId]) ? Carbon::parse($lastCoberturas[$fId]) : null;
                $lastC = isset($lastCios[$fId]) ? Carbon::parse($lastCios[$fId]) : null;
                $lastS = isset($lastSaltas[$fId]) ? Carbon::parse($lastSaltas[$fId]) : null;

                $prevCio = null;
                if ($lastCob) {
                    $prevCio = (clone $lastCob)->addDays($cfg['gestacao_dias'] + $cfg['lactacao_min_dias'] + $cfg['intervalo_desmame_cio_dias']);
                } else {
                    $lastEvento = $lastC;
                    if ($lastEvento === null || ($lastS !== null && $lastS->gt($lastEvento))) {
                        $lastEvento = $lastS;
                    }
                    if ($lastEvento) {
                        $prevCio = (clone $lastEvento)->addDays(max(1, $cfg['dias_ate_cio']));
                    }
                }

                if (! $prevCio && $row->tipo === 'leitoa' && ! empty($row->data_nascimento)) {
                    $nasc = Carbon::parse($row->data_nascimento);
                    $maturityStart = (clone $nasc)->addDays(max(0, $cfg['maturidade_min_dias']));
                    $prevCio = (clone $maturityStart)->addDays(max(1, $cfg['dias_ate_cio']));
                }

                if ($prevCio) {
                    $row->previsao_cio_inicio = $prevCio->toDateString();
                    $row->previsao_cio_fim = (clone $prevCio)->addDays($cfg['cio_dias'])->toDateString();
                } else {
                    $row->previsao_cio_inicio = null;
                    $row->previsao_cio_fim = null;
                }
            }
        }

        return response()->json($rows);
    }

    public function machos()
    {
        if (! Schema::hasTable('macho')) {
            return response()->json([]);
        }

        $includeTodos = request()->boolean('all');

        $query = DB::table('macho')->orderBy('id_primaria')->select([
            'id',
            'id_primaria',
            'id_secundaria',
            'localizacao',
            'baia',
        ]);

        if (! $includeTodos && Schema::hasTable('macho_movimento')) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('macho_movimento as mm')
                    ->whereColumn('mm.macho_id', 'macho.id')
                    ->whereIn('mm.acao', ['morte', 'descarte', 'venda']);
            });
        }

        return response()->json($query->limit(5000)->get());
    }

    public function causasMorte()
    {
        return $this->causasPorTipo('morte');
    }

    public function causasVenda()
    {
        return $this->causasPorTipo('venda');
    }

    public function causasDescarte()
    {
        return $this->causasPorTipo('descarte');
    }

    private function causasPorTipo(string $tipo)
    {
        if (! Schema::hasTable('causa') || ! Schema::hasTable('grupo_causa')) {
            return response()->json([]);
        }

        $tipo = mb_strtolower($tipo);

        $items = Causa::query()
            ->with('grupoCausa')
            ->where('situacao', true)
            ->whereHas('grupoCausa', function ($q) use ($tipo) {
                $q->whereRaw('LOWER(nome) LIKE ?', ['%'.$tipo.'%']);
            })
            ->orderBy('nome')
            ->get()
            ->map(function (Causa $c) {
                return [
                    'id' => $c->id,
                    'codigo' => $c->codigo,
                    'nome' => $c->nome,
                ];
            })->values();

        return response()->json($items);
    }
}
