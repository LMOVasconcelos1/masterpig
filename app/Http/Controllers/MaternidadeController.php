<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use App\Models\MaternidadeParto;
use App\Models\MaternidadeDesmame;
use App\Models\MaternidadeAdocao;
use App\Models\Femea;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MaternidadeController extends Controller
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

        return (int) $raw;
    }

    public function index()
    {
        $femeasLactantes = 0;
        $maesLeite = 0;
        $inconsistencias = [];

        if (Schema::hasTable('maternidade_parto')) {
            $cfg = [
                'lactacao_max_dias' => $this->metaInt('criterio_dias_lactacao_max', 28),
            ];

            // Fêmeas Lactantes: Partos sem desmame
            $femeasLactantes = DB::table('maternidade_parto as mp')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->whereNull('md.id')
                ->count();

            // Mães de Leite: Fêmeas que receberam leitões (parto_destino_id)
            $maesLeite = DB::table('maternidade_adocao')
                ->distinct('parto_destino_id')
                ->count();

            // Inconsistências: Leitões com idade superior a 101 dias
            $partosSemDesmame = DB::table('maternidade_parto as mp')
                ->join('femea as f', 'f.id', '=', 'mp.femea_id')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->whereNull('md.id')
                ->select([
                    'mp.id',
                    'mp.data as data_parto',
                    'mp.lote',
                    'f.id_primaria',
                    'f.id_secundaria',
                    'f.localizacao',
                    'f.baia'
                ])
                ->get();

            foreach ($partosSemDesmame as $parto) {
                $dataParto = Carbon::parse($parto->data_parto);
                $idadeLeitoes = (int) $dataParto->diffInDays(Carbon::today());
                $previsaoDesmame = (clone $dataParto)->addDays($cfg['lactacao_max_dias']);

                if ($idadeLeitoes > 101) {
                    $inconsistencias[] = [
                        'parto_id' => $parto->id,
                        'femea' => (string) $parto->id_primaria . ($parto->id_secundaria ? " ({$parto->id_secundaria})" : ""),
                        'lote' => $parto->lote ?? '-',
                        'localizacao' => (string) $parto->localizacao . ($parto->baia ? " - Baia: {$parto->baia}" : ""),
                        'idade_leitoes' => $idadeLeitoes,
                        'data_parto' => $dataParto->format('d/m/Y'),
                        'previsao_desmame' => $previsaoDesmame->format('d/m/Y'),
                        'problema' => "Leitões com idade superior a 101 dias ({$idadeLeitoes} dias). Já deveriam ter sido desmamados."
                    ];
                }
            }
        }

        // Matrizes aptas para parto: 
        // 1. Tem registro de cobertura (manejo) OU no cadastro (data_cobertura)
        // 2. Não tem parto registrado APÓS essa última cobertura
        $matrizesAptas = [];
        if (Schema::hasTable('femea')) {
            $queryAptas = DB::table('femea as f')
                // Última cobertura (unificada: manejo ou cadastro)
                ->leftJoin(DB::raw('(SELECT femea_id, MAX(data) as d, MAX(id) as last_id FROM gestacao_cobertura GROUP BY femea_id) as last_gc'), 'f.id', '=', 'last_gc.femea_id')
                ->where(function($q) {
                    $q->whereNotNull('last_gc.d')
                      ->orWhereNotNull('f.data_cobertura');
                })
                // Filtra fêmeas ativas (sem morte/descarte)
                ->whereNotExists(function ($q) {
                    if (Schema::hasTable('femea_movimento')) {
                        $q->select(DB::raw(1))
                          ->from('femea_movimento as fm')
                          ->whereColumn('fm.femea_id', 'f.id')
                          ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
                    } else {
                        $q->select(DB::raw(0))->whereRaw('1=0');
                    }
                });

            $rowsAptas = $queryAptas->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.data_cobertura as cadastrada_em',
                'last_gc.d as manejo_em',
                'last_gc.last_id as cobertura_id'
            ])->get();

            $gestacaoDias = $this->metaInt('meta_gestacao_periodo_gestacao', 114);

            foreach ($rowsAptas as $ra) {
                $dataC = $ra->manejo_em ?: $ra->cadastrada_em;
                if (!$dataC) continue;

                $dataC = Carbon::parse($dataC);

                // Verifica se existe parto após esta cobertura
                $hasParto = DB::table('maternidade_parto')
                    ->where('femea_id', $ra->id)
                    ->where('data', '>=', $dataC->toDateString())
                    ->exists();

                if (!$hasParto) {
                    $previsaoParto = (clone $dataC)->addDays($gestacaoDias);
                    $matrizesAptas[] = [
                        'id' => $ra->id,
                        'id_primaria' => (string) $ra->id_primaria,
                        'id_secundaria' => $ra->id_secundaria === null ? null : (string) $ra->id_secundaria,
                        'identificacao' => (string) $ra->id_primaria . ($ra->id_secundaria ? " ({$ra->id_secundaria})" : ""),
                        'previsao_parto' => $previsaoParto->toDateString(),
                        'cobertura_id' => $ra->cobertura_id
                    ];
                }
            }
        }

        $partosRegistrados = [];
        if (Schema::hasTable('maternidade_parto')) {
            $partosRegistrados = DB::table('maternidade_parto as mp')
                ->join('femea as f', 'f.id', '=', 'mp.femea_id')
                ->select([
                    'mp.*',
                    'f.id_primaria',
                    'f.id_secundaria',
                ])
                ->orderByDesc('mp.data')
                ->orderByDesc('mp.id')
                ->limit(100)
                ->get();
        }

        $morteCausas = [];
        if (Schema::hasTable('maternidade_morte_leitao_causas')) {
            $morteCausas = DB::table('maternidade_morte_leitao_causas')->orderBy('nome')->get();
        }

        $mortesLeitaoRegistradas = [];
        if (Schema::hasTable('maternidade_morte_leitao')) {
            $mortesLeitaoRegistradas = DB::table('maternidade_morte_leitao as mml')
                ->join('femea as f', 'f.id', '=', 'mml.femea_id')
                ->leftJoin('causa as c', 'c.id', '=', 'mml.causa_id')
                ->select([
                    'mml.*',
                    'f.id_primaria',
                    'f.id_secundaria',
                    'c.nome as causa_nome'
                ])
                ->orderByDesc('mml.data')
                ->limit(100)
                ->get();
        }

        $lotesCreche = [];
        if (Schema::hasTable('creche_lotes')) {
            $lotesCreche = DB::table('creche_lotes')
                ->where('situacao', 'aberto')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
                ->all();
        }

        $usuarios = [];
        if (Schema::hasTable('usuario')) {
            $usuarios = DB::table('usuario')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->nome])
                ->all();
        }

        $desmamesRegistrados = [];
        if (Schema::hasTable('maternidade_desmame')) {
            $desmamesRegistrados = DB::table('maternidade_desmame as md')
                ->join('maternidade_parto as mp', 'mp.id', '=', 'md.parto_id')
                ->join('femea as f', 'f.id', '=', 'mp.femea_id')
                ->select([
                    'md.*',
                    'mp.data as data_parto',
                    'mp.lote as lote_parto',
                    'mp.total_vivos',
                    'f.id_primaria',
                    'f.id_secundaria',
                ])
                ->orderByDesc('md.data')
                ->orderByDesc('md.id')
                ->limit(100)
                ->get();
        }

        // Fêmeas com leitões disponíveis (Lactantes)
        $femeasLactantesFull = [];
        if (Schema::hasTable('maternidade_parto')) {
            $mortesByParto = collect();
            if (Schema::hasTable('maternidade_morte_leitao')) {
                $mortesByParto = DB::table('maternidade_morte_leitao')
                    ->selectRaw('parto_id, COALESCE(SUM(quantidade), 0) as total')
                    ->groupBy('parto_id')
                    ->pluck('total', 'parto_id');
            }

            $recebidosByParto = collect();
            $doadosByParto = collect();
            if (Schema::hasTable('maternidade_adocao')) {
                $recebidosByParto = DB::table('maternidade_adocao')
                    ->selectRaw('parto_destino_id as parto_id, COALESCE(SUM(quantidade), 0) as total')
                    ->groupBy('parto_destino_id')
                    ->pluck('total', 'parto_id');
                $doadosByParto = DB::table('maternidade_adocao')
                    ->selectRaw('parto_origem_id as parto_id, COALESCE(SUM(quantidade), 0) as total')
                    ->groupBy('parto_origem_id')
                    ->pluck('total', 'parto_id');
            }

            $femeasLactantesFull = DB::table('maternidade_parto as mp')
                ->join('femea as f', 'f.id', '=', 'mp.femea_id')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->whereNull('md.id')
                ->select([
                    'f.id',
                    'f.id_primaria',
                    'f.id_secundaria',
                    'mp.id as parto_id',
                    'mp.data as parto_data',
                    'mp.lote as parto_lote',
                    'mp.total_vivos',
                ])
                ->get()
                ->map(function ($row) use ($mortesByParto, $recebidosByParto, $doadosByParto) {
                    $mortes = (int) ($mortesByParto[$row->parto_id] ?? 0);
                    $recebidos = (int) ($recebidosByParto[$row->parto_id] ?? 0);
                    $doados = (int) ($doadosByParto[$row->parto_id] ?? 0);

                    $row->mortes = $mortes;
                    $row->recebidos = $recebidos;
                    $row->doados = $doados;
                    $row->disponiveis = max(0, (int) $row->total_vivos + $recebidos - $doados - $mortes);
                    $row->identificacao = (string) $row->id_primaria . ($row->id_secundaria ? " ({$row->id_secundaria})" : "");
                    return $row;
                });
        }

        return view('admin.maternidade.index', compact(
            'femeasLactantes', 
            'maesLeite', 
            'inconsistencias', 
            'matrizesAptas', 
            'partosRegistrados',
            'morteCausas',
            'mortesLeitaoRegistradas',
            'femeasLactantesFull',
            'desmamesRegistrados',
            'lotesCreche',
            'usuarios'
        ));
    }

    public function storeParto(Request $request)
    {
        $validated = $request->validate([
            'femea_id' => 'required|exists:femea,id',
            'lote' => 'nullable|string|max:50',
            'data' => 'required|date',
            'hora_inicio' => 'nullable',
            'hora_termino' => 'nullable',
            'total_vivos' => 'required|integer|min:0',
            'total_mortos' => 'required|integer|min:0',
            'total_mumificados' => 'required|integer|min:0',
            'observacao' => 'nullable|string',
        ]);

        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($validated) {
                // Tentar encontrar a última cobertura para vincular
                $cobertura = DB::table('gestacao_cobertura')
                    ->where('femea_id', $validated['femea_id'])
                    ->orderByDesc('data')
                    ->first();

                $parto = MaternidadeParto::create([
                    'femea_id' => $validated['femea_id'],
                    'cobertura_id' => $cobertura?->id,
                    'lote' => $validated['lote'] ?? null,
                    'data' => $validated['data'],
                    'hora_inicio' => $validated['hora_inicio'] ?? null,
                    'hora_termino' => $validated['hora_termino'] ?? null,
                    'total_vivos' => $validated['total_vivos'],
                    'total_mortos' => $validated['total_mortos'],
                    'total_mumificados' => $validated['total_mumificados'],
                    'observacao' => $validated['observacao'],
                ]);

                if (! Schema::hasTable('femea_movimento')) {
                    return;
                }

                $femea = DB::table('femea')
                    ->where('id', (int) $validated['femea_id'])
                    ->select(['id', 'id_primaria'])
                    ->first();

                if (! $femea) {
                    return;
                }

                $data = Carbon::parse($validated['data'])->startOfDay()->toDateString();
                $now = now();
                $mov = [
                    'femea_id' => (int) $validated['femea_id'],
                    'acao' => 'parto',
                    'data' => $data,
                    'valor' => null,
                    'peso' => null,
                    'fornecedor_id' => null,
                    'observacoes' => 'Parto maternidade #'.(int) $parto->id,
                ];

                if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                    $mov['femea_id_primaria'] = (string) $femea->id_primaria;
                }
                if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                    $mov['criado_em'] = $now;
                }
                if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                    $mov['atualizado_em'] = $now;
                }

                DB::table('femea_movimento')->insert($mov);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return redirect()->back()->withErrors([
                    'movimento' => 'Para registrar o histórico no plantel, é necessário atualizar o ENUM de `femea_movimento.acao`.',
                    'sql' => $sqlEnum,
                ]);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Parto registrado com sucesso!');
    }

    public function storeMorteLeitao(Request $request)
    {
        $validated = $request->validate([
            'femea_id' => 'required|exists:femea,id',
            'parto_id' => 'required',
            'data' => 'required|date',
            'hora' => 'nullable',
            'quantidade' => 'required|integer|min:1',
            'causa_id' => 'nullable|exists:causa,id',
            'funcionario' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
        ]);

        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($validated) {
                $morteId = DB::table('maternidade_morte_leitao')->insertGetId([
                    'femea_id' => $validated['femea_id'],
                    'parto_id' => $validated['parto_id'],
                    'data' => $validated['data'],
                    'hora' => $validated['hora'] ?? null,
                    'quantidade' => $validated['quantidade'],
                    'causa_id' => $validated['causa_id'],
                    'funcionario' => $validated['funcionario'],
                    'observacao' => $validated['observacao'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (! Schema::hasTable('femea_movimento')) {
                    return;
                }

                $femea = DB::table('femea')
                    ->where('id', (int) $validated['femea_id'])
                    ->select(['id', 'id_primaria'])
                    ->first();

                if (! $femea) {
                    return;
                }

                $data = Carbon::parse($validated['data'])->startOfDay()->toDateString();
                $now = now();
                $obs = 'Morte de leitão maternidade #'.(int) $morteId.' - Parto '.(int) $validated['parto_id'].' - Qtd '.(int) $validated['quantidade'];
                $mov = [
                    'femea_id' => (int) $validated['femea_id'],
                    'acao' => 'morte_leitao',
                    'data' => $data,
                    'valor' => null,
                    'peso' => null,
                    'fornecedor_id' => null,
                    'observacoes' => $obs,
                ];

                if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                    $mov['femea_id_primaria'] = (string) $femea->id_primaria;
                }
                if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                    $mov['criado_em'] = $now;
                }
                if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                    $mov['atualizado_em'] = $now;
                }

                DB::table('femea_movimento')->insert($mov);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return redirect()->back()->withErrors([
                    'movimento' => 'Para registrar o histórico no plantel, é necessário atualizar o ENUM de `femea_movimento.acao`.',
                    'sql' => $sqlEnum,
                ]);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Morte de leitão registrada com sucesso!');
    }

    public function storeCausa(Request $request)
    {
        $request->validate(['nome' => 'required|string|max:255']);

        // Tenta encontrar um grupo de causa para Maternidade
        $grupo = DB::table('grupo_causa')
            ->where('nome', 'like', '%Maternidade%')
            ->orWhere('nome', 'like', '%Mortalidade%')
            ->first();

        if (!$grupo) {
            $grupo = DB::table('grupo_causa')->first();
        }

        if (!$grupo) {
            return response()->json(['error' => 'Nenhum grupo de causa cadastrado no sistema.'], 422);
        }

        $id = DB::table('causa')->insertGetId([
            'nome' => $request->nome,
            'codigo' => 'MAT-' . strtoupper(uniqid()),
            'situacao' => 1,
            'grupo_causa_id' => $grupo->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'id' => $id,
            'nome' => $request->nome
        ]);
    }

    public function storeDesmame(Request $request)
    {
        $rules = [
            'parto_id' => [
                'required',
                'exists:maternidade_parto,id',
                Rule::unique('maternidade_desmame', 'parto_id'),
            ],
            'data_input' => 'required|string|max:20',
            'quantidade' => 'required|integer|min:1',
            'peso_medio' => 'nullable|numeric|min:0',
            'localizacao_destino' => 'nullable|string|max:80',
            'destino_matriz' => 'nullable|string|max:80',
            'baia_matriz' => 'nullable|string|max:80',
            'peso_matriz' => 'nullable|numeric|min:0',
            'escore_corporal' => 'nullable|string|max:30',
            'caracteristicas_desmame' => 'nullable|string|max:500',
            'funcionario' => 'nullable|string|max:255',
            'observacao' => 'nullable|string|max:500',
        ];

        if (Schema::hasTable('creche_lotes')) {
            $rules['lote_destino_id'] = ['required', 'integer', 'exists:creche_lotes,id'];
        } else {
            $rules['lote_destino'] = 'nullable|string|max:60';
        }

        $validated = $request->validate($rules, [
            'parto_id.unique' => 'Já existe um desmame registrado para este parto.',
        ]);

        $partoId = (int) $validated['parto_id'];
        $loteDestinoId = isset($validated['lote_destino_id']) ? (int) $validated['lote_destino_id'] : null;

        $parsedData = PigCycleService::parseFilterDate($validated['data_input']);
        if (!$parsedData) {
            return redirect()->back()->withErrors(['data_input' => 'Data de desmame inválida.']);
        }

        $partoRow = DB::table('maternidade_parto as mp')
            ->join('femea as f', 'f.id', '=', 'mp.femea_id')
            ->where('mp.id', $partoId)
            ->select(['mp.id', 'mp.data as data_parto', 'mp.femea_id', 'f.id_primaria', 'f.id_secundaria'])
            ->first();

        if (!$partoRow) {
            return redirect()->back()->withErrors(['parto_id' => 'Parto inválido.']);
        }

        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($validated, $loteDestinoId, $partoRow, $partoId, $parsedData) {
                $payload = [
                'parto_id' => $partoId,
                'data' => $parsedData->toDateString(),
                'quantidade' => (int) $validated['quantidade'],
                'peso_medio' => $validated['peso_medio'] ?? null,
                'observacao' => $validated['observacao'] ?? null,
                ];

                if ($loteDestinoId && Schema::hasTable('creche_lotes')) {
                    $nomeLote = DB::table('creche_lotes')->where('id', $loteDestinoId)->value('nome');
                    if (Schema::hasColumn('maternidade_desmame', 'lote_destino')) {
                        $payload['lote_destino'] = $nomeLote ? (string) $nomeLote : null;
                    }
                    if (Schema::hasColumn('maternidade_desmame', 'lote_destino_id')) {
                        $payload['lote_destino_id'] = $loteDestinoId;
                    }
                } else if (Schema::hasColumn('maternidade_desmame', 'lote_destino')) {
                    $payload['lote_destino'] = $validated['lote_destino'] ?? null;
                }

                $extra = [
                    'localizacao_destino' => $validated['localizacao_destino'] ?? null,
                    'destino_matriz' => $validated['destino_matriz'] ?? null,
                    'baia_matriz' => $validated['baia_matriz'] ?? null,
                    'peso_matriz' => $validated['peso_matriz'] ?? null,
                    'escore_corporal' => $validated['escore_corporal'] ?? null,
                    'caracteristicas_desmame' => $validated['caracteristicas_desmame'] ?? null,
                    'funcionario' => $validated['funcionario'] ?? null,
                ];

                foreach ($extra as $col => $value) {
                    if (Schema::hasColumn('maternidade_desmame', $col)) {
                        $payload[$col] = $value;
                    }
                }

                $desmame = MaternidadeDesmame::create($payload);

                if (Schema::hasTable('femea_movimento')) {
                    $data = $parsedData->copy()->startOfDay()->toDateString();
                    $now = now();
                    $obs = 'Desmame maternidade #'.(int) ($desmame->id ?? 0).' - Parto '.(int) $partoId.' - Qtd '.(int) $validated['quantidade'];
                    $mov = [
                        'femea_id' => (int) $partoRow->femea_id,
                        'acao' => 'desmame',
                        'data' => $data,
                        'valor' => null,
                        'peso' => $validated['peso_matriz'] ?? null,
                        'fornecedor_id' => null,
                        'observacoes' => $obs,
                    ];

                    if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                        $mov['femea_id_primaria'] = (string) $partoRow->id_primaria;
                    }
                    if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                        $mov['criado_em'] = $now;
                    }
                    if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                        $mov['atualizado_em'] = $now;
                    }

                    DB::table('femea_movimento')->insert($mov);
                }

                if (!Schema::hasTable('creche_compras') || !Schema::hasTable('creche_lotes') || !$loteDestinoId) {
                    return;
                }

                $dataDesmame = $parsedData->copy()->startOfDay()->format('Y-m-d');
                $dataNascimento = Carbon::parse($partoRow->data_parto)->startOfDay()->format('Y-m-d');

                $qtd = (int) $validated['quantidade'];
                $pesoMedio = $validated['peso_medio'] !== null ? (float) $validated['peso_medio'] : null;
                $pesoTotal = $pesoMedio !== null ? round($pesoMedio * $qtd, 2) : 0.0;

                $ident = (string) $partoRow->id_primaria . ($partoRow->id_secundaria ? " ({$partoRow->id_secundaria})" : '');
                $nota = "DESMAME MATERNIDADE - Parto {$partoId} - {$ident}";

                DB::table('creche_compras')->insert([
                    'data_compra' => $dataDesmame,
                    'lote_id' => $loteDestinoId,
                    'localizacao' => $validated['localizacao_destino'] ?? null,
                    'quantidade' => $qtd,
                    'peso_total' => $pesoTotal,
                    'data_nascimento' => $dataNascimento,
                    'valor_compra' => null,
                    'fornecedor_id' => null,
                    'nota_fiscal' => mb_substr($nota, 0, 120),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return redirect()->back()->withErrors([
                    'movimento' => 'Para registrar o histórico no plantel, é necessário atualizar o ENUM de `femea_movimento.acao`.',
                    'sql' => $sqlEnum,
                ]);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Desmame registrado com sucesso!');
    }
}
