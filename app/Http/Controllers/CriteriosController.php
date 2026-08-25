<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CriteriosController extends Controller
{
    public function page()
    {
        return view('admin.ajustes.criterios');
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

        $defaults = [
            'criterio_cobertura_idade_min_dias' => '210',
            'criterio_cobertura_idade_max_dias' => '240',
            'criterio_cobertura_ciclos_min' => '3',
            'criterio_cobertura_peso_min_kg' => '0',
            'criterio_cobertura_peso_max_kg' => '0',
            'criterio_cobertura_presenca_cio' => 'sim',
            'criterio_dias_ate_cio' => '21',
            'criterio_dias_cio' => '3',
            'criterio_dias_gestacao' => '114',
            'criterio_dias_lactacao_min' => '21',
            'criterio_dias_lactacao_max' => '28',
            'criterio_dias_intervalo_desmame_cio' => '5',
            'criterio_leitoa_idade_min_dias' => '150',
            'criterio_leitoa_idade_max_dias' => '210',
            'criterio_maturidade_idade_min_dias' => '151',
            'criterio_maturidade_idade_max_dias' => '220',
            'criterio_calendario_tipo' => 'gregoriano',
        ];

        $items = [];
        foreach ($keys as $k) {
            $items[$k] = $rows[$k] ?? ($defaults[$k] ?? null);
        }

        if (isset($items['criterio_cobertura_idade_min_dias']) && (string) $items['criterio_cobertura_idade_min_dias'] === '0') {
            $items['criterio_cobertura_idade_min_dias'] = $defaults['criterio_cobertura_idade_min_dias'];
        }
        if (isset($items['criterio_cobertura_idade_max_dias']) && (string) $items['criterio_cobertura_idade_max_dias'] === '0') {
            $items['criterio_cobertura_idade_max_dias'] = $defaults['criterio_cobertura_idade_max_dias'];
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

                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } elseif ($value === null) {
                    $value = null;
                } else {
                    $value = (string) $value;
                }

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
            'message' => 'Critérios salvos com sucesso!',
        ]);
    }

    private function rules(): array
    {
        return [
            'criterios_enabled' => ['nullable', 'boolean'],


            'criterio_cobertura_idade_min_dias' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'criterio_cobertura_idade_max_dias' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'criterio_cobertura_ciclos_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'criterio_cobertura_peso_min_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'criterio_cobertura_peso_max_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'criterio_cobertura_presenca_cio' => ['nullable', 'in:sim,nao'],

            'criterio_dias_ate_cio' => ['nullable', 'integer', 'min:0', 'max:365'],
            'criterio_dias_cio' => ['nullable', 'integer', 'min:1', 'max:10'],
            'criterio_dias_gestacao' => ['nullable', 'integer', 'min:1', 'max:200'],
            'criterio_dias_lactacao_min' => ['nullable', 'integer', 'min:1', 'max:60'],
            'criterio_dias_lactacao_max' => ['nullable', 'integer', 'min:1', 'max:60'],
            'criterio_dias_intervalo_desmame_cio' => ['nullable', 'integer', 'min:0', 'max:30'],
            'criterio_leitoa_idade_min_dias' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'criterio_leitoa_idade_max_dias' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'criterio_maturidade_idade_min_dias' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'criterio_maturidade_idade_max_dias' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'criterio_calendario_tipo' => ['nullable', 'in:gregoriano,1000_dias'],
        ];
    }
}
