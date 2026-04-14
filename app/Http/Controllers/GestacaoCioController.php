<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use App\Services\CioCountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoCioController extends Controller
{
    /**
     * Obtém valor inteiro da tabela de metas ou retorna valor padrão
     * Utilizada para buscar critérios como idade mínima e dias até o cio
     * @param string $key Chave da meta
     * @param int $default Valor padrão se não encontrar
     * @return int Valor da meta ou padrão
     */
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

    /**
     * Lista todos os registros de cio das fêmeas
     * Aplica filtros por busca, período e número do cio
     * Calcula dia PIG, número do cio, idade e outras informações relevantes
     * Retorna dados formatados para exibição no frontend
     */
    public function index(Request $request)
    {
        if (! Schema::hasTable('gestacao_cio')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela gestacao_cio não existe no banco. Entre em contato com o suporte.',
            ]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $search = $request->input('search');
        $dataInicInput = $request->input('data_inicial');
        $dataFimInput = $request->input('data_final');
        $cioFiltro = $request->input('cio');

        $query = DB::table('gestacao_cio as gc')
            ->join('femea as f', 'f.id', '=', 'gc.femea_id')
            ->orderByDesc('gc.data');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('f.id_primaria', 'like', "%{$search}%")
                  ->orWhere('f.id_secundaria', 'like', "%{$search}%");
            });
        }

        // Filtros de data
        $parsedInic = PigCycleService::parseFilterDate($dataInicInput);
        if ($parsedInic) {
            $query->where('gc.data', '>=', $parsedInic->toDateString());
        }

        $parsedFim = PigCycleService::parseFilterDate($dataFimInput);
        if ($parsedFim) {
            $query->where('gc.data', '<=', $parsedFim->toDateString());
        }

        $query->select([
                'gc.id as cio_id',
                'gc.data',
                'gc.peso',
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.peso_atual',
                'f.data_nascimento',
                'f.raca_id',
                'f.localizacao',
                'f.baia',
            ])
            ->limit($limit);

        $rows = $query->get();
        $ids = $rows->pluck('id')->unique()->toArray();

        $lastCoberturas = [];
        if (!empty($ids) && Schema::hasTable('gestacao_cobertura')) {
            $lastCoberturas = DB::table('gestacao_cobertura')
                ->whereIn('femea_id', $ids)
                ->selectRaw('femea_id, MAX(data) as last_data')
                ->groupBy('femea_id')
                ->pluck('last_data', 'femea_id')
                ->toArray();
        }

        $items = $rows->map(function ($row) use ($lastCoberturas) {
                $dataCio = Carbon::parse($row->data);
                $lastCob = isset($lastCoberturas[$row->id]) ? Carbon::parse($lastCoberturas[$row->id]) : null;
                
                // Calcular dia PIG usando o método corrigido com ciclo de 1000 dias
                $diaPig = PigCycleService::toPigDay($dataCio);
                
                $diaCiclo = null;
                if ($lastCob) {
                    $diaCiclo = $lastCob->diffInDays($dataCio, false);
                }

                // Calcular número do cio - Usando CioCountingService para lógica consistente
                $numeroCio = CioCountingService::calcularNumeroCioPorData($row->id, $dataCio, $lastCob);
                
                // Debug: Log para verificar contagem (pode ser removido em produção)
                if (config('app.debug')) {
                    \Log::info("Cio ID: {$row->cio_id}, Fêmea ID: {$row->id}, Última cobertura: " . ($lastCob ? $lastCob->toDateString() : 'N/A') . 
                              ", Data cio: {$dataCio->toDateString()}, Número cio: {$numeroCio}");
                }

                // Calcular idade no momento do cio
                $idade = null;
                if ($row->data_nascimento) {
                    $nascimento = Carbon::parse($row->data_nascimento);
                    $idade = $nascimento->diffInDays($dataCio);
                }

                return [
                    'id' => (int) $row->id,
                    'cio_id' => (int) $row->cio_id,
                    'matriz' => (string) $row->id_primaria,
                    'id_primaria' => $row->id_primaria,
                    'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                    'id_secundaria' => $row->id_secundaria,
                    'data' => PigCycleService::formatDisplayDate($dataCio),
                    'dia_ciclo' => $diaPig,
                    'cio' => $numeroCio . 'º cio',
                    'peso' => $row->peso ? number_format($row->peso, 2, ',', '.') . ' kg' : '-',
                    'idade' => $idade !== null ? $idade . ' dias' : '-',
                    'raca_id' => $row->raca_id,
                    'localizacao' => $row->localizacao,
                    'baia' => $row->baia,
                    'raw_data' => $dataCio->toDateString(),
                    'raw_peso' => $row->peso
                ];
            })
            ->values();

        // Filtro de número do cio (aplicado sobre a coleção mapeada)
        if ($cioFiltro !== null && $cioFiltro !== '') {
            $items = $items->filter(function($it) use ($cioFiltro) {
                // A chave "cio" contém algo como "1º cio"
                return strpos($it['cio'], $cioFiltro . 'º') === 0;
            })->values();
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Registra um novo cio para uma fêmea
     * Valida se a fêmea pode ter cio (leitoa ou matriz vazia)
     * Verifica idade mínima e intervalo mínimo entre cios
     * Atualiza peso atual da fêmea se informado
     * Retorna mensagem de sucesso ou erro com validações específicas
     */
    public function store(Request $request)
    {
        if (! Schema::hasTable('gestacao_cio') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'data' => ['required', 'date'],
            'peso' => ['nullable', 'numeric', 'min:0'],
        ]);

        $row = DB::table('femea')
            ->where('id', (int) $validated['femea_id'])
            ->select(['id', 'tipo_compra', 'data_nascimento'])
            ->first();

        if (! $row) {
            return response()->json([
                'message' => 'Fêmea inválida.',
            ], 422);
        }

        $tipo = (string) $row->tipo_compra;
        if (! in_array($tipo, ['leitoa', 'matriz_vazia'], true)) {
            return response()->json([
                'message' => 'Registro de cio é permitido apenas para leitoas ou matrizes.',
            ], 422);
        }

        if (empty($row->data_nascimento)) {
            return response()->json([
                'message' => 'Data de nascimento é obrigatória para registrar cio.',
            ], 422);
        }

        $data = Carbon::parse($validated['data'])->startOfDay();
        $nascimento = Carbon::parse($row->data_nascimento)->startOfDay();
        $idadeDias = $nascimento->diffInDays($data);

        // Obter critérios de validação do metas.blade.php
        $entradaLeitoaCioMin = $this->metaInt('criterio_entrada_leitoa_cio_min', null);
        $entradaLeitoaCioMax = $this->metaInt('criterio_entrada_leitoa_cio_max', null);
        $intervaloCiosLeitoaMin = $this->metaInt('criterio_intervalo_cios_leitoa_min', null);
        $intervaloCiosLeitoaMax = $this->metaInt('criterio_intervalo_cios_leitoa_max', null);

        // Validação para leitoas: dias entre entrada e primeiro cio
        if ($tipo === 'leitoa' && $entradaLeitoaCioMin !== null && $entradaLeitoaCioMax !== null) {
            // Buscar data de compra da fêmea
            $dataCompra = DB::table('femea_movimento')
                ->where('femea_id', (int) $validated['femea_id'])
                ->where('acao', 'compra')
                ->value('data');

            if ($dataCompra) {
                $diasDesdeEntrada = Carbon::parse($dataCompra)->diffInDays($data);
                if ($diasDesdeEntrada < $entradaLeitoaCioMin || $diasDesdeEntrada > $entradaLeitoaCioMax) {
                    return response()->json([
                        'message' => "Para leitoas, o cio deve ser registrado entre {$entradaLeitoaCioMin} e {$entradaLeitoaCioMax} dias após a entrada (atualmente: {$diasDesdeEntrada} dias).",
                    ], 422);
                }
            }
        }

        $exists = DB::table('gestacao_cio')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', $data->toDateString())
            ->exists();

        $lastCio = DB::table('gestacao_cio')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', '<=', $data->toDateString())
            ->max('data');

        if ($lastCio) {
            $last = Carbon::parse($lastCio)->startOfDay();
            $diasEntreCios = $last->diffInDays($data);
            
            // Validação específica para leitoas
            if ($tipo === 'leitoa') {
                if ($intervaloCiosLeitoaMin !== null && $intervaloCiosLeitoaMax !== null) {
                    if ($diasEntreCios < $intervaloCiosLeitoaMin || $diasEntreCios > $intervaloCiosLeitoaMax) {
                        return response()->json([
                            'message' => "Para leitoas, o intervalo entre cios deve ser entre {$intervaloCiosLeitoaMin} e {$intervaloCiosLeitoaMax} dias (atualmente: {$diasEntreCios} dias).",
                        ], 422);
                    }
                }
            }
        }

        if (! $exists) {
            DB::table('gestacao_cio')->insert([
                'femea_id' => (int) $validated['femea_id'],
                'data' => $data->toDateString(),
                'peso' => $validated['peso'] ?? null,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            // Registra o cio na tabela femea_movimento
            DB::table('femea_movimento')->insert([
                'femea_id' => (int) $validated['femea_id'],
                'femea_id_primaria' => $row->id_primaria,
                'acao' => 'cio',
                'data' => $data->toDateString(),
                'peso' => $validated['peso'] ?? null,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            // Atualizar o peso_atual da fêmea se informado
            if (isset($validated['peso']) && $validated['peso'] !== null) {
                $peso = floatval($validated['peso']);
                if ($peso > 0) {
                    DB::table('femea')->where('id', (int) $validated['femea_id'])->update([
                        'peso_atual' => $peso,
                        'atualizado_em' => now(),
                    ]);
                }
            }


        }

        return response()->json([
            'message' => 'Cio registrado com sucesso!',
        ], 201);
    }

    /**
     * Exclui um registro de cio pelo ID
     * Verifica se o registro existe antes de excluir
     * Retorna mensagem de sucesso ou erro
     * @param int $id ID do registro de cio
     */
    public function destroy(int $id)
    {
        if (! Schema::hasTable('gestacao_cio')) {
            return response()->json([
                'message' => 'Tabela gestacao_cio não existe no banco. Entre em contato com o suporte.',
            ], 422);
        }

        $exists = DB::table('gestacao_cio')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json([
                'message' => 'Registro não encontrado. Entre em contato com o suporte.',
            ], 404);
        }

        DB::table('gestacao_cio')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Registro excluído com sucesso!',
        ]);
    }
}
