<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoAnaliseController extends Controller
{
    /** @var array<string, int|null> */
    private array $metaCache = [];

    private const PERDA_LABELS = [
        'aborto' => 'Aborto',
        'repeticao_cio' => 'Repetição de cio',
        'falsa_prenhez' => 'Falsa prenhez',
    ];

    public function index(Request $request)
    {
        $hoje = Carbon::today();
        $gestacaoDias = max(1, $this->metaInt('meta_gestacao_periodo_gestacao', $this->metaInt('criterio_dias_gestacao', 114)));

        $inicio = PigCycleService::parseFilterDate((string) $request->query('inicio', '')) ?: $hoje->copy()->subDays(89);
        $fim = PigCycleService::parseFilterDate((string) $request->query('fim', '')) ?: $hoje->copy();

        if ($inicio->gt($fim)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $inicioIso = $inicio->toDateString();
        $fimIso = $fim->toDateString();
        $diasPrev = max(1, min(180, (int) $request->query('dias_prev', 30)));

        $hasCobertura = Schema::hasTable('gestacao_cobertura');
        $hasPerda = Schema::hasTable('gestacao_perda');
        $hasParto = Schema::hasTable('maternidade_parto');
        $hasFemea = Schema::hasTable('femea');

        // ---------------------------------------------------------------
        // Coberturas do período (base para quase todos os indicadores)
        // ---------------------------------------------------------------
        $coberturas = collect();
        if ($hasCobertura && $hasFemea) {
            $coberturas = $this->carregarCoberturas($inicioIso, $fimIso);
        }

        // ---------------------------------------------------------------
        // Histórico completo (para estado atual das gestantes)
        // ---------------------------------------------------------------
        $coberturasPorFemea = collect();
        if ($hasCobertura) {
            $coberturasPorFemea = DB::table('gestacao_cobertura')
                ->select(['femea_id', 'data'])
                ->get()
                ->groupBy('femea_id')
                ->map(fn ($g) => $g->pluck('data')->filter()->map(fn ($d) => (string) $d)->values()->all());
        }

        $partosPorFemea = collect();
        $partosPorCobertura = [];
        $partosPeriodo = collect();
        if ($hasParto) {
            $partos = DB::table('maternidade_parto')
                ->select(['id', 'femea_id', 'cobertura_id', 'data', 'total_vivos', 'total_mortos', 'total_mumificados'])
                ->get();

            $partosPorFemea = $partos->groupBy('femea_id')
                ->map(fn ($g) => $g->map(fn ($p) => ['data' => (string) $p->data, 'cobertura_id' => $p->cobertura_id === null ? null : (int) $p->cobertura_id])->values()->all());

            foreach ($partos as $p) {
                if ($p->cobertura_id !== null) {
                    $partosPorCobertura[(int) $p->cobertura_id] = true;
                }
            }

            $partosPeriodo = $partos->filter(fn ($p) => $p->data >= $inicioIso && $p->data <= $fimIso)->values();
        }

        $perdasAll = collect();
        if ($hasPerda) {
            $perdasAll = DB::table('gestacao_perda')
                ->select(['id', 'femea_id', 'tipo', 'data', 'hora', 'localizacao', 'baia', 'observacoes'])
                ->get();
        }
        $perdasPeriodo = $perdasAll->filter(fn ($p) => $p->data >= $inicioIso && $p->data <= $fimIso)->values();

        $perdasPorFemea = $perdasAll->groupBy('femea_id')
            ->map(fn ($g) => $g->map(fn ($p) => ['data' => (string) $p->data, 'tipo' => (string) $p->tipo])->values()->all());

        // Fêmeas inativas (morte/descarte/venda)
        $inativas = [];
        if ($hasFemea && Schema::hasTable('femea_movimento')) {
            $inativas = DB::table('femea_movimento')
                ->whereIn('acao', ['morte', 'descarte', 'venda'])
                ->distinct()
                ->pluck('femea_id')
                ->map(fn ($v) => (int) $v)
                ->flip()
                ->all();
        }

        // ---------------------------------------------------------------
        // Mapa de cobertura
        // ---------------------------------------------------------------
        $matrizesCobertasPeriodo = $coberturas->pluck('femea_id')->unique()->count();
        $perdasPorTipo = $this->contarPerdasPorTipo($perdasPeriodo);

        $gestantes = $this->montarGestantes($coberturasPorFemea, $partosPorFemea, $perdasPorFemea, $inativas, $gestacaoDias, $hoje, $diasPrev);
        $gestantesAtivas = collect($gestantes)->where('ativo', true)->values();

        $partosVinculadosPeriodo = $coberturas->filter(fn ($c) => $this->coberturaTeveParto($c, $partosPorCobertura, $partosPorFemea, $gestacaoDias))->count();

        $coberturasCount = $coberturas->count();
        $partosCount = $partosPeriodo->count();
        $perdasCount = $perdasPeriodo->count();

        $mapa = [
            'coberturas' => $coberturasCount,
            'matrizes_cobertas' => $matrizesCobertasPeriodo,
            'gestantes_ativas' => $gestantesAtivas->count(),
            'partos_previstos' => $gestantesAtivas->where('status', '!=', 'atrasada')->count(),
            'partos_previstos_janela' => $gestantesAtivas->where('status', 'proxima')->count(),
            'matrizes_em_atraso' => $gestantesAtivas->where('status', 'atrasada')->count(),
            'partos_registrados' => $partosCount,
            'taxa_parição' => $coberturasCount > 0 ? round($partosCount / $coberturasCount * 100, 1) : null,
            'perdas_total' => $perdasCount,
            'taxa_perda' => $coberturasCount > 0 ? round($perdasCount / $coberturasCount * 100, 1) : null,
            'perdas_por_tipo' => $perdasPorTipo,
            'dias_prev' => $diasPrev,
        ];

        // ---------------------------------------------------------------
        // Desempenho da gestação
        // ---------------------------------------------------------------
        $resolvidas = $coberturas->filter(function ($c) use ($gestacaoDias, $hoje) {
            return Carbon::parse($c['data'])->addDays($gestacaoDias)->lte($hoje);
        });
        $resolvidasCount = $resolvidas->count();
        $conceberam = $resolvidas->filter(fn ($c) => $this->coberturaTeveParto($c, $partosPorCobertura, $partosPorFemea, $gestacaoDias))->count();

        $intervalos = [];
        if ($hasParto && $coberturasCount > 0) {
            foreach ($coberturas as $c) {
                $parto = $this->buscarPartoDaCobertura($c, $partosPorCobertura, $partosPorFemea, $gestacaoDias);
                if ($parto) {
                    $intervalos[] = Carbon::parse($c['data'])->diffInDays(Carbon::parse($parto), false);
                }
            }
        }
        $intervaloMedio = count($intervalos) > 0 ? round(array_sum($intervalos) / count($intervalos), 1) : null;

        $leitoesVivos = $partosPeriodo->sum(fn ($p) => (int) $p->total_vivos);
        $mediaLeitoes = $partosCount > 0 ? round($leitoesVivos / $partosCount, 1) : null;

        $indicadores = [
            ['chave' => 'coberturas', 'label' => 'Coberturas no período', 'valor' => $coberturasCount, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'matrizes', 'label' => 'Matrizes cobertas', 'valor' => $matrizesCobertasPeriodo, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'partos', 'label' => 'Partos registrados', 'valor' => $partosCount, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'taxa_parição', 'label' => 'Taxa de parição', 'valor' => $mapa['taxa_parição'], 'unidade' => '%', 'serie' => 'percentual'],
            ['chave' => 'taxa_concepcao', 'label' => 'Taxa de concepção', 'valor' => $resolvidasCount > 0 ? round($conceberam / $resolvidasCount * 100, 1) : null, 'unidade' => '%', 'serie' => 'percentual'],
            ['chave' => 'perdas', 'label' => 'Perdas reprodutivas', 'valor' => $perdasCount, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'taxa_perda', 'label' => 'Taxa de perda', 'valor' => $mapa['taxa_perda'], 'unidade' => '%', 'serie' => 'percentual'],
            ['chave' => 'aborto', 'label' => 'Abortos', 'valor' => $perdasPorTipo['aborto']['total'] ?? 0, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'repeticao_cio', 'label' => 'Repetições de cio', 'valor' => $perdasPorTipo['repeticao_cio']['total'] ?? 0, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'falsa_prenhez', 'label' => 'Falsas prenhezes', 'valor' => $perdasPorTipo['falsa_prenhez']['total'] ?? 0, 'unidade' => '', 'serie' => 'volume'],
            ['chave' => 'intervalo_parto', 'label' => 'Intervalo médio cob.→parto', 'valor' => $intervaloMedio, 'unidade' => 'dias', 'serie' => 'tempo'],
            ['chave' => 'leitoes', 'label' => 'Média de leitões vivos/parto', 'valor' => $mediaLeitoes, 'unidade' => '', 'serie' => 'volume'],
        ];

        $serieMensal = $this->serieMensal($fim, $coberturas, $partosPeriodo, $perdasPeriodo);

        // ---------------------------------------------------------------
        // Perdas reprodutivas (lista + distribuição)
        // ---------------------------------------------------------------
        $perdasPorTipoLista = [];
        foreach (self::PERDA_LABELS as $chave => $label) {
            $qtd = (int) ($perdasPorTipo[$chave]['total'] ?? 0);
            $perdasPorTipoLista[] = [
                'tipo' => $chave,
                'label' => $label,
                'total' => $qtd,
                'percentual' => $perdasCount > 0 ? round($qtd / $perdasCount * 100, 1) : 0,
            ];
        }

        $perdasItems = $this->montarPerdasItems($perdasPeriodo, $coberturasPorFemea);

        // ---------------------------------------------------------------
        // Análise de coberturas
        // ---------------------------------------------------------------
        $porMacho = $this->analisePorMacho($coberturas, $partosPorCobertura, $partosPorFemea, $gestacaoDias);
        $porDiaSemana = $this->analisePorDiaSemana($coberturas);
        $porUsuario = $this->analisePorUsuario($coberturas);
        $presencaCio = $this->analisePresencaCio($coberturas);

        $insights = $this->montarInsights($mapa, $perdasPorTipo, $porMacho, $coberturasCount, $perdasCount, $presencaCio, $gestantesAtivas->count(), $resolvidasCount, $conceberam);

        // ---------------------------------------------------------------
        // Matrizes cobertas (lista com previsão de parto)
        // ---------------------------------------------------------------
        $matrizesCobertas = $this->montarListaGestantes($gestantesAtivas);

        return response()->json([
            'periodo' => [
                'inicio' => $inicioIso,
                'fim' => $fimIso,
                'inicio_br' => $inicio->format('d/m/Y'),
                'fim_br' => $fim->format('d/m/Y'),
                'gestacao_dias' => $gestacaoDias,
            ],
            'mapa' => $mapa,
            'matrizes_cobertas' => $matrizesCobertas,
            'desempenho' => [
                'indicadores' => $indicadores,
                'perdas_por_tipo' => $perdasPorTipoLista,
                'serie_mensal' => $serieMensal,
            ],
            'perdas' => [
                'total' => $perdasCount,
                'por_tipo' => $perdasPorTipoLista,
                'items' => $perdasItems,
            ],
            'analise' => [
                'por_macho' => $porMacho,
                'por_dia_semana' => $porDiaSemana,
                'por_usuario' => $porUsuario,
                'presenca_cio' => $presencaCio,
                'insights' => $insights,
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function carregarCoberturas(string $inicioIso, string $fimIso)
    {
        $hasUsuario = Schema::hasColumn('gestacao_cobertura', 'usuario_id') && Schema::hasTable('usuario');
        $temSemen = Schema::hasColumn('gestacao_cobertura', 'semen');
        $temCio = Schema::hasColumn('gestacao_cobertura', 'presenca_cio');

        $rows = DB::table('gestacao_cobertura as gc')
            ->join('femea as f', 'f.id', '=', 'gc.femea_id')
            ->leftJoin('macho as m', 'm.id', '=', 'gc.macho_id')
            ->when($hasUsuario, fn ($q) => $q->leftJoin('usuario as u', 'u.id', '=', 'gc.usuario_id'))
            ->whereBetween('gc.data', [$inicioIso, $fimIso])
            ->orderBy('gc.data')
            ->orderBy('gc.id')
            ->get(array_filter([
                'gc.id',
                'gc.femea_id',
                'gc.data',
                'gc.macho_id',
                'f.id_primaria',
                'f.id_secundaria',
                'm.id_primaria as macho_primaria',
                $temSemen ? 'gc.semen' : DB::raw('NULL as semen'),
                $temCio ? 'gc.presenca_cio' : DB::raw('NULL as presenca_cio'),
                $hasUsuario ? 'u.nome as usuario_nome' : DB::raw('NULL as usuario_nome'),
            ]));

        return $rows->map(function ($r) {
            $machoRef = $r->macho_primaria === null ? null : 'M-'.$r->macho_primaria;
            $semen = $r->semen === null ? null : trim((string) $r->semen);
            $ref = $machoRef ?: ($semen !== null && $semen !== '' ? 'S-'.$semen : null);

            return [
                'id' => (int) $r->id,
                'femea_id' => (int) $r->femea_id,
                'matriz' => $r->id_primaria === null ? '-' : (string) $r->id_primaria,
                'matriz_secundaria' => $r->id_secundaria === null ? null : (string) $r->id_secundaria,
                'data' => (string) $r->data,
                'presenca_cio' => $r->presenca_cio === null ? null : (string) $r->presenca_cio,
                'usuario' => $r->usuario_nome === null ? null : (string) $r->usuario_nome,
                'ref' => $ref,
            ];
        })->values();
    }

    private function contarPerdasPorTipo($perdas): array
    {
        $out = [];
        foreach (self::PERDA_LABELS as $chave => $label) {
            $out[$chave] = ['tipo' => $chave, 'label' => $label, 'total' => 0];
        }
        foreach ($perdas as $p) {
            $tipo = (string) $p->tipo;
            if (! isset($out[$tipo])) {
                $out[$tipo] = ['tipo' => $tipo, 'label' => $tipo, 'total' => 0];
            }
            $out[$tipo]['total']++;
        }

        return $out;
    }

    private function coberturaTeveParto(array $c, array $partosPorCobertura, $partosPorFemea, int $gestacaoDias): bool
    {
        if (isset($partosPorCobertura[$c['id']])) {
            return true;
        }

        return $this->buscarPartoDaCobertura($c, $partosPorCobertura, $partosPorFemea, $gestacaoDias) !== null;
    }

    private function buscarPartoDaCobertura(array $c, array $partosPorCobertura, $partosPorFemea, int $gestacaoDias): ?string
    {
        $lista = $partosPorFemea[$c['femea_id']] ?? [];
        $min = Carbon::parse($c['data'])->addDays($gestacaoDias - 30)->toDateString();
        $max = Carbon::parse($c['data'])->addDays($gestacaoDias + 21)->toDateString();

        foreach ($lista as $p) {
            if ($p['cobertura_id'] === $c['id']) {
                return $p['data'];
            }
            if ($p['data'] >= $min && $p['data'] <= $max) {
                return $p['data'];
            }
        }

        return null;
    }

    /**
     * Estado atual das matrizes (gestantes ativas).
     */
    private function montarGestantes($coberturasPorFemea, $partosPorFemea, $perdasPorFemea, array $inativas, int $gestacaoDias, Carbon $hoje, int $diasPrev): array
    {
        $out = [];

        foreach ($coberturasPorFemea as $femeaId => $datas) {
            $femeaId = (int) $femeaId;
            if (empty($datas)) {
                continue;
            }

            $datas = array_values($datas);
            sort($datas);
            $ultima = end($datas);
            if (! $ultima) {
                continue;
            }

            $partoDepois = collect($partosPorFemea[$femeaId] ?? [])->contains(fn ($p) => $p['data'] >= $ultima);
            $perdaDepois = collect($perdasPorFemea[$femeaId] ?? [])->contains(fn ($p) => $p['data'] >= $ultima);
            $ativo = ! isset($inativas[$femeaId]) && ! $partoDepois && ! $perdaDepois;

            $previsao = Carbon::parse($ultima)->addDays($gestacaoDias);
            $diasGestacao = Carbon::parse($ultima)->diffInDays($hoje, false);

            if (! $ativo) {
                continue;
            }

            if ($previsao->lt($hoje)) {
                $status = 'atrasada';
            } elseif ($previsao->lte($hoje->copy()->addDays($diasPrev))) {
                $status = 'proxima';
            } else {
                $status = 'no_prazo';
            }

            $out[] = [
                'ativo' => true,
                'femea_id' => $femeaId,
                'matriz' => null,
                'matriz_secundaria' => null,
                'data_cobertura' => (string) $ultima,
                'dias_gestacao' => max(0, (int) $diasGestacao),
                'previsao' => $previsao->toDateString(),
                'dias_para_parto' => (int) $hoje->diffInDays($previsao, false),
                'status' => $status,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $gestantes
     * @return array<int, array<string, mixed>>
     */
    private function montarListaGestantes($gestantes): array
    {
        if (empty($gestantes)) {
            return [];
        }

        $ids = array_column($gestantes, 'femea_id');
        $femeas = DB::table('femea')->whereIn('id', $ids)->pluck('id_primaria', 'id');
        $secundarias = DB::table('femea')->whereIn('id', $ids)->pluck('id_secundaria', 'id');

        $out = [];
        foreach ($gestantes as $g) {
            $fid = (int) $g['femea_id'];
            $out[] = [
                'matriz' => isset($femeas[$fid]) ? (string) $femeas[$fid] : '-',
                'matriz_secundaria' => isset($secundarias[$fid]) && $secundarias[$fid] !== null ? (string) $secundarias[$fid] : null,
                'data_cobertura' => PigCycleService::formatDisplayDate(Carbon::parse($g['data_cobertura'])),
                'data_cobertura_iso' => (string) $g['data_cobertura'],
                'dias_gestacao' => (int) $g['dias_gestacao'],
                'previsao' => PigCycleService::formatDisplayDate(Carbon::parse($g['previsao'])),
                'previsao_iso' => (string) $g['previsao'],
                'dias_para_parto' => (int) $g['dias_para_parto'],
                'status' => (string) $g['status'],
            ];
        }

        usort($out, fn ($a, $b) => strcmp((string) $a['previsao_iso'], (string) $b['previsao_iso']));

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $perdas
     * @return array<int, array<string, mixed>>
     */
    private function montarPerdasItems($perdas, $coberturasPorFemea): array
    {
        if ($perdas->isEmpty()) {
            return [];
        }

        $femeaIds = $perdas->pluck('femea_id')->unique()->values()->all();
        $femeas = DB::table('femea')->whereIn('id', $femeaIds)->pluck('id_primaria', 'id');
        $secundarias = DB::table('femea')->whereIn('id', $femeaIds)->pluck('id_secundaria', 'id');

        $out = [];
        foreach ($perdas as $p) {
            $fid = (int) $p->femea_id;
            $datas = $coberturasPorFemea[$fid] ?? [];
            $cobAnterior = null;
            foreach ($datas as $d) {
                if ($d <= (string) $p->data && ($cobAnterior === null || $d > $cobAnterior)) {
                    $cobAnterior = $d;
                }
            }

            $diasDesde = $cobAnterior ? Carbon::parse($cobAnterior)->diffInDays(Carbon::parse($p->data), false) : null;

            $out[] = [
                'matriz' => isset($femeas[$fid]) ? (string) $femeas[$fid] : '-',
                'matriz_secundaria' => isset($secundarias[$fid]) && $secundarias[$fid] !== null ? (string) $secundarias[$fid] : null,
                'tipo' => (string) $p->tipo,
                'tipo_label' => self::PERDA_LABELS[(string) $p->tipo] ?? (string) $p->tipo,
                'data' => PigCycleService::formatDisplayDate(Carbon::parse($p->data)),
                'data_iso' => (string) $p->data,
                'dias_desde_cobertura' => $diasDesde,
                'localizacao' => $p->localizacao === null ? null : (string) $p->localizacao,
                'baia' => $p->baia === null ? null : (string) $p->baia,
                'observacoes' => $p->observacoes === null ? null : (string) $p->observacoes,
            ];
        }

        usort($out, fn ($a, $b) => strcmp((string) $b['data_iso'], (string) $a['data_iso']));

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analisePorMacho($coberturas, array $partosPorCobertura, $partosPorFemea, int $gestacaoDias): array
    {
        if ($coberturas->isEmpty()) {
            return [];
        }

        $ids = $coberturas->pluck('id')->all();
        $porRef = [];

        $temMontas = Schema::hasTable('gestacao_cobertura_monta');
        if ($temMontas && ! empty($ids)) {
            $rows = DB::table('gestacao_cobertura_monta as gm')
                ->leftJoin('macho as m', 'm.id', '=', 'gm.macho_id')
                ->whereIn('gm.cobertura_id', $ids)
                ->get(['gm.cobertura_id', 'gm.tipo', 'gm.semen', 'm.id_primaria as macho_primaria']);

            foreach ($rows as $r) {
                $tipo = (string) $r->tipo;
                $ref = null;
                if ($tipo === 'macho' && $r->macho_primaria !== null) {
                    $ref = 'M-'.$r->macho_primaria;
                } elseif ($tipo === 'semen') {
                    $semen = $r->semen === null ? null : trim((string) $r->semen);
                    $ref = $semen !== null && $semen !== '' ? 'S-'.$semen : null;
                }
                if ($ref === null) {
                    continue;
                }
                $porRef[$ref][(int) $r->cobertura_id] = true;
            }
        }

        if (empty($porRef)) {
            foreach ($coberturas as $c) {
                if ($c['ref'] !== null) {
                    $porRef[$c['ref']][$c['id']] = true;
                }
            }
        }

        $coberturasById = $coberturas->keyBy('id');

        $out = [];
        foreach ($porRef as $ref => $cobIds) {
            $cobIds = array_keys($cobIds);
            $total = count($cobIds);
            $partos = 0;
            foreach ($cobIds as $cid) {
                $c = $coberturasById[$cid] ?? null;
                if ($c && $this->coberturaTeveParto($c, $partosPorCobertura, $partosPorFemea, $gestacaoDias)) {
                    $partos++;
                }
            }
            $out[] = [
                'ref' => (string) $ref,
                'coberturas' => $total,
                'partos' => $partos,
                'taxa' => $total > 0 ? round($partos / $total * 100, 1) : null,
            ];
        }

        usort($out, fn ($a, $b) => $b['coberturas'] <=> $a['coberturas']);

        return array_slice($out, 0, 15);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analisePorDiaSemana($coberturas): array
    {
        $labels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $contagem = array_fill(0, 7, 0);

        foreach ($coberturas as $c) {
            $contagem[Carbon::parse($c['data'])->dayOfWeek]++;
        }

        $out = [];
        foreach ($labels as $i => $label) {
            $out[] = ['label' => $label, 'coberturas' => $contagem[$i]];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analisePorUsuario($coberturas): array
    {
        $grupo = [];
        foreach ($coberturas as $c) {
            $nome = $c['usuario'] ?: 'Não informado';
            $grupo[$nome] = ($grupo[$nome] ?? 0) + 1;
        }

        $out = [];
        foreach ($grupo as $nome => $qtd) {
            $out[] = ['usuario' => $nome, 'coberturas' => $qtd];
        }

        usort($out, fn ($a, $b) => $b['coberturas'] <=> $a['coberturas']);

        return array_slice($out, 0, 15);
    }

    private function analisePresencaCio($coberturas): array
    {
        $sim = 0;
        $nao = 0;
        $sem = 0;
        foreach ($coberturas as $c) {
            $v = $c['presenca_cio'];
            if ($v === 'sim') {
                $sim++;
            } elseif ($v === 'nao') {
                $nao++;
            } else {
                $sem++;
            }
        }

        return ['sim' => $sim, 'nao' => $nao, 'sem_registro' => $sem, 'total' => count($coberturas)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serieMensal(Carbon $fim, $coberturas, $partosPeriodo, $perdasPeriodo): array
    {
        $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $porMes = function ($itens, callable $getData) {
            $mapa = [];
            foreach ($itens as $item) {
                $mes = substr((string) $getData($item), 0, 7);
                $mapa[$mes] = ($mapa[$mes] ?? 0) + 1;
            }

            return $mapa;
        };

        $coal = $porMes($coberturas, fn ($c) => $c['data']);
        $nasc = $porMes($partosPeriodo, fn ($p) => (string) $p->data);
        $perda = $porMes($perdasPeriodo, fn ($p) => (string) $p->data);

        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $fim->copy()->startOfMonth()->subMonths($i);
            $chave = $m->format('Y-m');
            $out[] = [
                'mes' => $meses[(int) $m->format('n') - 1].'/'.substr($m->format('Y'), 2),
                'coberturas' => $coal[$chave] ?? 0,
                'partos' => $nasc[$chave] ?? 0,
                'perdas' => $perda[$chave] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function montarInsights(array $mapa, array $perdasPorTipo, array $porMacho, int $coberturasCount, int $perdasCount, array $presencaCio, int $gestantesAtivas, int $resolvidas, int $conceberam): array
    {
        $insights = [];

        $taxaPerda = $mapa['taxa_perda'];
        if ($taxaPerda !== null && $taxaPerda >= 15) {
            $insights[] = [
                'nivel' => 'critico',
                'titulo' => 'Taxa de perda elevada',
                'descricao' => 'Taxa de perdas reprodutivas em '.$this->fmt($taxaPerda).'% (referência: abaixo de 10%).',
            ];
        } elseif ($taxaPerda !== null && $taxaPerda >= 10) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Perdas acima do ideal',
                'descricao' => 'Taxa de perdas em '.$this->fmt($taxaPerda).'%. Revisar diagnóstico de gestação e manejo.',
            ];
        }

        $repet = (int) ($perdasPorTipo['repeticao_cio']['total'] ?? 0);
        if ($coberturasCount > 0 && ($repet / $coberturasCount * 100) >= 10) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Alta repetição de cio',
                'descricao' => $repet.' repetições de cio no período. Verificar detecção de cio e momento da cobertura.',
            ];
        }

        $aborto = (int) ($perdasPorTipo['aborto']['total'] ?? 0);
        if ($coberturasCount > 0 && ($aborto / $coberturasCount * 100) >= 5) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Abortos relevantes',
                'descricao' => $aborto.' abortos registrados. Investigar sanidade e condições de manejo.',
            ];
        }

        $pior = null;
        foreach ($porMacho as $m) {
            if ($m['coberturas'] >= 5 && $m['taxa'] !== null && ($pior === null || $m['taxa'] < $pior['taxa'])) {
                $pior = $m;
            }
        }
        if ($pior !== null && $pior['taxa'] < 75) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Desempenho por reprodutor',
                'descricao' => 'O reprodutor "'.$pior['ref'].'" tem taxa de sucesso de '.$this->fmt($pior['taxa']).'%.',
            ];
        }

        if ($resolvidas > 0 && ($conceberam / $resolvidas * 100) < 85) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Taxa de concepção abaixo do ideal',
                'descricao' => 'Taxa de concepção em '.$this->fmt(round($conceberam / $resolvidas * 100, 1)).'% (referência: acima de 85%).',
            ];
        }

        if (($mapa['matrizes_em_atraso'] ?? 0) > 0) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Partos previstos em atraso',
                'descricao' => $mapa['matrizes_em_atraso'].' matriz(es) passaram da data prevista de parto sem registro de parto ou perda.',
            ];
        }

        $semCio = (int) ($presencaCio['sem_registro'] ?? 0);
        if ($semCio > 0) {
            $insights[] = [
                'nivel' => 'atencao',
                'titulo' => 'Coberturas sem cio registrado',
                'descricao' => $semCio.' cobertura(s) sem registro de presença de cio. Registrar o cio melhora os indicadores.',
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'nivel' => 'ok',
                'titulo' => 'Indicadores dentro do esperado',
                'descricao' => 'Nenhum ponto crítico identificado no período analisado.',
            ];
        }

        return $insights;
    }

    private function fmt($valor): string
    {
        return number_format((float) $valor, 1, ',', '.');
    }

    private function metaInt(string $key, int $default): int
    {
        if (array_key_exists($key, $this->metaCache)) {
            $cached = $this->metaCache[$key];

            return $cached === null ? $default : $cached;
        }

        try {
            if (! Schema::hasTable('meta')) {
                $this->metaCache[$key] = null;

                return $default;
            }
            $raw = DB::table('meta')->where('chave', $key)->value('valor');
            if ($raw === null || trim((string) $raw) === '') {
                $this->metaCache[$key] = null;

                return $default;
            }
            $valor = (int) $raw;
            $this->metaCache[$key] = $valor;

            return $valor;
        } catch (\Throwable) {
            $this->metaCache[$key] = null;

            return $default;
        }
    }
}
