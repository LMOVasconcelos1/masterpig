<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoMovimentoController extends Controller
{
    public function mortes()
    {
        return $this->listarPorAcao('morte');
    }

    public function descartes()
    {
        return $this->listarPorAcao('descarte');
    }

    public function vendas()
    {
        return $this->listarPorAcao('venda');
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('macho_movimento')) {
            return response()->json([
                'message' => 'Tabela macho_movimento não existe no banco.',
            ], 422);
        }

        $row = DB::table('macho_movimento')->where('id', $id)->select(['id', 'acao'])->first();
        if (! $row) {
            return response()->json([
                'message' => 'Lançamento não encontrado.',
            ], 404);
        }

        $acao = (string) ($row->acao ?? '');
        if (! in_array($acao, ['morte', 'descarte', 'venda'], true)) {
            return response()->json([
                'message' => 'Este tipo de lançamento não pode ser excluído.',
            ], 422);
        }

        DB::table('macho_movimento')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Lançamento excluído com sucesso!',
        ]);
    }

    private function listarPorAcao(string $acao)
    {
        if (! Schema::hasTable('macho') || ! Schema::hasTable('macho_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ]);
        }

        $hasCausaId = Schema::hasColumn('macho_movimento', 'causa_id') && Schema::hasTable('causa');

        $query = DB::table('macho_movimento as mm')
            ->join('macho as m', 'm.id', '=', 'mm.macho_id')
            ->where('mm.acao', $acao)
            ->orderByDesc('mm.data')
            ->select([
                'mm.id',
                'mm.data',
                'm.id as macho_id',
                'm.id_primaria',
                'mm.observacoes',
            ]);

        if ($hasCausaId) {
            $query->leftJoin('causa as c', 'c.id', '=', 'mm.causa_id')
                ->addSelect('c.nome as causa_nome');
        }

        $rows = $query->get();

        $items = $rows->map(function ($row) use ($hasCausaId, $acao) {
            $causa = $hasCausaId ? ($row->causa_nome ?? null) : null;
            if (! $causa) {
                $causa = $row->observacoes ?? '-';
            }

            return [
                'id' => $row->id,
                'acao' => $acao,
                'data' => PigCycleService::formatDisplayDate(Carbon::parse($row->data)),
                'macho_id' => $row->macho_id,
                'id_primaria' => $row->id_primaria,
                'causa' => $causa,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }
}
