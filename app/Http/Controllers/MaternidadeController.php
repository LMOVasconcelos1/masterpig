<?php

namespace App\Http\Controllers;

use App\Models\MaternidadeParto;
use App\Models\MaternidadeDesmame;
use App\Models\MaternidadeAdocao;
use App\Models\Femea;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Fêmeas com leitões disponíveis (Lactantes)
        $femeasLactantesFull = [];
        if (Schema::hasTable('maternidade_parto')) {
            $femeasLactantesFull = DB::table('maternidade_parto as mp')
                ->join('femea as f', 'f.id', '=', 'mp.femea_id')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->whereNull('md.id')
                ->select([
                    'f.id',
                    'f.id_primaria',
                    'f.id_secundaria',
                    'mp.id as parto_id',
                    'mp.total_vivos'
                ])
                ->get()
                ->map(function ($row) {
                    // Subtrair mortes registradas
                    $mortes = 0;
                    if (Schema::hasTable('maternidade_morte_leitao')) {
                        $mortes = DB::table('maternidade_morte_leitao')
                            ->where('parto_id', $row->parto_id)
                            ->sum('quantidade');
                    }
                    
                    $row->disponiveis = max(0, $row->total_vivos - $mortes);
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
            'femeasLactantesFull'
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

        DB::table('maternidade_morte_leitao')->insert([
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
        $validated = $request->validate([
            'parto_id' => 'required|exists:maternidade_parto,id',
            'data' => 'required|date',
            'quantidade' => 'required|integer|min:1',
            'peso_medio' => 'nullable|numeric|min:0',
            'observacao' => 'nullable|string',
        ]);

        MaternidadeDesmame::create($validated);

        return redirect()->back()->with('success', 'Desmame registrado com sucesso!');
    }
}
