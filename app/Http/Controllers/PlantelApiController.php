<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlantelApiController extends Controller
{
    public function femeas()
    {
        if (!Schema::hasTable('femea')) {
            return response()->json([]);
        }

        $includeTodas = request()->boolean('all');

        $query = DB::table('femea')->orderBy('id_primaria')->select([
            'id',
            'id_primaria',
            'id_secundaria',
            'localizacao',
            'baia',
            'tipo_compra as tipo',
        ]);

        if (!$includeTodas && Schema::hasTable('femea_movimento')) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('femea_movimento as fm')
                    ->whereColumn('fm.femea_id', 'femea.id')
                    ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
            });
        }

        return response()->json($query->limit(5000)->get());
    }

    public function machos()
    {
        if (!Schema::hasTable('macho')) {
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

        if (!$includeTodos && Schema::hasTable('macho_movimento')) {
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
        if (!Schema::hasTable('causa') || !Schema::hasTable('grupo_causa')) {
            return response()->json([]);
        }

        $tipo = mb_strtolower($tipo);

        $items = Causa::query()
            ->with('grupoCausa')
            ->where('situacao', true)
            ->whereHas('grupoCausa', function ($q) use ($tipo) {
                $q->whereRaw('LOWER(nome) LIKE ?', ['%' . $tipo . '%']);
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
