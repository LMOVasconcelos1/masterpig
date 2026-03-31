<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FemeaCompraController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
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
        }

        $dataCompra = Carbon::parse($validated['data_compra'])->startOfDay();
        $dataNasc = empty($validated['data_nascimento']) ? null : Carbon::parse($validated['data_nascimento'])->startOfDay();
        $dataCob = empty($validated['data_cobertura']) ? null : Carbon::parse($validated['data_cobertura'])->startOfDay();

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

            // Se informou que houve cio, registra na tabela gestacao_cio
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
            }

            return $femea;
        });

        return response()->json([
            'message' => 'Compra registrada com sucesso!',
            'id' => $result->id,
        ], 201);
    }

    /**
     * Converte data do formato brasileiro (DD/MM/AAAA) para ISO (AAAA-MM-DD)
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
