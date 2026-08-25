<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlantelRelatorioController extends Controller
{
    public function femeasFilter()
    {
        return view('admin.plantel.relatorios.femeas-filter');
    }

    public function machosFilter()
    {
        return view('admin.plantel.relatorios.machos-filter');
    }

    public function femeas(Request $request)
    {
        $data_emissao = Carbon::now()->format('d/m/Y H:i');
        $items = $this->getFemeasItems($request->all());

        $format = mb_strtolower((string) $request->query('format', 'html'));
        $baseFile = 'relatorio-femeas-'.Carbon::now()->format('Ymd-Hi');

        if ($format === 'csv') {
            return $this->streamCsv(
                $baseFile.'.csv',
                ['id_primaria', 'id_secundaria', 'tipo', 'raca', 'localizacao', 'baia', 'data_compra', 'ultima_operacao', 'status'],
                $items->map(fn ($r) => [
                    $r['id_primaria'] ?? '',
                    $r['id_secundaria'] ?? '',
                    $r['tipo'] ?? '',
                    $r['raca'] ?? '',
                    $r['localizacao'] ?? '',
                    $r['baia'] ?? '',
                    $r['data_compra'] ?? '',
                    $r['ultima_operacao'] ?? '',
                    $r['status'] ?? '',
                ])->all()
            );
        }

        if ($format === 'pdf') {
            $isPdf = true;

            return Pdf::loadView('admin.plantel.relatorios.femeas', compact('items', 'data_emissao', 'isPdf'))
                ->setPaper('a4', 'landscape')
                ->download($baseFile.'.pdf');
        }

        $isPdf = false;

        return view('admin.plantel.relatorios.femeas', compact('items', 'data_emissao'));
    }

    public function machos(Request $request)
    {
        $data_emissao = Carbon::now()->format('d/m/Y H:i');
        $items = $this->getMachosItems();

        $format = mb_strtolower((string) $request->query('format', 'html'));
        $baseFile = 'relatorio-machos-'.Carbon::now()->format('Ymd-Hi');

        if ($format === 'csv') {
            return $this->streamCsv(
                $baseFile.'.csv',
                ['id_primaria', 'id_secundaria', 'raca', 'localizacao', 'baia', 'data_compra', 'ultima_operacao', 'status'],
                $items->map(fn ($r) => [
                    $r['id_primaria'] ?? '',
                    $r['id_secundaria'] ?? '',
                    $r['raca'] ?? '',
                    $r['localizacao'] ?? '',
                    $r['baia'] ?? '',
                    $r['data_compra'] ?? '',
                    $r['ultima_operacao'] ?? '',
                    $r['status'] ?? '',
                ])->all()
            );
        }

        if ($format === 'pdf') {
            $isPdf = true;

            return Pdf::loadView('admin.plantel.relatorios.machos', compact('items', 'data_emissao', 'isPdf'))
                ->setPaper('a4', 'landscape')
                ->download($baseFile.'.pdf');
        }

        $isPdf = false;

        return view('admin.plantel.relatorios.machos', compact('items', 'data_emissao'));
    }

    private function streamCsv(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getFemeasItems(array $filters = [])
    {
        if (! Schema::hasTable('femea')) {
            return collect();
        }

        $query = DB::table('femea as f')
            ->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')
            ->leftJoin('fornecedor as fo', 'fo.id', '=', 'f.fornecedor_id')
            ->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.tipo_compra',
                'r.nome as raca_nome',
                'f.localizacao',
                'f.baia',
                'f.data_compra',
                'f.peso_atual',
                'f.data_nascimento',
                'f.ciclos_ate_compra',
            ])
            ->orderBy('f.id_primaria');

        // JOINs para estados reprodutivos e situações
        if (Schema::hasTable('gestacao_cobertura')) {
            $lastCob = DB::table('gestacao_cobertura')
                ->selectRaw('MAX(data) as last_data, femea_id')
                ->groupBy('femea_id');
            $query->leftJoinSub($lastCob, 'lc', 'lc.femea_id', '=', 'f.id')
                ->addSelect('lc.last_data as last_cobertura');
        }

        if (Schema::hasTable('maternidade_parto')) {
            $lastParto = DB::table('maternidade_parto')
                ->selectRaw('MAX(data) as last_data, femea_id, COUNT(id) as partos_count')
                ->groupBy('femea_id');
            $query->leftJoinSub($lastParto, 'lp', 'lp.femea_id', '=', 'f.id')
                ->addSelect(['lp.last_data as last_parto', 'lp.partos_count']);
        }

        if (Schema::hasTable('maternidade_desmame')) {
            $lastDesmame = DB::table('maternidade_desmame as md')
                ->join('maternidade_parto as mp', 'mp.id', '=', 'md.parto_id')
                ->selectRaw('MAX(md.data) as last_data, mp.femea_id')
                ->groupBy('mp.femea_id');
            $query->leftJoinSub($lastDesmame, 'ld', 'ld.femea_id', '=', 'f.id')
                ->addSelect('ld.last_data as last_desmame');
        }

        if (Schema::hasTable('femea_movimento')) {
            $last = DB::table('femea_movimento')
                ->selectRaw('MAX(id) as last_id, femea_id')
                ->groupBy('femea_id');

            $query->leftJoinSub($last, 'lm', function ($join) {
                $join->on('lm.femea_id', '=', 'f.id');
            });

            $query->leftJoin('femea_movimento as fm', 'fm.id', '=', 'lm.last_id')
                ->addSelect([
                    'fm.acao as ultima_acao',
                    'fm.data as ultima_data',
                ]);
        }

        // Filtro de Categoria (Matriz vs Leitoa)
        if (isset($filters['categoria']) && $filters['categoria'] !== '') {
            if ($filters['categoria'] === 'leitoa') {
                $query->where('f.tipo_compra', 'leitoa');
            } else if ($filters['categoria'] === 'matriz') {
                $query->where('f.tipo_compra', '!=', 'leitoa');
            }
        }

        // Filtro de Situação
        if (isset($filters['situacao']) && $filters['situacao'] !== '') {
            if (Schema::hasTable('femea_movimento')) {
                if ($filters['situacao'] === 'ativas') {
                    $query->where(function($q) {
                        $q->whereNull('fm.acao')
                          ->orWhereNotIn('fm.acao', ['morte', 'descarte', 'venda']);
                    });
                } else if ($filters['situacao'] === 'descartadas') {
                    $query->where('fm.acao', 'descarte');
                } else if ($filters['situacao'] === 'pre_descartadas') {
                    $query->where('fm.acao', 'pre_descarte');
                }
            }
        }

        if (isset($filters['peso_min']) && $filters['peso_min'] !== '') {
            $query->where('f.peso_atual', '>=', (float) $filters['peso_min']);
        }
        if (isset($filters['peso_max']) && $filters['peso_max'] !== '') {
            $query->where('f.peso_atual', '<=', (float) $filters['peso_max']);
        }

        if (isset($filters['idade_min']) && $filters['idade_min'] !== '') {
            $date = Carbon::now()->subDays((int) $filters['idade_min'])->toDateString();
            $query->where('f.data_nascimento', '<=', $date);
        }
        if (isset($filters['idade_max']) && $filters['idade_max'] !== '') {
            $date = Carbon::now()->subDays((int) $filters['idade_max'])->toDateString();
            $query->where('f.data_nascimento', '>=', $date);
        }

        // Filtro de Ciclo (Paridade)
        if ((isset($filters['ciclo_min']) && $filters['ciclo_min'] !== '') || (isset($filters['ciclo_max']) && $filters['ciclo_max'] !== '')) {
            $min = (int) ($filters['ciclo_min'] ?? 0);
            $max = (int) ($filters['ciclo_max'] ?? 99);
            // Ciclo = ciclos_ate_compra + partos_count
            $query->whereRaw('(COALESCE(f.ciclos_ate_compra, 0) + COALESCE(lp.partos_count, 0)) BETWEEN ? AND ?', [$min, $max]);
        }

        $items = $query->limit(20000)->get()->map(function ($row) {
            $now = Carbon::today();
            $lastCob = $row->last_cobertura ? Carbon::parse($row->last_cobertura) : null;
            $lastParto = $row->last_parto ? Carbon::parse($row->last_parto) : null;
            $lastDesmame = $row->last_desmame ? Carbon::parse($row->last_desmame) : null;

            // Determinar Estado Reprodutivo
            $estado = 'Vazia';
            $diasNoEstado = 0;

            if ($lastCob && (!$lastParto || $lastCob->gt($lastParto))) {
                $estado = 'Gestante';
                $diasNoEstado = $lastCob->diffInDays($now);
            } else if ($lastParto && (!$lastDesmame || $lastParto->gt($lastDesmame))) {
                $estado = 'Lactante';
                $diasNoEstado = $lastParto->diffInDays($now);
            } else {
                $estado = 'Vazia';
                $baseDate = $lastDesmame ?: ($lastParto ?: ($lastCob ?: Carbon::parse($row->data_compra ?? now())));
                $diasNoEstado = $baseDate->diffInDays($now);
            }

            $row->calculated_estado = $estado;
            $row->calculated_dias = $diasNoEstado;
            $row->calculated_ciclo = ($row->ciclos_ate_compra ?? 0) + ($row->partos_count ?? 0);

            return $row;
        });

        // Filtragem em memória para estados reprodutivos e seus intervalos (mais robusto)
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $items = $items->filter(fn($r) => strtolower($r->calculated_estado) === strtolower($filters['estado']));
        }

        if (isset($filters['vazio_min']) && $filters['vazio_min'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Vazia' && $r->calculated_dias >= (int) $filters['vazio_min']);
        }
        if (isset($filters['vazio_max']) && $filters['vazio_max'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Vazia' && $r->calculated_dias <= (int) $filters['vazio_max']);
        }

        if (isset($filters['gestante_min']) && $filters['gestante_min'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Gestante' && $r->calculated_dias >= (int) $filters['gestante_min']);
        }
        if (isset($filters['gestante_max']) && $filters['gestante_max'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Gestante' && $r->calculated_dias <= (int) $filters['gestante_max']);
        }

        if (isset($filters['lactante_min']) && $filters['lactante_min'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Lactante' && $r->calculated_dias >= (int) $filters['lactante_min']);
        }
        if (isset($filters['lactante_max']) && $filters['lactante_max'] !== '') {
            $items = $items->filter(fn($r) => $r->calculated_estado === 'Lactante' && $r->calculated_dias <= (int) $filters['lactante_max']);
        }

        return $items->map(function ($row) {
            $ultimaOperacao = '-';
            $status = 'Ativo';

            if (! empty($row->ultima_acao)) {
                $ultimaOperacao = $row->ultima_acao;
                if (! empty($row->ultima_data)) {
                    $ultimaOperacao .= ' - '.PigCycleService::formatDisplayDate(Carbon::parse($row->ultima_data));
                }

                if (in_array($row->ultima_acao, ['morte', 'descarte', 'venda'], true)) {
                    $status = 'Inativo ('.$row->ultima_acao.')';
                }
            }

            return [
                'id' => $row->id,
                'id_primaria' => $row->id_primaria,
                'id_secundaria' => $row->id_secundaria,
                'tipo' => $row->tipo_compra,
                'raca' => $row->raca_nome,
                'localizacao' => $row->localizacao,
                'baia' => $row->baia,
                'ciclo' => $row->calculated_ciclo,
                'estado' => $row->calculated_estado . ($row->calculated_dias > 0 ? ' ('.$row->calculated_dias.'d)' : ''),
                'peso' => $row->peso_atual ? number_format($row->peso_atual, 2, ',', '.') : '-',
                'idade' => $row->data_nascimento ? (int) Carbon::parse($row->data_nascimento)->startOfDay()->diffInDays(Carbon::today()) : '-',
                'data_compra' => $row->data_compra ? PigCycleService::formatDisplayDate(Carbon::parse($row->data_compra)) : null,
                'ultima_operacao' => $ultimaOperacao,
                'status' => $status,
            ];
        });
    }

    private function getMachosItems()
    {
        if (! Schema::hasTable('macho')) {
            return collect();
        }

        $query = DB::table('macho as m')
            ->leftJoin('raca as r', 'r.id', '=', 'm.raca_id')
            ->select([
                'm.id',
                'm.id_primaria',
                'm.id_secundaria',
                'r.nome as raca_nome',
                'm.localizacao',
                'm.baia',
                'm.data_compra',
            ])
            ->orderBy('m.id_primaria');

        if (Schema::hasTable('macho_movimento')) {
            $last = DB::table('macho_movimento')
                ->selectRaw('MAX(id) as last_id, macho_id')
                ->groupBy('macho_id');

            $query->leftJoinSub($last, 'lm', function ($join) {
                $join->on('lm.macho_id', '=', 'm.id');
            });

            $query->leftJoin('macho_movimento as mm', 'mm.id', '=', 'lm.last_id')
                ->addSelect([
                    'mm.acao as ultima_acao',
                    'mm.data as ultima_data',
                ]);
        }

        return $query->limit(20000)->get()->map(function ($row) {
            $ultimaOperacao = '-';
            $status = 'Ativo';

            if (! empty($row->ultima_acao)) {
                $ultimaOperacao = $row->ultima_acao;
                if (! empty($row->ultima_data)) {
                    $ultimaOperacao .= ' - '.PigCycleService::formatDisplayDate(Carbon::parse($row->ultima_data));
                }

                if (in_array($row->ultima_acao, ['morte', 'descarte', 'venda'], true)) {
                    $status = 'Inativo ('.$row->ultima_acao.')';
                }
            }

            return [
                'id' => $row->id,
                'id_primaria' => $row->id_primaria,
                'id_secundaria' => $row->id_secundaria,
                'raca' => $row->raca_nome,
                'localizacao' => $row->localizacao,
                'baia' => $row->baia,
                'data_compra' => $row->data_compra ? PigCycleService::formatDisplayDate(Carbon::parse($row->data_compra)) : null,
                'ultima_operacao' => $ultimaOperacao,
                'status' => $status,
            ];
        });
    }
}
