<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FemeaCompraController extends Controller
{
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
     * Lista todas as compras de fêmeas registradas
     * Retorna dados paginados com filtros por busca, raça, fornecedor, localização, baia e período
     * Ordena pela data da compra em ordem decrescente
     */
    public function index(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas do plantel ainda não foram criadas no banco. Entre em contato com o nosso suporte',
            ]);
        }

        $limit = max(1, min(5000, (int) $request->query('limit', 200)));
        $page = max(1, (int) $request->query('page', 1));
        $search = $request->query('search', '');
        $racaId = $request->query('raca_id', '');
        $fornecedorId = $request->query('fornecedor_id', '');
        $localizacao = $request->query('localizacao', '');
        $baia = $request->query('baia', '');
        $dataInicial = $request->query('data_inicial', '');
        $dataFinal = $request->query('data_final', '');

        $query = DB::table('femea_movimento as fm')
            ->join('femea as f', 'f.id', '=', 'fm.femea_id')
            ->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')
            ->leftJoin('fornecedor as fo', 'fo.id', '=', 'f.fornecedor_id')
            ->where('fm.acao', 'compra')
            ->orderByDesc('fm.data')
            ->select([
                'fm.id',
                'fm.data',
                'f.tipo_compra',
                'f.id_primaria',
                'f.id_secundaria',
                'r.nome as raca_nome',
                'f.ciclos_ate_compra',
                'f.data_nascimento',
                'fo.nome as fornecedor_nome',
                'f.peso_compra',
                'f.peso_atual',
                'f.valor_compra',
                'f.raca_id',
                'f.fornecedor_id',
                'f.localizacao',
                'f.baia',
                'f.data_cobertura',
                'f.caracteristicas',
            ]);

        if ($request->filled('tipo_compra')) {
            $query->where('f.tipo_compra', $request->tipo_compra);
        }

        // Filtros adicionados
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('f.id_primaria', 'like', "%{$search}%")
                  ->orWhere('f.id_secundaria', 'like', "%{$search}%");
            });
        }

        if (!empty($racaId)) {
            $query->where('f.raca_id', $racaId);
        }

        if (!empty($dataInicial)) {
            // Converter de DD/MM/AAAA para AAAA-MM-DD
            $dataInicialFormatada = $this->converterDataBrParaIso($dataInicial);
            if ($dataInicialFormatada) {
                $query->whereDate('fm.data', '>=', $dataInicialFormatada);
            }
        }

        if (!empty($dataFinal)) {
            // Converter de DD/MM/AAAA para AAAA-MM-DD
            $dataFinalFormatada = $this->converterDataBrParaIso($dataFinal);
            if ($dataFinalFormatada) {
                $query->whereDate('fm.data', '<=', $dataFinalFormatada);
            }
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $rows = $query->offset($offset)->limit($limit)->get();

        $items = $rows->map(function ($row) {
            $idadeDias = null;
            if (! empty($row->data_nascimento)) {
                $idadeDias = Carbon::parse($row->data_nascimento)->diffInDays(Carbon::parse($row->data));
            }

            return [
                'id' => $row->id,
                'acao' => 'compra',
                'data' => Carbon::parse($row->data)->format('d/m/Y'),
                'tipo' => $row->tipo_compra,
                'id_primaria' => $row->id_primaria,
                'id_secundaria' => $row->id_secundaria,
                'raca' => $row->raca_nome,
                'ciclo' => $row->ciclos_ate_compra,
                'idade_dias' => $idadeDias,
                'fornecedor' => $row->fornecedor_nome,
                'peso_compra' => $row->peso_compra,
                'peso_atual' => $row->peso_atual,
                'valor' => $row->valor_compra,
                'raca_id' => $row->raca_id,
                'fornecedor_id' => $row->fornecedor_id,
                'localizacao' => $row->localizacao,
                'baia' => $row->baia,
                'data_cobertura' => $row->data_cobertura,
                'caracteristicas' => $row->caracteristicas,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'last_page' => (int) ceil($total / $limit)
        ]);
    }

    /**
     * Registra uma nova compra de fêmea no sistema
     * Valida todos os dados da compra e cria registro na tabela femea
     * Também registra o movimento na tabela femea_movimento
     * Se informado cio, registra também em gestacao_cio e femea_movimento com ação 'cio'
     */
    public function store(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
            ], 422);
        }

        $request->merge([
            'id_primaria' => trim((string) $request->input('id_primaria', '')),
            'id_secundaria' => $request->input('id_secundaria') === null ? null : trim((string) $request->input('id_secundaria')),
            'localizacao' => $request->input('localizacao') === null ? null : trim((string) $request->input('localizacao')),
            'baia' => $request->input('baia') === null ? null : trim((string) $request->input('baia')),
            'caracteristicas' => $request->input('caracteristicas') === null ? null : trim((string) $request->input('caracteristicas')),
        ]);

        // Converter datas que podem ser dias PIG antes da validação
        $this->convertPigDatesToIso($request);

        $validated = $request->validate([
            'tipo_compra' => ['required', 'in:leitoa,matriz_vazia,matriz_gestante'],
            'id_primaria' => ['required', 'string', 'max:50', 'unique:femea,id_primaria'],
            'id_secundaria' => ['nullable', 'string', 'max:50', 'unique:femea,id_secundaria'],
            'data_compra' => ['required', 'date'],
            'data_nascimento' => ['nullable', 'date', 'required_if:tipo_compra,leitoa'],
            'data_ultimo_cio' => ['nullable', 'date'],
            'houve_cio' => ['nullable', 'string', 'in:sim,nao'],
            'ciclos_ate_compra' => ['nullable', 'integer', 'min:0'],
            'data_cobertura' => ['nullable', 'date'],
            'raca_id' => ['nullable', 'exists:raca,id'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'peso_compra' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'exists:fornecedor,id'],
            'caracteristicas' => ['nullable', 'string'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
        ]);

        $dataCompra = Carbon::parse($validated['data_compra'])->startOfDay();
        $dataNasc = empty($validated['data_nascimento']) ? null : Carbon::parse($validated['data_nascimento'])->startOfDay();
        $dataCob = empty($validated['data_cobertura']) ? null : Carbon::parse($validated['data_cobertura'])->startOfDay();

        if ($validated['tipo_compra'] === 'leitoa') {
            if (! empty($validated['data_cobertura'])) {
                return response()->json([
                    'message' => 'Leitoa não deve ter data de cobertura na compra.',
                ], 422);
            }

            if ($validated['ciclos_ate_compra'] !== null) {
                return response()->json([
                    'message' => 'Leitoa não deve ter ciclos até a compra.',
                ], 422);
            }

            // Validar peso mínimo e máximo para leitoa
            if (isset($validated['peso_compra']) && $validated['peso_compra'] !== null) {
                $pesoMinimo = $this->metaInt('criterio_leitoa_peso_min', 0);
                $pesoMaximo = $this->metaInt('criterio_leitoa_peso_max', 999);
                
                if ($pesoMinimo > 0 && $validated['peso_compra'] < $pesoMinimo) {
                    return response()->json([
                        'message' => "O peso da leitoa deve ser no mínimo {$pesoMinimo}kg. Peso informado: {$validated['peso_compra']}kg.",
                    ], 422);
                }
                
                if ($pesoMaximo > 0 && $validated['peso_compra'] > $pesoMaximo) {
                    return response()->json([
                        'message' => "O peso da leitoa deve ser no máximo {$pesoMaximo}kg. Peso informado: {$validated['peso_compra']}kg.",
                    ], 422);
                }
            }

            // Validar idade mínima e máxima para leitoa
            if ($dataNasc) {
                $idadeDias = $dataNasc->diffInDays($dataCompra);
                $idadeMinima = $this->metaInt('criterio_leitoa_idade_min_dias', 0);
                $idadeMaxima = $this->metaInt('criterio_leitoa_idade_max_dias', 999);
                
                if ($idadeMinima > 0 && $idadeDias < $idadeMinima) {
                    return response()->json([
                        'message' => "A idade mínima para entrada de leitoa é de {$idadeMinima} dias. Idade atual: {$idadeDias} dias.",
                    ], 422);
                }
                
                if ($idadeMaxima > 0 && $idadeDias > $idadeMaxima) {
                    return response()->json([
                        'message' => "A idade máxima para entrada de leitoa é de {$idadeMaxima} dias. Idade atual: {$idadeDias} dias.",
                    ], 422);
                }
            }
        }

        if ($validated['tipo_compra'] === 'matriz_gestante' && empty($validated['data_cobertura'])) {
            return response()->json([
                'message' => 'Data de cobertura é obrigatória para matriz gestante.',
            ], 422);
        }

        if (($validated['tipo_compra'] === 'matriz_vazia' || $validated['tipo_compra'] === 'matriz_gestante') && $validated['ciclos_ate_compra'] === null) {
            return response()->json([
                'message' => 'Ciclos até a compra é obrigatório para matriz vazia e gestante.',
            ], 422);
        }

        if (empty($validated['data_nascimento']) && isset($validated['ciclos_ate_compra'])) {
            $dias = (int) $validated['ciclos_ate_compra'] * 21;
            $validated['data_nascimento'] = Carbon::parse($validated['data_compra'])->subDays($dias)->format('Y-m-d');
            $dataNasc = Carbon::parse($validated['data_nascimento'])->startOfDay();
        }

        if ($dataNasc && $dataNasc->gt($dataCompra)) {
            return response()->json([
                'message' => 'Data de nascimento não pode ser maior que a data de compra.',
            ], 422);
        }

        if ($validated['tipo_compra'] === 'matriz_gestante' && $dataCob) {
            if ($dataCob->gt($dataCompra)) {
                return response()->json([
                    'message' => 'Data de cobertura não pode ser maior que a data de compra para matriz gestante.',
                ], 422);
            }
            if ($dataNasc && $dataNasc->gt($dataCob)) {
                return response()->json([
                    'message' => 'Data de nascimento não pode ser maior que a data de cobertura.',
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($validated) {
            // Se informou peso_compra, define peso_atual igual ao peso_compra
            if (isset($validated['peso_compra']) && $validated['peso_compra'] !== null) {
                $validated['peso_atual'] = $validated['peso_compra'];
            }

            $femea = Femea::create($validated);
            //Salva a compra da femea na tabela femea_movimento
            DB::table('femea_movimento')->insert([
                'femea_id' => $femea->id,
                'femea_id_primaria' => $femea->id_primaria,
                'acao' => 'compra',
                'data' => $femea->data_compra,
                'valor' => $femea->valor_compra,
                'peso' => $femea->peso_compra,
                'fornecedor_id' => $femea->fornecedor_id,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            // Se informou que houve cio, registra na tabela gestacao_cio e femea_movimento
            if (($validated['houve_cio'] ?? 'nao') === 'sim' && ! empty($validated['data_ultimo_cio'])) {
                if (Schema::hasTable('gestacao_cio')) {
                    $payload = [
                        'femea_id' => $femea->id,
                        'data' => $validated['data_ultimo_cio'],
                    ];
                    if (Schema::hasColumn('gestacao_cio', 'observacao')) {
                        $payload['observacao'] = 'Registrado no ato da compra';
                    }
                    if (Schema::hasColumn('gestacao_cio', 'criado_em')) {
                        $payload['criado_em'] = now();
                    }
                    if (Schema::hasColumn('gestacao_cio', 'atualizado_em')) {
                        $payload['atualizado_em'] = now();
                    }

                    DB::table('gestacao_cio')->insert($payload);
                }

                // Registra o cio na tabela femea_movimento
                DB::table('femea_movimento')->insert([
                    'femea_id' => $femea->id,
                    'femea_id_primaria' => $femea->id_primaria,
                    'acao' => 'cio',
                    'data' => $validated['data_ultimo_cio'],
                    'criado_em' => now(),
                    'atualizado_em' => now(),
                ]);
            }

            return $femea;
        });

        return response()->json([
            'message' => 'Compra registrada com sucesso!',
            'id' => $result->id,
        ], 201);
    }

    /**
     * Converte datas que podem ser dias PIG para formato ISO antes da validação
     * @param Request $request
     */
    private function convertPigDatesToIso(Request $request)
    {
        $dateFields = ['data_compra', 'data_nascimento', 'data_cobertura', 'data_ultimo_cio'];
        
        foreach ($dateFields as $field) {
            $value = $request->input($field);
            if ($value && is_numeric($value)) {
                // É um dia PIG, converter para data ISO
                try {
                    $date = PigCycleService::fromPigDay((int) $value);
                    $request->merge([$field => $date->format('Y-m-d')]);
                } catch (\Exception $e) {
                    // Se falhar a conversão, mantém o valor original para que a validação falhe
                }
            }
        }
    }

    /**
     * Converte data do formato brasileiro (DD/MM/AAAA) para ISO (AAAA-MM-DD)
     * Utilizada nos filtros de data da listagem de compras
     * @param string $dataBr Data no formato brasileiro
     * @return string|null Data no formato ISO ou null se inválida
     */
    private function converterDataBrParaIso($dataBr)
    {
        $dataBr = trim($dataBr);
        if (empty($dataBr)) {
            return null;
        }

        // Se já estiver no formato ISO, retorna como está
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataBr)) {
            return $dataBr;
        }

        // Converte de DD/MM/AAAA para AAAA-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataBr, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        return null;
    }
}
