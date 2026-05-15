<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SemenController extends Controller
{
    public function page()
    {
        if (! Schema::hasTable('semen')) {
            return view('admin.semen.index', [
                'errorMessage' => 'Tabela de sêmen ainda não foi criada no banco.',
            ]);
        }

        return view('admin.semen.index', [
            'errorMessage' => null,
        ]);
    }

    public function index(Request $request)
    {
        $limit = max(1, min(5000, (int) $request->query('limit', 200)));
        $page = max(1, (int) $request->query('page', 1));
        $search = $request->query('search', '');
        $racaId = $request->query('raca_id', '');
        $fornecedorId = $request->query('fornecedor_id', '');
        $dataInicial = $request->query('data_inicial', '');
        $dataFinal = $request->query('data_final', '');

        if (! Schema::hasTable('semen')) {
            return response()->json([
                'success' => false,
                'items' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'pages' => 0,
                'message' => 'Tabela de sêmen ainda não foi criada no banco.',
            ], 422);
        }

        $query = DB::table('semen as s')
            ->leftJoin('raca as r', 'r.id', '=', 's.raca_id')
            ->leftJoin('fornecedor as f', 'f.id', '=', 's.fornecedor_id')
            ->orderByDesc('s.data_compra')
            ->select([
                's.id',
                's.id_primaria',
                's.id_secundaria',
                's.data_nascimento',
                's.data_compra',
                's.valor_compra',
                's.criado_em',
                's.atualizado_em',
                'r.nome as raca_nome',
                'f.nome as fornecedor_nome',
            ]);

        // Aplicar filtros
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('s.id_primaria', 'like', "%{$search}%")
                  ->orWhere('s.id_secundaria', 'like', "%{$search}%")
                  ->orWhere('r.nome', 'like', "%{$search}%")
                  ->orWhere('f.nome', 'like', "%{$search}%");
            });
        }

        if ($racaId) {
            $query->where('s.raca_id', $racaId);
        }

        if ($fornecedorId) {
            $query->where('s.fornecedor_id', $fornecedorId);
        }

        if ($dataInicial) {
            $query->where('s.data_compra', '>=', $dataInicial);
        }

        if ($dataFinal) {
            $query->where('s.data_compra', '<=', $dataFinal);
        }

        $total = $query->count();
        $items = $query->offset(($page - 1) * $limit)->limit($limit)->get();

        return response()->json([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('semen')) {
            return response()->json([
                'message' => 'A tabela de sêmen ainda não foi criada no banco. Execute a query SQL fornecida.',
            ], 422);
        }

        $validated = $request->validate([
            'id_primaria' => ['required', 'string', 'max:50', 'unique:semen,id_primaria'],
            'id_secundaria' => ['nullable', 'string', 'max:50'],
            'raca_id' => ['nullable', 'integer', 'exists:raca,id'],
            'data_nascimento' => ['nullable', 'date'],
            'data_compra' => ['required', 'date'],
            'valor_compra' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedor,id'],
        ]);

        $validated['criado_em'] = now();
        $validated['atualizado_em'] = now();

        try {
            $semen = DB::table('semen')->insertGetId($validated);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Já existe um registro de sêmen com essa identificação.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'id' => $semen,
            'message' => 'Sêmen cadastrado com sucesso!',
        ], 201);
    }

    public function show($id)
    {
        if (!Schema::hasTable('semen')) {
            return response()->json([
                'message' => 'A tabela de sêmen ainda não foi criada no banco.',
            ], 422);
        }

        $semen = DB::table('semen as s')
            ->leftJoin('raca as r', 'r.id', '=', 's.raca_id')
            ->leftJoin('fornecedor as f', 'f.id', '=', 's.fornecedor_id')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.id_primaria',
                's.id_secundaria',
                's.data_nascimento',
                's.data_compra',
                's.valor_compra',
                's.criado_em',
                's.atualizado_em',
                'r.nome as raca_nome',
                'f.nome as fornecedor_nome',
            ])
            ->first();

        if (!$semen) {
            return response()->json([
                'message' => 'Sêmen não encontrado.',
            ], 404);
        }

        return response()->json($semen);
    }

    public function update(Request $request, $id)
    {
        if (!Schema::hasTable('semen')) {
            return response()->json([
                'message' => 'A tabela de sêmen ainda não foi criada no banco.',
            ], 422);
        }

        $semen = DB::table('semen')->where('id', $id)->first();

        if (!$semen) {
            return response()->json([
                'message' => 'Sêmen não encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'id_primaria' => ['required', 'string', 'max:50', 'unique:semen,id_primaria,' . $id],
            'id_secundaria' => ['nullable', 'string', 'max:50'],
            'raca_id' => ['nullable', 'integer', 'exists:raca,id'],
            'data_nascimento' => ['nullable', 'date'],
            'data_compra' => ['required', 'date'],
            'valor_compra' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedor,id'],
        ]);

        $validated['atualizado_em'] = now();

        DB::table('semen')->where('id', $id)->update($validated);

        return response()->json([
            'message' => 'Sêmen atualizado com sucesso!',
        ]);
    }

    public function destroy($id)
    {
        if (!Schema::hasTable('semen')) {
            return response()->json([
                'message' => 'A tabela de sêmen ainda não foi criada no banco.',
            ], 422);
        }

        $semen = DB::table('semen')->where('id', $id)->first();

        if (!$semen) {
            return response()->json([
                'message' => 'Sêmen não encontrado.',
            ], 404);
        }

        DB::table('semen')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Sêmen excluído com sucesso!',
        ]);
    }
}
