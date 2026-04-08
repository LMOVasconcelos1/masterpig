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
        if (! Schema::hasTable('meta')) {
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

            'gestacao_meta_taxa_paricao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gestacao_meta_perdas_reprodutivas' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'meta_manutencao_reposicao' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_descarte_matrizes' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_mortalidade_matrizes' => ['nullable', 'numeric', 'min:0'],
            'meta_manutencao_perdas_leitoas_pre_cobertura' => ['nullable', 'numeric', 'min:0'],

            'meta_selecao_idade_selecao' => ['nullable', 'numeric', 'min:0'],
            'meta_selecao_idade_cobertura' => ['nullable', 'numeric', 'min:0'],

            'meta_produtividade_dias_nao_produtivos' => ['nullable', 'numeric', 'min:0'],

            'meta_retencao_cobertura_p1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p4' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p5' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p6' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p7' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p8' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p9' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_retencao_pos_p10' => ['nullable', 'numeric', 'min:0', 'max:100'],

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

            // Novos Critérios Requeridos
            'criterio_leitoa_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_leitoa_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_leitoa_idade_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_leitoa_idade_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_matriz_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_matriz_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_matriz_idade_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_matriz_idade_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_matriz_ciclo_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_matriz_ciclo_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_gestante_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_idade_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_idade_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_ciclo_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_ciclo_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_dias_gestacao_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_gestante_dias_gestacao_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_macho_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_macho_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_macho_idade_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_macho_idade_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_venda_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_venda_peso_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_cio_entrada_leitoa_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_cio_entrada_leitoa_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_cio_intervalo_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_cio_intervalo_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_inconsistencia_descarte_dias_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_inconsistencia_descarte_dias_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_inconsistencia_macho_parado_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_inconsistencia_macho_parado_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_inconsistencia_macho_parado_max' => ['nullable', 'numeric', 'min:0'],

            // MANEJO DE GESTAÇÃO - METAS
            'meta_gestacao_montas_por_cobertura' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_periodo_gestacao' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_intervalo_desmame_cobertura' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_repeticao_cio' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_aborto' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_falsa_prenhez' => ['nullable', 'numeric', 'min:0'],
            'meta_gestacao_partos_femea_ano' => ['nullable', 'numeric', 'min:0'],
            
            // MANEJO DE GESTAÇÃO - CRITÉRIOS
            'criterio_matriz_vazia_max_dias' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_leitoa_idade_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_leitoa_idade_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_leitoa_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_leitoa_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_matriz_peso_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_matriz_peso_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_cobertura_lactante_permitida' => ['nullable', 'in:sim,nao'],

            'criterio_perda_aborto_dias_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_perda_aborto_dias_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_perda_repeticao_cio_dias_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_perda_repeticao_cio_dias_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_perda_falsa_gestacao_dias_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_perda_falsa_gestacao_dias_max' => ['nullable', 'numeric', 'min:0'],

            'criterio_intervalo_repeticao_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_repeticao_cobertura_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_aborto_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_aborto_cobertura_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_falsa_prenhez_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_falsa_prenhez_cobertura_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_cobertura_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_cobertura_cobertura_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_parto_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_parto_cobertura_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_lactacao_salto_cio_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_lactacao_salto_cio_max' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_vazio_salto_cobertura_min' => ['nullable', 'numeric', 'min:0'],
            'criterio_intervalo_vazio_salto_cobertura_max' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
