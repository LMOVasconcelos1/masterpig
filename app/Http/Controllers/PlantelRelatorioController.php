<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlantelRelatorioController extends Controller
{
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
            ])
            ->orderBy('f.id_primaria');

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

        return $query->limit(20000)->get()->map(function ($row) {
            $ultimaOperacao = '-';
            $status = 'Ativo';

            if (! empty($row->ultima_acao)) {
                $ultimaOperacao = $row->ultima_acao;
                if (! empty($row->ultima_data)) {
                    $ultimaOperacao .= ' - '.Carbon::parse($row->ultima_data)->format('d/m/Y');
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
                'peso' => $row->peso_atual ? number_format($row->peso_atual, 2, ',', '.') : '-',
                'idade' => $row->data_nascimento ? Carbon::parse($row->data_nascimento)->diffInDays(Carbon::now()) : '-',
                'data_compra' => $row->data_compra ? Carbon::parse($row->data_compra)->format('d/m/Y') : null,
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
                    $ultimaOperacao .= ' - '.Carbon::parse($row->ultima_data)->format('d/m/Y');
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
                'data_compra' => $row->data_compra ? Carbon::parse($row->data_compra)->format('d/m/Y') : null,
                'ultima_operacao' => $ultimaOperacao,
                'status' => $status,
            ];
        });
    }
}
