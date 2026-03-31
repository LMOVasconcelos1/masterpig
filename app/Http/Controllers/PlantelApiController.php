<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Services\PigCycleService;
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
        try {
            if (! Schema::hasTable('femea')) {
                return response()->json(['items' => [], 'total' => 0]);
            }

            $includeTodas = request()->boolean('all');
            $includePrevisao = request()->boolean('previsao_cio');
            $limit = request()->integer('limit', 50);
            $page = request()->integer('page', 1);
            $search = request()->input('search');
            $racaId = request()->input('raca_id');
            $fornecedorId = request()->input('fornecedor_id');
            $localizacao = request()->input('localizacao');
            $baia = request()->input('baia');
            $dataInicInput = request()->input('data_inicial');
            $dataFimInput = request()->input('data_final');

            $select = [
                'femea.id',
                'femea.id_primaria',
                'femea.id_secundaria',
                'femea.localizacao',
                'femea.baia',
                'femea.raca_id',
                'femea.tipo_compra as tipo',
                'femea.data_nascimento',
                'femea.fornecedor_id',
                'femea.peso_compra as peso',
            ];

            if (Schema::hasTable('raca')) {
                $query = DB::table('femea')->orderBy('femea.id_primaria')->select($select);
                $query->leftJoin('raca', 'raca.id', '=', 'femea.raca_id')->addSelect('raca.nome as raca_nome');
            } else {
                $query = DB::table('femea')->orderBy('femea.id_primaria')->select($select)->addSelect(DB::raw('NULL as raca_nome'));
            }

            if (Schema::hasTable('fornecedor')) {
                $query->leftJoin('fornecedor', 'fornecedor.id', '=', 'femea.fornecedor_id')->addSelect('fornecedor.nome as fornecedor_nome');
            } else {
                $query->addSelect(DB::raw('NULL as fornecedor_nome'));
            }

            if (Schema::hasTable('femea_movimento')) {
                $last = DB::table('femea_movimento')
                    ->selectRaw('MAX(id) as last_id, femea_id')
                    ->groupBy('femea_id');

                $query->leftJoinSub($last, 'lm', function ($join) {
                    $join->on('lm.femea_id', '=', 'femea.id');
                });

                $query->leftJoin('femea_movimento as fm', 'fm.id', '=', 'lm.last_id')
                    ->addSelect([
                        'fm.acao as ultima_acao',
                        'fm.data as ultima_data',
                    ]);
            }

            // Buscar o último peso registrado nos cios
            if (Schema::hasTable('gestacao_cio')) {
                $lastPeso = DB::table('gestacao_cio')
                    ->selectRaw('MAX(id) as last_id, femea_id')
                    ->whereNotNull('peso')
                    ->groupBy('femea_id');

                $query->leftJoinSub($lastPeso, 'lp', function ($join) {
                    $join->on('lp.femea_id', '=', 'femea.id');
                });

                $query->leftJoin('gestacao_cio as gc_peso', 'gc_peso.id', '=', 'lp.last_id')
                    ->addSelect('gc_peso.peso as peso_atual');
            }

            if (! $includeTodas && Schema::hasTable('femea_movimento')) {
                $query->where(function ($q) {
                    $q->whereNull('fm.acao')
                      ->orWhereNotIn('fm.acao', ['morte', 'descarte', 'venda']);
                });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('femea.id_primaria', 'like', "%{$search}%")
                        ->orWhere('femea.id_secundaria', 'like', "%{$search}%");
                });
            }

            if (!empty($racaId)) {
                $query->where('femea.raca_id', $racaId);
            }

            if (!empty($fornecedorId)) {
                $query->where('femea.fornecedor_id', $fornecedorId);
            }

            if (!empty($localizacao)) {
                $query->where('femea.localizacao', 'like', "%{$localizacao}%");
            }

            if (!empty($baia)) {
                $query->where('femea.baia', 'like', "%{$baia}%");
            }

            // Filtros de data (baseados no último movimento fm.data)
            $parsedInic = PigCycleService::parseFilterDate($dataInicInput);
            if ($parsedInic && Schema::hasTable('femea_movimento')) {
                $query->where('fm.data', '>=', $parsedInic->toDateString());
            }

            $parsedFim = PigCycleService::parseFilterDate($dataFimInput);
            if ($parsedFim && Schema::hasTable('femea_movimento')) {
                $query->where('fm.data', '<=', $parsedFim->toDateString());
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $rows = $query->offset($offset)->limit($limit)->get()->map(function ($row) {
                $row->ultima_operacao_label = $row->ultima_acao ?? '-';
                $row->ultima_data_formatada = $row->ultima_data ? PigCycleService::formatDisplayDate(Carbon::parse($row->ultima_data)) : '-';
                
                $nasc = $row->data_nascimento ? Carbon::parse($row->data_nascimento) : null;
                $row->idade_dias = $nasc ? (int) $nasc->diffInDays(now()) : null;
                $row->raca = $row->raca_nome ?? '-';
                $row->fornecedor = $row->fornecedor_nome ?? '-';
                $row->peso_formatado = $row->peso ? number_format($row->peso, 2, ',', '.') . ' kg' : '-';
                
                return $row;
            });

            if ($includePrevisao) {
                $durations = PigCycleService::getCycleDurations();
                $cfg = [
                    'dias_ate_cio' => $this->metaInt('criterio_dias_ate_cio', 21),
                    'cio_dias' => $durations['cio'],
                    'gestacao_dias' => $durations['gestacao'],
                    'lactacao_min_dias' => $durations['lactacao'],
                    'intervalo_desmame_cio_dias' => $durations['intervalo'],
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
                        $cycle = PigCycleService::calculateCycle($lastCob);
                        $prevCio = $cycle['nextCioDate'];
                    } else {
                        $lastEvento = $lastC;
                        if ($lastEvento === null || ($lastS !== null && $lastS->gt($lastEvento))) {
                            $lastEvento = $lastS;
                        }
                        if ($lastEvento) {
                            $prevCio = (clone $lastEvento)->addDays($cfg['dias_ate_cio']);
                        }
                    }

                    $row->previsao_cio = $prevCio ? PigCycleService::formatDisplayDate($prevCio) : '-';
                }
            }

            return response()->json([
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($total / $limit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno: ' . $e->getMessage(), 'items' => [], 'total' => 0], 500);
        }
    }

    public function updateFemea(Request $request, int $id)
    {
        if (! Schema::hasTable('femea')) {
            return response()->json(['message' => 'Tabela não encontrada'], 404);
        }

        $validated = $request->validate([
            'id_primaria' => 'required|string|max:255',
            'id_secundaria' => 'nullable|string|max:255',
            'raca_id' => 'nullable|integer',
            'localizacao' => 'nullable|string|max:255',
            'baia' => 'nullable|string|max:255',
        ]);

        DB::table('femea')->where('id', $id)->update([
            'id_primaria' => $validated['id_primaria'],
            'id_secundaria' => $validated['id_secundaria'],
            'raca_id' => $validated['raca_id'],
            'localizacao' => $validated['localizacao'],
            'baia' => $validated['baia'],
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Fêmea atualizada com sucesso']);
    }

    public function deleteFemea(int $id)
    {
        if (! Schema::hasTable('femea')) {
            return response()->json(['message' => 'Tabela não encontrada'], 404);
        }

        // Deletar movimentos e outros dados relacionados primeiro se necessário
        if (Schema::hasTable('femea_movimento')) {
            DB::table('femea_movimento')->where('femea_id', $id)->delete();
        }
        if (Schema::hasTable('gestacao_cio')) {
            DB::table('gestacao_cio')->where('femea_id', $id)->delete();
        }
        if (Schema::hasTable('gestacao_cobertura')) {
            DB::table('gestacao_cobertura')->where('femea_id', $id)->delete();
        }
        if (Schema::hasTable('gestacao_salta_cio')) {
            DB::table('gestacao_salta_cio')->where('femea_id', $id)->delete();
        }
        if (Schema::hasTable('gestacao_perda')) {
            DB::table('gestacao_perda')->where('femea_id', $id)->delete();
        }

        DB::table('femea')->where('id', $id)->delete();

        return response()->json(['message' => 'Fêmea excluída com sucesso']);
    }

    public function machos()
    {
        try {
            if (! Schema::hasTable('macho')) {
                return response()->json(['items' => [], 'total' => 0]);
            }

            $includeTodos = request()->boolean('all');
            $limit = request()->integer('limit', 50);
            $page = request()->integer('page', 1);
            $search = request()->input('search');
            $localizacao = request()->input('localizacao');
            $baia = request()->input('baia');
            $dataInicInput = request()->input('data_inicial');
            $dataFimInput = request()->input('data_final');

            $query = DB::table('macho')->orderBy('macho.id_primaria')->select([
                'macho.id',
                'macho.id_primaria',
                'macho.id_secundaria',
                'macho.localizacao',
                'macho.baia',
            ]);

            if (Schema::hasTable('macho_movimento')) {
                $last = DB::table('macho_movimento')
                    ->selectRaw('MAX(id) as last_id, macho_id')
                    ->groupBy('macho_id');

                $query->leftJoinSub($last, 'lm', function ($join) {
                    $join->on('lm.macho_id', '=', 'macho.id');
                });

                $query->leftJoin('macho_movimento as mm', 'mm.id', '=', 'lm.last_id')
                    ->addSelect([
                        'mm.acao as ultima_acao',
                    ]);
            }

            if (! $includeTodos && Schema::hasTable('macho_movimento')) {
                $query->where(function ($q) {
                    $q->whereNull('mm.acao')
                      ->orWhereNotIn('mm.acao', ['morte', 'descarte', 'venda']);
                });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('macho.id_primaria', 'like', "%{$search}%")
                        ->orWhere('macho.id_secundaria', 'like', "%{$search}%");
                });
            }

            if (!empty($localizacao)) {
                $query->where('macho.localizacao', 'like', "%{$localizacao}%");
            }

            if (!empty($baia)) {
                $query->where('macho.baia', 'like', "%{$baia}%");
            }

            // Filtros de data (baseados no último movimento mm.data)
            $parsedInic = PigCycleService::parseFilterDate($dataInicInput);
            if ($parsedInic && Schema::hasTable('macho_movimento')) {
                $query->where('mm.data', '>=', $parsedInic->toDateString());
            }

            $parsedFim = PigCycleService::parseFilterDate($dataFimInput);
            if ($parsedFim && Schema::hasTable('macho_movimento')) {
                $query->where('mm.data', '<=', $parsedFim->toDateString());
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $rows = $query->offset($offset)->limit($limit)->get();

            return response()->json([
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($total / $limit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno: ' . $e->getMessage(), 'items' => [], 'total' => 0], 500);
        }
    }

    public function cios()
    {
        try {
            if (!Schema::hasTable('gestacao_cio')) {
                return response()->json(['items' => [], 'total' => 0]);
            }

            $limit = request()->integer('limit', 50);
            $page = request()->integer('page', 1);
            $search = request()->input('search');
            $dataInicInput = request()->input('data_inicial');
            $dataFimInput = request()->input('data_final');
            $cioNumero = request()->input('cio');

            $query = DB::table('gestacao_cio')
                ->join('femea', 'femea.id', '=', 'gestacao_cio.femea_id')
                ->select([
                    'gestacao_cio.id',
                    'gestacao_cio.data',
                    'gestacao_cio.femea_id',
                    'femea.id_primaria',
                    'femea.id_secundaria',
                    'gestacao_cio.peso',
                    'femea.data_nascimento'
                ])
                ->orderBy('gestacao_cio.data', 'desc');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('femea.id_primaria', 'like', "%{$search}%")
                        ->orWhere('femea.id_secundaria', 'like', "%{$search}%");
                });
            }

            // Filtros de data
            $parsedInic = PigCycleService::parseFilterDate($dataInicInput);
            if ($parsedInic) {
                $query->where('gestacao_cio.data', '>=', $parsedInic->toDateString());
            }

            $parsedFim = PigCycleService::parseFilterDate($dataFimInput);
            if ($parsedFim) {
                $query->where('gestacao_cio.data', '<=', $parsedFim->toDateString());
            }

            // Filtro número do cio (exato)
            $cioVal = request()->input('cio');
            if ($cioVal !== null && $cioVal !== '') {
                $query->where('gestacao_cio.cio', '=', $cioVal);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $rows = $query->offset($offset)->limit($limit)->get()->map(function ($row) {
                $row->data_formatada = PigCycleService::formatDisplayDate(Carbon::parse($row->data));
                $nasc = $row->data_nascimento ? Carbon::parse($row->data_nascimento) : null;
                $row->idade_dias = $nasc ? (int) $nasc->diffInDays(Carbon::parse($row->data)) : null;
                $row->peso_formatado = $row->peso ? number_format($row->peso, 2, ',', '.') . ' kg' : '-';
                return $row;
            });

            return response()->json([
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($total / $limit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    public function updateCio(\Illuminate\Http\Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'data' => 'required|string',
                'peso' => 'nullable|numeric',
            ]);

            $parsedDate = \App\Services\PigCycleService::parseFilterDate($validated['data']);
            if (!$parsedDate) {
                return response()->json(['message' => 'Data ou Dia PIG inválido'], 422);
            }

            $cio = DB::table('gestacao_cio')->where('id', $id)->first();
            if (!$cio) {
                return response()->json(['message' => 'Registro de cio não encontrado'], 404);
            }

            DB::statement("UPDATE gestacao_cio SET data = ?, peso = ? WHERE id = ?", [
                $parsedDate->toDateString(),
                $validated['peso'],
                $id
            ]);

            // Sincroniza o peso na ficha da fêmea (usando SQL puro por segurança)
            if ($validated['peso']) {
                DB::statement("UPDATE femea SET peso_atual = ? WHERE id = ?", [
                    $validated['peso'],
                    $cio->femea_id
                ]);
            }

            return response()->json(['message' => 'Cio atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar: ' . $e->getMessage()], 500);
        }
    }

    public function deleteCio(int $id)
    {
        try {
            DB::table('gestacao_cio')->where('id', $id)->delete();
            return response()->json(['message' => 'Cio excluído com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir: ' . $e->getMessage()], 500);
        }
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
