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

        // Matrizes aptas para parto (que tiveram cobertura mas não tiveram parto para essa cobertura)
        $matrizesAptas = [];
        if (Schema::hasTable('gestacao_cobertura')) {
            $matrizesAptas = DB::table('femea as f')
                ->join('gestacao_cobertura as gc', 'f.id', '=', 'gc.femea_id')
                ->leftJoin('maternidade_parto as mp', 'gc.id', '=', 'mp.cobertura_id')
                ->whereNull('mp.id')
                ->select([
                    'f.id',
                    'f.id_primaria',
                    'f.id_secundaria',
                    'gc.data as data_cobertura',
                    'gc.id as cobertura_id'
                ])
                ->get()
                ->map(function($row) {
                    $gestacaoDias = $this->metaInt('criterio_dias_gestacao', 114);
                    $previsaoParto = Carbon::parse($row->data_cobertura)->addDays($gestacaoDias);
                    return [
                        'id' => $row->id,
                        'identificacao' => (string) $row->id_primaria . ($row->id_secundaria ? " ({$row->id_secundaria})" : ""),
                        'previsao_parto' => $previsaoParto->toDateString(),
                        'cobertura_id' => $row->cobertura_id
                    ];
                });
        }

        return view('admin.maternidade.index', compact('femeasLactantes', 'maesLeite', 'inconsistencias', 'matrizesAptas'));
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
