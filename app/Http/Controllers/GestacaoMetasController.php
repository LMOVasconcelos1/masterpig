<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoMetasController extends Controller
{
    private function rules(): array
    {
        return [
            'gestacao_meta_taxa_paricao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gestacao_meta_perdas_reprodutivas' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function index()
    {
        if (! Schema::hasTable('meta')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela meta não existe no banco.',
            ]);
        }

        $keys = array_keys($this->rules());
        $rows = DB::table('meta')->whereIn('chave', $keys)->pluck('valor', 'chave');

        $items = [];
        foreach ($keys as $k) {
            $items[$k] = $rows[$k] ?? null;
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('meta')) {
            return response()->json([
                'message' => 'Tabela meta não existe no banco.',
            ], 422);
        }

        $rules = $this->rules();
        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $rules) {
            $now = now();
            foreach (array_keys($rules) as $key) {
                if (! array_key_exists($key, $validated)) {
                    continue;
                }
                $value = $validated[$key];
                $value = $value === null ? null : (string) $value;

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
            'message' => 'Metas de gestação salvas com sucesso!',
        ]);
    }
}
