<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MetasController extends Controller
{
    public function page()
    {
        return view('admin.ajustes.metas');
    }

    public function index()
    {
        if (!Schema::hasTable('meta')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela meta não existe no banco.',
            ]);
        }

        $rows = DB::table('meta')->select(['chave', 'valor'])->get();

        $items = [];
        foreach ($rows as $row) {
            $items[$row->chave] = $row->valor;
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('meta')) {
            return response()->json([
                'message' => 'Tabela meta não existe no banco.',
            ], 422);
        }

        $rules = $this->rules();
        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $rules) {
            $now = now();
            foreach (array_keys($rules) as $key) {
                if (!array_key_exists($key, $validated)) continue;
                $value = $validated[$key];

                $exists = DB::table('meta')->where('chave', $key)->exists();
                if ($exists) {
                    DB::table('meta')->where('chave', $key)->update([
                        'valor' => $value,
                        'atualizado_em' => $now,
                    ]);
                } else {
                    DB::table('meta')->insert([
                        'chave' => $key,
                        'valor' => $value,
                        'criado_em' => $now,
                        'atualizado_em' => $now,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Metas salvas com sucesso!',
        ]);
    }

    private function rules(): array
    {
        return [
            'meta_plantel_estoque_matrizes' => ['nullable', 'numeric', 'min:0'],
            'meta_plantel_estoque_leitoas' => ['nullable', 'numeric', 'min:0'],

            'meta_entrada_peso_leitoa' => ['nullable', 'numeric', 'min:0'],
            'meta_entrada_peso_matriz' => ['nullable', 'numeric', 'min:0'],
            'meta_entrada_peso_macho' => ['nullable', 'numeric', 'min:0'],

            'meta_manutencao_reposicao' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_descarte_matrizes' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_mortalidade_matrizes' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_perdas_leitoas_pre_cobertura' => ['nullable', 'numeric', 'min:0'],

            'meta_selecao_idade_selecao' => ['nullable', 'numeric', 'min:0'],
            'meta_selecao_idade_cobertura' => ['nullable', 'numeric', 'min:0'],

            'meta_produtividade_dias_nao_produtivos' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
