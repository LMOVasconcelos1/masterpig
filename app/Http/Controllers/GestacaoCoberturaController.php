<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\PigCycleService;

class GestacaoCoberturaController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('gestacao_cobertura')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela gestacao_cobertura não existe no banco.',
            ]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));

        $hasUsuarioTable = Schema::hasTable('usuario');
        $hasUsuarioId = Schema::hasColumn('gestacao_cobertura', 'usuario_id');

        $select = [
            'gc.id',
            'gc.data',
            'gc.hora',
            'gc.localizacao',
            'gc.baia',
            'gc.semen',
            'f.id_primaria',
            'f.id_secundaria',
            'm.id_primaria as macho_id_primaria',
        ];

        if ($hasUsuarioId && $hasUsuarioTable) {
            $select[] = 'u.nome as usuario_nome';
        } else {
            $select[] = DB::raw('gc.funcionario as usuario_nome');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'peso_matriz')) {
            $select[] = 'gc.peso_matriz';
        } else {
            $select[] = DB::raw('NULL as peso_matriz');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'caracteristicas')) {
            $select[] = 'gc.caracteristicas';
        } else {
            $select[] = DB::raw('NULL as caracteristicas');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'observacoes')) {
            $select[] = 'gc.observacoes';
        } else {
            $select[] = DB::raw('NULL as observacoes');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'presenca_cio')) {
            $select[] = 'gc.presenca_cio';
        } else {
            $select[] = DB::raw('NULL as presenca_cio');
        }

        $query = DB::table('gestacao_cobertura as gc')
            ->join('femea as f', 'f.id', '=', 'gc.femea_id')
            ->leftJoin('macho as m', 'm.id', '=', 'gc.macho_id')
            ->when($hasUsuarioId && $hasUsuarioTable, function ($q) {
                $q->leftJoin('usuario as u', 'u.id', '=', 'gc.usuario_id');
            })
            ->orderByDesc('gc.data')
            ->orderByDesc('gc.hora')
            ->select($select)
            ->limit($limit);

        $rows = $query->get();

        $semenByCoberturaId = [];
        if (Schema::hasTable('gestacao_cobertura_semen') && $rows->isNotEmpty()) {
            $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->filter()->values()->all();
            if (! empty($ids)) {
                $semenByCoberturaId = DB::table('gestacao_cobertura_semen')
                    ->whereIn('cobertura_id', $ids)
                    ->orderBy('id')
                    ->get(['cobertura_id', 'semen'])
                    ->groupBy('cobertura_id')
                    ->map(fn ($group) => $group->pluck('semen')->filter()->map(fn ($v) => (string) $v)->values()->all())
                    ->all();
            }
        }

        $montasByCoberturaId = [];
        $machoIdPrimariaById = [];
        $usuarioNomeById = [];
        if (Schema::hasTable('gestacao_cobertura_monta') && $rows->isNotEmpty()) {
            $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->filter()->values()->all();
            if (! empty($ids)) {
                $montasRows = DB::table('gestacao_cobertura_monta')
                    ->whereIn('cobertura_id', $ids)
                    ->orderBy('cobertura_id')
                    ->orderBy('ordem')
                    ->orderBy('id')
                    ->get([
                        'cobertura_id',
                        'ordem',
                        'tipo',
                        'macho_id',
                        'semen',
                        'data',
                        'hora',
                        'usuario_id',
                    ]);

                $machoIds = $montasRows->pluck('macho_id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
                if (! empty($machoIds) && Schema::hasTable('macho')) {
                    $machoIdPrimariaById = DB::table('macho')
                        ->whereIn('id', $machoIds)
                        ->pluck('id_primaria', 'id')
                        ->map(fn ($v) => $v === null ? null : (string) $v)
                        ->all();
                }

                $usuarioIds = $montasRows->pluck('usuario_id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
                if (! empty($usuarioIds) && Schema::hasTable('usuario')) {
                    $usuarioNomeById = DB::table('usuario')
                        ->whereIn('id', $usuarioIds)
                        ->pluck('nome', 'id')
                        ->map(fn ($v) => $v === null ? null : (string) $v)
                        ->all();
                }

                $montasByCoberturaId = $montasRows
                    ->groupBy('cobertura_id')
                    ->map(fn ($group) => $group->values()->all())
                    ->all();
            }
        }

        $items = $rows->map(function ($row) use ($semenByCoberturaId, $montasByCoberturaId, $machoIdPrimariaById, $usuarioNomeById) {
            $coberturaId = (int) $row->id;
            $dt = Carbon::parse($row->data)->startOfDay();
            $diaPigAbs = PigCycleService::toPigAbsoluteDay($dt);
            $horaCob = $row->hora === null ? null : (string) $row->hora;
            if ($horaCob !== null && preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaCob)) {
                $horaCob = substr($horaCob, 0, 5);
            }
            $semens = $semenByCoberturaId[$coberturaId] ?? [];
            $semenDisplay = null;
            if (! empty($semens)) {
                $semenDisplay = implode(' + ', $semens);
            } elseif ($row->semen !== null && trim((string) $row->semen) !== '') {
                $semenDisplay = (string) $row->semen;
            }

            $montas = [];
            if (array_key_exists($coberturaId, $montasByCoberturaId)) {
                $montas = array_map(function ($m) use ($machoIdPrimariaById, $usuarioNomeById) {
                    $tipo = (string) ($m->tipo ?? '');
                    $machoId = $m->macho_id === null ? null : (int) $m->macho_id;
                    $machoPrimaria = $machoId !== null ? ($machoIdPrimariaById[$machoId] ?? null) : null;
                    $usuarioId = $m->usuario_id === null ? null : (int) $m->usuario_id;

                    $horaM = $m->hora === null ? null : (string) $m->hora;
                    if ($horaM !== null && preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaM)) {
                        $horaM = substr($horaM, 0, 5);
                    }

                    return [
                        'tipo' => $tipo,
                        'macho' => $tipo === 'macho' ? ($machoPrimaria === null ? null : (string) $machoPrimaria) : null,
                        'semen' => $tipo === 'semen' ? (empty($m->semen) ? null : (string) $m->semen) : null,
                        'data' => Carbon::parse($m->data)->format('d/m/Y'),
                        'hora' => $horaM,
                        'usuario' => $usuarioId !== null ? ($usuarioNomeById[$usuarioId] ?? null) : null,
                    ];
                }, $montasByCoberturaId[$coberturaId]);
            }

            $montasSummary = null;
            if (! empty($montas)) {
                $refs = array_values(array_filter(array_map(function ($m) {
                    if (($m['tipo'] ?? null) === 'macho' && ! empty($m['macho'])) return 'M-'.$m['macho'];
                    if (($m['tipo'] ?? null) === 'semen' && ! empty($m['semen'])) return 'S-'.$m['semen'];
                    return null;
                }, $montas)));

                if (! empty($refs)) {
                    $montasSummary = count($refs) === 1 ? $refs[0] : ($refs[0].' + '.(count($refs) - 1));
                }
            }

            return [
                'id' => $coberturaId,
                'matriz' => (string) $row->id_primaria,
                'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'macho' => $row->macho_id_primaria === null ? null : (string) $row->macho_id_primaria,
                'semen' => $semenDisplay,
                'semens' => $semens,
                'montas' => $montas,
                'montas_summary' => $montasSummary,
                'data' => $diaPigAbs === null ? '-' : (string) $diaPigAbs,
                'data_br' => $dt->format('d/m/Y'),
                'data_iso' => $dt->toDateString(),
                'hora' => $horaCob,
                'usuario' => $row->usuario_nome === null ? null : (string) $row->usuario_nome,
                'localizacao' => $row->localizacao === null ? null : (string) $row->localizacao,
                'baia' => $row->baia === null ? null : (string) $row->baia,
                'peso_matriz' => $row->peso_matriz === null ? null : (float) $row->peso_matriz,
                'caracteristicas' => $row->caracteristicas === null ? null : (string) $row->caracteristicas,
                'observacoes' => $row->observacoes === null ? null : (string) $row->observacoes,
                'presenca_cio' => $row->presenca_cio === null ? null : (string) $row->presenca_cio,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('gestacao_cobertura') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'usuario_id' => ['required', 'exists:usuario,id'],
            'macho_id' => ['nullable', 'exists:macho,id'],
            'semen' => ['nullable', 'string', 'max:120'],
            'semens' => ['nullable', 'array', 'min:1', 'max:10'],
            'semens.*' => ['required', 'string', 'max:120'],
            'data' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'presenca_cio' => ['required', 'in:sim'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'peso_matriz' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'caracteristicas' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:500'],
            'montas' => ['nullable', 'array', 'min:1', 'max:20'],
            'montas.*.tipo' => ['required', 'in:macho,semen'],
            'montas.*.macho_id' => ['nullable'],
            'montas.*.semen' => ['nullable', 'string', 'max:120'],
            'montas.*.data' => ['required', 'date'],
            'montas.*.hora' => ['required', 'date_format:H:i'],
            'montas.*.usuario_id' => ['required', 'exists:usuario,id'],
        ]);

        $warnings = [];

        $femeaRow = DB::table('femea')
            ->where('id', (int) $validated['femea_id'])
            ->select(['id', 'tipo_compra', 'localizacao', 'baia', 'data_nascimento'])
            ->first();

        if ($femeaRow === null) {
            return response()->json([
                'message' => 'Fêmea inválida.',
            ], 422);
        }

        $metaInt = function (string $key, int $default): int {
            if (! Schema::hasTable('meta')) {
                return $default;
            }
            $raw = DB::table('meta')->where('chave', $key)->value('valor');
            if ($raw === null || trim((string) $raw) === '') {
                return $default;
            }
            $n = (int) $raw;

            return $n < 0 ? $default : $n;
        };

        $cioDias = max(1, $metaInt('criterio_dias_cio', 3));
        $diasAteCio = max(1, $metaInt('criterio_dias_ate_cio', 21));
        $gestacaoDias = max(1, $metaInt('criterio_dias_gestacao', 114));
        $lactacaoMinDias = max(1, $metaInt('criterio_dias_lactacao_min', 21));
        $lactacaoMaxDias = max($lactacaoMinDias, $metaInt('criterio_dias_lactacao_max', 28));
        $intervaloDesmameCioDias = max(0, $metaInt('criterio_dias_intervalo_desmame_cio', 5));
        $coberturaCiclosMin = max(0, $metaInt('criterio_cobertura_ciclos_min', 3));
        $coberturaIdadeMin = max(0, $metaInt('criterio_cobertura_idade_min_dias', 210));


        $tipo = (string) ($femeaRow->tipo_compra ?? '');
        if (! in_array($tipo, ['leitoa', 'matriz_vazia'], true)) {
            return response()->json([
                'message' => 'A fêmea selecionada não pode receber cobertura (apenas leitoa ou matriz vazia).',
            ], 422);
        }

        if (Schema::hasTable('femea_movimento')) {
            $inativa = DB::table('femea_movimento')
                ->where('femea_id', (int) $validated['femea_id'])
                ->whereIn('acao', ['morte', 'descarte', 'venda'])
                ->exists();

            if ($inativa) {
                return response()->json([
                    'message' => 'A fêmea selecionada está inativa e não pode receber cobertura.',
                ], 422);
            }
        }

        $dataCobertura = Carbon::parse($validated['data'])->startOfDay();
        $dataCoberturaIso = $dataCobertura->toDateString();

        if (! empty($femeaRow->data_nascimento)) {
            $idadeDias = Carbon::parse($femeaRow->data_nascimento)->diffInDays($dataCobertura);
            if ($idadeDias < $coberturaIdadeMin) {
                return response()->json([
                    'message' => "Idade insuficiente para cobertura ({$idadeDias} dias, mínimo {$coberturaIdadeMin}).",
                ], 422);
            }
        }

        $dupQuery = DB::table('gestacao_cobertura')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', $dataCoberturaIso);

        if (Schema::hasColumn('gestacao_cobertura', 'hora')) {
            $dupQuery->where('hora', (string) $validated['hora']);
        }

        if ($dupQuery->exists()) {
            return response()->json([
                'message' => 'Já existe uma cobertura registrada para essa data e hora.',
            ], 422);
        }

        $cioWindowStart = null;
        $cioWindowEnd = null;

        $last = DB::table('gestacao_cobertura')
            ->where('femea_id', (int) $validated['femea_id'])
            ->max('data');

        if ($last) {
            $lastCobertura = Carbon::parse($last)->startOfDay();
            $partoPrev = (clone $lastCobertura)->addDays($gestacaoDias);
            $desmamePrevMin = (clone $partoPrev)->addDays($lactacaoMinDias);
            $desmamePrevMax = (clone $partoPrev)->addDays($lactacaoMaxDias);

            if ($dataCobertura->lt($partoPrev)) {
                return response()->json([
                    'message' => 'A fêmea selecionada está gestante e não pode receber cobertura.',
                ], 422);
            }

            if ($dataCobertura->betweenIncluded($partoPrev, $desmamePrevMax)) {
                return response()->json([
                    'message' => 'A fêmea selecionada está em lactação e não pode receber cobertura.',
                ], 422);
            }

            $firstCioStart = (clone $desmamePrevMin)->addDays($intervaloDesmameCioDias);
            if ($dataCobertura->lt($firstCioStart)) {
                return response()->json([
                    'message' => 'A fêmea selecionada ainda não está na fase de cio para cobertura.',
                ], 422);
            }

            $diff = $firstCioStart->diffInDays($dataCobertura);
            $cycles = intdiv($diff, $diasAteCio);
            // Removido bloqueio por ciclos (permitindo cobertura independente da contagem)
            if ($cycles < $coberturaCiclosMin) {
                $warnings[] = "Atenção: Cobertura realizada no {$cycles}º ciclo (critério ideal: {$coberturaCiclosMin}).";
            }
            $cioWindowStart = (clone $firstCioStart)->addDays($cycles * $diasAteCio)->startOfDay();
            $cioWindowEnd = (clone $cioWindowStart)->addDays($cioDias)->startOfDay();

            if ($dataCobertura->gt($cioWindowEnd)) {
                return response()->json([
                    'message' => 'A fêmea selecionada não está na fase de cio para cobertura.',
                ], 422);
            }
        }

        if (! Schema::hasTable('gestacao_cio')) {
            return response()->json([
                'message' => 'Registre o cio antes da cobertura.',
            ], 422);
        }

        if (Schema::hasTable('gestacao_salta_cio')) {
            $saltaQuery = DB::table('gestacao_salta_cio')
                ->where('femea_id', (int) $validated['femea_id'])
                ->where('data', '<=', $dataCobertura->toDateString());

            if ($cioWindowStart) {
                $saltaQuery->where('data', '>=', $cioWindowStart->toDateString());
            }

            $lastSalta = $saltaQuery->max('data');
            if ($lastSalta) {
                $dataSalta = Carbon::parse($lastSalta)->startOfDay();
                if ($dataSalta->diffInDays($dataCobertura) <= $cioDias) {
                    return response()->json([
                        'message' => 'Existe salta cio registrado para este ciclo. Aguarde o próximo cio para registrar cobertura.',
                    ], 422);
                }
            }
        }

        $cioQuery = DB::table('gestacao_cio')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', '<=', $dataCobertura->toDateString());

        $lastCio = $cioQuery->orderByDesc('data')->first();

        if (! $lastCio) {
            $warnings[] = 'Atenção: Não foi encontrado um registro de cio anterior para esta cobertura. Recomenda-se registrar o cio na ficha da matriz.';
        } else {
            $dataCio = Carbon::parse($lastCio->data)->startOfDay();
            if ($dataCio->diffInDays($dataCobertura) > 5) {
                $warnings[] = 'Atenção: O último cio registrado para esta matriz foi há mais de 5 dias.';
            }
        }

        $montas = [];
        if (array_key_exists('montas', $validated) && is_array($validated['montas'])) {
            if (! Schema::hasTable('gestacao_cobertura_monta')) {
                return response()->json([
                    'message' => 'Para registrar as montas/inseminações, é necessário criar a tabela gestacao_cobertura_monta no banco.',
                    'sql' => "CREATE TABLE IF NOT EXISTS `gestacao_cobertura_monta` (\n  `id` bigint unsigned NOT NULL AUTO_INCREMENT,\n  `cobertura_id` bigint unsigned NOT NULL,\n  `ordem` int unsigned NOT NULL DEFAULT 1,\n  `tipo` enum('macho','semen') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,\n  `macho_id` bigint unsigned DEFAULT NULL,\n  `semen` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `data` date NOT NULL,\n  `hora` time NOT NULL,\n  `usuario_id` bigint unsigned DEFAULT NULL,\n  `criado_em` timestamp NULL DEFAULT NULL,\n  `atualizado_em` timestamp NULL DEFAULT NULL,\n  PRIMARY KEY (`id`),\n  KEY `idx_gcm_cobertura` (`cobertura_id`),\n  KEY `idx_gcm_data` (`data`),\n  KEY `idx_gcm_macho` (`macho_id`),\n  KEY `idx_gcm_usuario` (`usuario_id`),\n  CONSTRAINT `fk_gcm_cobertura` FOREIGN KEY (`cobertura_id`) REFERENCES `gestacao_cobertura` (`id`) ON DELETE CASCADE,\n  CONSTRAINT `fk_gcm_macho` FOREIGN KEY (`macho_id`) REFERENCES `macho` (`id`) ON DELETE SET NULL,\n  CONSTRAINT `fk_gcm_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;",
                ], 422);
            }

            foreach (array_values($validated['montas']) as $i => $m) {
                $tipo = (string) ($m['tipo'] ?? '');
                $machoId = $m['macho_id'] ?? null;
                $semen = trim((string) ($m['semen'] ?? ''));

                if ($tipo === 'macho') {
                    $machoId = is_numeric($machoId) ? (int) $machoId : null;
                    if (! $machoId || ! Schema::hasTable('macho') || ! DB::table('macho')->where('id', $machoId)->exists()) {
                        return response()->json(['message' => 'Monta '.($i + 1).': macho inválido.'], 422);
                    }
                    $semen = '';
                } elseif ($tipo === 'semen') {
                    if ($semen === '') {
                        return response()->json(['message' => 'Monta '.($i + 1).': sêmen inválido.'], 422);
                    }
                    $machoId = null;
                } else {
                    return response()->json(['message' => 'Monta '.($i + 1).': tipo inválido.'], 422);
                }

                $montas[] = [
                    'ordem' => $i + 1,
                    'tipo' => $tipo,
                    'macho_id' => $machoId,
                    'semen' => $semen === '' ? null : $semen,
                    'data' => Carbon::parse($m['data'])->toDateString(),
                    'hora' => (string) $m['hora'],
                    'usuario_id' => (int) $m['usuario_id'],
                ];
            }
        }

        $semens = [];
        $machoId = null;

        if (! empty($montas)) {
            $first = $montas[0];
            if ($first['tipo'] === 'macho') {
                $machoId = $first['macho_id'];
            } else {
                $semens = [$first['semen']];
            }
        } else {
            if (array_key_exists('semens', $validated) && is_array($validated['semens'])) {
                $semens = collect($validated['semens'])
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->all();
            }

            $semen = trim((string) ($validated['semen'] ?? ''));
            if ($semen !== '') {
                $semens = array_values(array_unique(array_merge($semens, [$semen])));
            }

            $machoId = $validated['macho_id'] ?? null;

            if ($machoId === null && empty($semens)) {
                return response()->json([
                    'message' => 'Informe o macho ou o sêmen utilizado.',
                ], 422);
            }

            if (count($semens) > 1 && ! Schema::hasTable('gestacao_cobertura_semen')) {
                return response()->json([
                    'message' => 'Para informar mais de um sêmen, é necessário criar a tabela gestacao_cobertura_semen no banco.',
                    'sql' => "CREATE TABLE IF NOT EXISTS `gestacao_cobertura_semen` (\n  `id` bigint unsigned NOT NULL AUTO_INCREMENT,\n  `cobertura_id` bigint unsigned NOT NULL,\n  `semen` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,\n  `criado_em` timestamp NULL DEFAULT NULL,\n  `atualizado_em` timestamp NULL DEFAULT NULL,\n  PRIMARY KEY (`id`),\n  KEY `idx_gcs_cobertura` (`cobertura_id`),\n  CONSTRAINT `fk_gcs_cobertura` FOREIGN KEY (`cobertura_id`) REFERENCES `gestacao_cobertura` (`id`) ON DELETE CASCADE\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;",
                ], 422);
            }
        }

        $usuarioNome = DB::table('usuario')->where('id', (int) $validated['usuario_id'])->value('nome');
        $usuarioNome = is_string($usuarioNome) ? trim($usuarioNome) : '';
        if ($usuarioNome === '') {
            return response()->json([
                'message' => 'Usuário inválido.',
            ], 422);
        }

        $payload = [
            'femea_id' => (int) $validated['femea_id'],
            'usuario_id' => Schema::hasColumn('gestacao_cobertura', 'usuario_id') ? (int) $validated['usuario_id'] : null,
            'macho_id' => $machoId === null ? null : (int) $machoId,
            'semen' => empty($semens) ? null : $semens[0],
            'data' => Carbon::parse($validated['data'])->toDateString(),
            'hora' => (string) $validated['hora'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ];

        if (! Schema::hasColumn('gestacao_cobertura', 'usuario_id') && Schema::hasColumn('gestacao_cobertura', 'funcionario')) {
            $payload['funcionario'] = $usuarioNome;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'peso_matriz')) {
            $payload['peso_matriz'] = $validated['peso_matriz'] ?? null;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'caracteristicas')) {
            $value = trim((string) ($validated['caracteristicas'] ?? ''));
            $payload['caracteristicas'] = $value === '' ? null : $value;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'observacoes')) {
            $value = trim((string) ($validated['observacoes'] ?? ''));
            $payload['observacoes'] = $value === '' ? null : $value;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'presenca_cio')) {
            $payload['presenca_cio'] = (string) $validated['presenca_cio'];
        }

        $warnings = array_values(array_merge($warnings, $this->criteriosWarnings($payload)));

        $coberturaId = null;
        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";
        $sqlEnumMacho = "ALTER TABLE `macho_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cobertura') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($payload, $warnings, $semens, $montas, &$coberturaId, $femeaRow) {
                $coberturaId = (int) DB::table('gestacao_cobertura')->insertGetId($payload);

            if (count($semens) > 1 && Schema::hasTable('gestacao_cobertura_semen')) {
                $now = now();
                DB::table('gestacao_cobertura_semen')->insert(
                    array_map(function ($semen) use ($coberturaId, $now) {
                        return [
                            'cobertura_id' => $coberturaId,
                            'semen' => $semen,
                            'criado_em' => $now,
                            'atualizado_em' => $now,
                        ];
                    }, $semens)
                );
            }

            if (! empty($montas) && Schema::hasTable('gestacao_cobertura_monta')) {
                $now = now();
                DB::table('gestacao_cobertura_monta')->insert(
                    array_map(function ($m) use ($coberturaId, $now) {
                        return [
                            'cobertura_id' => $coberturaId,
                            'ordem' => (int) ($m['ordem'] ?? 1),
                            'tipo' => (string) ($m['tipo'] ?? ''),
                            'macho_id' => empty($m['macho_id']) ? null : (int) $m['macho_id'],
                            'semen' => empty($m['semen']) ? null : (string) $m['semen'],
                            'data' => (string) $m['data'],
                            'hora' => (string) $m['hora'],
                            'usuario_id' => empty($m['usuario_id']) ? null : (int) $m['usuario_id'],
                            'criado_em' => $now,
                            'atualizado_em' => $now,
                        ];
                    }, $montas)
                );
            }

            $updateFemea = [];
            if (Schema::hasColumn('femea', 'data_cobertura')) {
                $updateFemea['data_cobertura'] = $payload['data'];
            }
            if (Schema::hasColumn('femea', 'tipo_compra')) {
                $updateFemea['tipo_compra'] = 'matriz_gestante';
            }
            if (Schema::hasColumn('femea', 'localizacao') && $payload['localizacao'] !== null) {
                $updateFemea['localizacao'] = $payload['localizacao'];
            }
            if (Schema::hasColumn('femea', 'baia') && $payload['baia'] !== null) {
                $updateFemea['baia'] = $payload['baia'];
            }
            if (! empty($updateFemea)) {
                $updateFemea['atualizado_em'] = now();
                DB::table('femea')->where('id', $payload['femea_id'])->update($updateFemea);
            }

            if (Schema::hasTable('femea_movimento')) {
                $now = now();
                $mov = [
                    'femea_id' => (int) $payload['femea_id'],
                    'acao' => 'cobertura',
                    'data' => (string) $payload['data'],
                    'valor' => null,
                    'peso' => $payload['peso_matriz'] ?? null,
                    'fornecedor_id' => null,
                    'observacoes' => 'Cobertura #'.$coberturaId,
                ];

                if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                    $mov['femea_id_primaria'] = (string) ($femeaRow->id_primaria ?? '');
                }
                if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                    $mov['criado_em'] = $now;
                }
                if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                    $mov['atualizado_em'] = $now;
                }

                DB::table('femea_movimento')->insert($mov);
            }

            if (Schema::hasTable('macho_movimento')) {
                $machoIds = [];
                if (! empty($payload['macho_id'])) {
                    $machoIds[] = (int) $payload['macho_id'];
                }
                foreach ($montas as $m) {
                    if (($m['tipo'] ?? null) === 'macho' && ! empty($m['macho_id'])) {
                        $machoIds[] = (int) $m['macho_id'];
                    }
                }

                $machoIds = array_values(array_unique(array_filter($machoIds)));
                if (! empty($machoIds)) {
                    $now = now();
                    $obs = 'Cobertura #'.$coberturaId.' - Matriz '.(string) ($femeaRow->id_primaria ?? '');
                    foreach ($machoIds as $mid) {
                        $movM = [
                            'macho_id' => (int) $mid,
                            'acao' => 'cobertura',
                            'data' => (string) ($payload['data'] ?? ''),
                            'valor' => null,
                            'peso' => null,
                            'fornecedor_id' => null,
                            'causa_id' => null,
                            'comprador' => null,
                            'observacoes' => $obs,
                        ];
                        if (Schema::hasColumn('macho_movimento', 'criado_em')) {
                            $movM['criado_em'] = $now;
                        }
                        if (Schema::hasColumn('macho_movimento', 'atualizado_em')) {
                            $movM['atualizado_em'] = $now;
                        }
                        DB::table('macho_movimento')->insert($movM);
                    }
                }
            }

            if (! empty($warnings) && Schema::hasTable('criterio_log')) {
                $now = now();
                $usuarioId = $payload['usuario_id'] ?? null;

                DB::table('criterio_log')->insert([
                    'modulo' => 'gestacao',
                    'evento' => 'cobertura',
                    'referencia_id' => $coberturaId,
                    'usuario_id' => $usuarioId === null ? null : (int) $usuarioId,
                    'femea_id' => (int) $payload['femea_id'],
                    'warnings' => json_encode(array_values($warnings), JSON_UNESCAPED_UNICODE),
                    'dados' => json_encode([
                        'data' => $payload['data'] ?? null,
                        'hora' => $payload['hora'] ?? null,
                        'peso_matriz' => $payload['peso_matriz'] ?? null,
                        'presenca_cio' => $payload['presenca_cio'] ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'ocorrido_em' => $now,
                    'criado_em' => $now,
                    'atualizado_em' => $now,
                ]);
            }
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return response()->json([
                    'message' => "Para registrar o histórico no plantel, é necessário atualizar os ENUMs de movimentos para incluir as novas ações.",
                    'sql' => [$sqlEnum, $sqlEnumMacho],
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Cobertura registrada com sucesso!',
            'warnings' => $warnings,
        ], 201);
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('gestacao_cobertura')) {
            return response()->json([
                'message' => 'Tabela gestacao_cobertura não existe no banco.',
            ], 422);
        }

        $row = DB::table('gestacao_cobertura')->where('id', $id)->select(['id', 'femea_id'])->first();
        if (! $row) {
            return response()->json([
                'message' => 'Cobertura não encontrada.',
            ], 404);
        }

        $femeaId = (int) $row->femea_id;

        DB::transaction(function () use ($id, $femeaId) {
            if (Schema::hasTable('femea_movimento')) {
                DB::table('femea_movimento')
                    ->where('femea_id', $femeaId)
                    ->where('acao', 'cobertura')
                    ->where('observacoes', 'Cobertura #'.$id)
                    ->delete();
            }

            if (Schema::hasTable('macho_movimento')) {
                DB::table('macho_movimento')
                    ->where('acao', 'cobertura')
                    ->where('observacoes', 'like', 'Cobertura #'.$id.'%')
                    ->delete();
            }

            if (Schema::hasTable('gestacao_cobertura_monta')) {
                DB::table('gestacao_cobertura_monta')->where('cobertura_id', $id)->delete();
            }
            if (Schema::hasTable('gestacao_cobertura_semen')) {
                DB::table('gestacao_cobertura_semen')->where('cobertura_id', $id)->delete();
            }
            DB::table('gestacao_cobertura')->where('id', $id)->delete();

            if (Schema::hasTable('criterio_log')) {
                DB::table('criterio_log')
                    ->where('evento', 'cobertura')
                    ->where('referencia_id', $id)
                    ->delete();
            }

            if (! Schema::hasTable('femea')) {
                return;
            }

            $last = DB::table('gestacao_cobertura')->where('femea_id', $femeaId)->max('data');

            $update = [];
            if (Schema::hasColumn('femea', 'data_cobertura')) {
                $update['data_cobertura'] = $last ? Carbon::parse($last)->toDateString() : null;
            }

            if (Schema::hasColumn('femea', 'tipo_compra')) {
                if ($last) {
                    $update['tipo_compra'] = 'matriz_gestante';
                } else {
                    $current = DB::table('femea')->where('id', $femeaId)->value('tipo_compra');
                    if ((string) $current === 'matriz_gestante') {
                        $update['tipo_compra'] = 'matriz_vazia';
                    }
                }
            }

            if (! empty($update)) {
                $update['atualizado_em'] = now();
                DB::table('femea')->where('id', $femeaId)->update($update);
            }
        });

        return response()->json([
            'message' => 'Cobertura excluída com sucesso!',
        ]);
    }

    public function show(int $id)
    {
        if (! Schema::hasTable('gestacao_cobertura')) {
            return response()->json([
                'message' => 'Tabela gestacao_cobertura não existe no banco.',
            ], 422);
        }

        $hasUsuarioTable = Schema::hasTable('usuario');
        $hasUsuarioId = Schema::hasColumn('gestacao_cobertura', 'usuario_id');

        $select = [
            'gc.id',
            'gc.femea_id',
            'gc.usuario_id',
            'gc.macho_id',
            'gc.semen',
            'gc.data',
            'gc.hora',
            'gc.localizacao',
            'gc.baia',
            'f.id_primaria',
            'f.id_secundaria',
        ];

        if (Schema::hasColumn('gestacao_cobertura', 'peso_matriz')) {
            $select[] = 'gc.peso_matriz';
        } else {
            $select[] = DB::raw('NULL as peso_matriz');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'caracteristicas')) {
            $select[] = 'gc.caracteristicas';
        } else {
            $select[] = DB::raw('NULL as caracteristicas');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'observacoes')) {
            $select[] = 'gc.observacoes';
        } else {
            $select[] = DB::raw('NULL as observacoes');
        }

        if (Schema::hasColumn('gestacao_cobertura', 'presenca_cio')) {
            $select[] = 'gc.presenca_cio';
        } else {
            $select[] = DB::raw('NULL as presenca_cio');
        }

        $row = DB::table('gestacao_cobertura as gc')
            ->join('femea as f', 'f.id', '=', 'gc.femea_id')
            ->when($hasUsuarioId && $hasUsuarioTable, function ($q) {
                $q->leftJoin('usuario as u', 'u.id', '=', 'gc.usuario_id');
            })
            ->where('gc.id', $id)
            ->select($select)
            ->first();

        if (! $row) {
            return response()->json([
                'message' => 'Cobertura não encontrada.',
            ], 404);
        }

        $semens = [];
        if (Schema::hasTable('gestacao_cobertura_semen')) {
            $semens = DB::table('gestacao_cobertura_semen')
                ->where('cobertura_id', (int) $row->id)
                ->orderBy('id')
                ->pluck('semen')
                ->filter()
                ->map(fn ($v) => (string) $v)
                ->values()
                ->all();
        }
        if (empty($semens) && $row->semen !== null && trim((string) $row->semen) !== '') {
            $semens = [(string) $row->semen];
        }

        $montas = [];
        if (Schema::hasTable('gestacao_cobertura_monta')) {
            $montasRows = DB::table('gestacao_cobertura_monta')
                ->where('cobertura_id', (int) $row->id)
                ->orderBy('ordem')
                ->orderBy('id')
                ->get([
                    'ordem',
                    'tipo',
                    'macho_id',
                    'semen',
                    'data',
                    'hora',
                    'usuario_id',
                ]);

            $machoIds = $montasRows->pluck('macho_id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
            $machoIdPrimariaById = [];
            if (! empty($machoIds) && Schema::hasTable('macho')) {
                $machoIdPrimariaById = DB::table('macho')
                    ->whereIn('id', $machoIds)
                    ->pluck('id_primaria', 'id')
                    ->map(fn ($v) => $v === null ? null : (string) $v)
                    ->all();
            }

            $montas = $montasRows->map(function ($m) use ($machoIdPrimariaById) {
                $tipo = (string) ($m->tipo ?? '');
                $machoId = $m->macho_id === null ? null : (int) $m->macho_id;
                $machoPrimaria = $machoId !== null ? ($machoIdPrimariaById[$machoId] ?? null) : null;
                $semen = $m->semen === null ? null : (string) $m->semen;
                $ref = '';
                if ($tipo === 'macho' && $machoPrimaria !== null) {
                    $ref = 'M-' . $machoPrimaria;
                } elseif ($tipo === 'semen' && $semen !== null && $semen !== '') {
                    $ref = 'S-' . $semen;
                }

                return [
                    'ordem' => (int) ($m->ordem ?? 1),
                    'tipo' => $tipo,
                    'macho_id' => $machoId,
                    'semen' => $semen,
                    'ref' => $ref,
                    'data' => Carbon::parse($m->data)->toDateString(),
                    'hora' => $m->hora === null ? null : (string) $m->hora,
                    'usuario_id' => $m->usuario_id === null ? null : (int) $m->usuario_id,
                ];
            })->values()->all();
        }

        $dataIso = Carbon::parse($row->data)->toDateString();
        $horaCob = $row->hora === null ? null : (string) $row->hora;
        if ($horaCob !== null && preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaCob)) {
            $horaCob = substr($horaCob, 0, 5);
        }

        return response()->json([
            'item' => [
                'id' => (int) $row->id,
                'femea_id' => (int) $row->femea_id,
                'matriz' => (string) $row->id_primaria,
                'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'usuario_id' => $row->usuario_id === null ? null : (int) $row->usuario_id,
                'macho_id' => $row->macho_id === null ? null : (int) $row->macho_id,
                'semens' => $semens,
                'data' => $dataIso,
                'hora' => $horaCob,
                'localizacao' => $row->localizacao === null ? null : (string) $row->localizacao,
                'baia' => $row->baia === null ? null : (string) $row->baia,
                'peso_matriz' => $row->peso_matriz === null ? null : (float) $row->peso_matriz,
                'caracteristicas' => $row->caracteristicas === null ? null : (string) $row->caracteristicas,
                'observacoes' => $row->observacoes === null ? null : (string) $row->observacoes,
                'presenca_cio' => $row->presenca_cio === null ? null : (string) $row->presenca_cio,
                'montas' => $montas,
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (! Schema::hasTable('gestacao_cobertura') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $existing = DB::table('gestacao_cobertura')->where('id', $id)->select(['id', 'femea_id'])->first();
        if (! $existing) {
            return response()->json([
                'message' => 'Cobertura não encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'usuario_id' => ['required', 'exists:usuario,id'],
            'macho_id' => ['nullable', 'exists:macho,id'],
            'semen' => ['nullable', 'string', 'max:120'],
            'semens' => ['nullable', 'array', 'min:1', 'max:10'],
            'semens.*' => ['required', 'string', 'max:120'],
            'data' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'presenca_cio' => ['required', 'in:sim'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'peso_matriz' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'caracteristicas' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:500'],
            'montas' => ['nullable', 'array', 'min:1', 'max:20'],
            'montas.*.tipo' => ['required', 'in:macho,semen'],
            'montas.*.macho_id' => ['nullable'],
            'montas.*.semen' => ['nullable', 'string', 'max:120'],
            'montas.*.data' => ['required', 'date'],
            'montas.*.hora' => ['required', 'date_format:H:i'],
            'montas.*.usuario_id' => ['required', 'exists:usuario,id'],
        ]);

        $dataIso = Carbon::parse($validated['data'])->startOfDay()->toDateString();

        $dupQuery = DB::table('gestacao_cobertura')
            ->where('id', '<>', $id)
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', $dataIso);

        if (Schema::hasColumn('gestacao_cobertura', 'hora')) {
            $dupQuery->where('hora', (string) $validated['hora']);
        }

        if ($dupQuery->exists()) {
            return response()->json([
                'message' => 'Já existe uma cobertura registrada para essa data e hora.',
            ], 422);
        }

        $montasRaw = is_array($validated['montas'] ?? null) ? $validated['montas'] : [];
        $montas = array_values(array_map(function ($m, $i) {
            return [
                'ordem' => $i + 1,
                'tipo' => (string) ($m['tipo'] ?? ''),
                'macho_id' => empty($m['macho_id']) ? null : (int) $m['macho_id'],
                'semen' => empty($m['semen']) ? null : (string) $m['semen'],
                'data' => Carbon::parse((string) ($m['data'] ?? ''))->startOfDay()->toDateString(),
                'hora' => (string) ($m['hora'] ?? ''),
                'usuario_id' => empty($m['usuario_id']) ? null : (int) $m['usuario_id'],
            ];
        }, $montasRaw, array_keys($montasRaw)));

        $semens = [];
        if (is_array($validated['semens'] ?? null) && ! empty($validated['semens'])) {
            $semens = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $validated['semens'])));
        } elseif (! empty($validated['semen'])) {
            $semens = [trim((string) $validated['semen'])];
        }

        $machoId = $validated['macho_id'] ?? null;

        $payload = [
            'femea_id' => (int) $validated['femea_id'],
            'usuario_id' => Schema::hasColumn('gestacao_cobertura', 'usuario_id') ? (int) $validated['usuario_id'] : null,
            'macho_id' => $machoId === null ? null : (int) $machoId,
            'semen' => empty($semens) ? null : $semens[0],
            'data' => $dataIso,
            'hora' => (string) $validated['hora'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'atualizado_em' => now(),
        ];

        if (Schema::hasColumn('gestacao_cobertura', 'peso_matriz')) {
            $payload['peso_matriz'] = $validated['peso_matriz'] ?? null;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'caracteristicas')) {
            $value = trim((string) ($validated['caracteristicas'] ?? ''));
            $payload['caracteristicas'] = $value === '' ? null : $value;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'observacoes')) {
            $value = trim((string) ($validated['observacoes'] ?? ''));
            $payload['observacoes'] = $value === '' ? null : $value;
        }

        if (Schema::hasColumn('gestacao_cobertura', 'presenca_cio')) {
            $payload['presenca_cio'] = (string) $validated['presenca_cio'];
        }

        $warnings = array_values($this->criteriosWarnings($payload));
        $oldFemeaId = (int) $existing->femea_id;
        $newFemeaId = (int) $payload['femea_id'];
        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";
        $sqlEnumMacho = "ALTER TABLE `macho_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cobertura') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($id, $payload, $warnings, $semens, $montas, $oldFemeaId, $newFemeaId) {
                DB::table('gestacao_cobertura')->where('id', $id)->update($payload);

                if (Schema::hasTable('gestacao_cobertura_monta')) {
                    DB::table('gestacao_cobertura_monta')->where('cobertura_id', $id)->delete();
                    if (! empty($montas)) {
                        $now = now();
                        DB::table('gestacao_cobertura_monta')->insert(
                            array_map(function ($m) use ($id, $now) {
                                return [
                                    'cobertura_id' => (int) $id,
                                    'ordem' => (int) ($m['ordem'] ?? 1),
                                    'tipo' => (string) ($m['tipo'] ?? ''),
                                    'macho_id' => empty($m['macho_id']) ? null : (int) $m['macho_id'],
                                    'semen' => empty($m['semen']) ? null : (string) $m['semen'],
                                    'data' => (string) ($m['data'] ?? ''),
                                    'hora' => (string) ($m['hora'] ?? ''),
                                    'usuario_id' => empty($m['usuario_id']) ? null : (int) $m['usuario_id'],
                                    'criado_em' => $now,
                                    'atualizado_em' => $now,
                                ];
                            }, $montas)
                        );
                    }
                }

                if (Schema::hasTable('gestacao_cobertura_semen')) {
                    DB::table('gestacao_cobertura_semen')->where('cobertura_id', $id)->delete();
                    if (count($semens) > 1) {
                        $now = now();
                        DB::table('gestacao_cobertura_semen')->insert(
                            array_map(function ($semen) use ($id, $now) {
                                return [
                                    'cobertura_id' => (int) $id,
                                    'semen' => (string) $semen,
                                    'criado_em' => $now,
                                    'atualizado_em' => $now,
                                ];
                            }, $semens)
                        );
                    }
                }

                if (Schema::hasTable('criterio_log')) {
                    DB::table('criterio_log')
                        ->where('evento', 'cobertura')
                        ->where('referencia_id', $id)
                        ->delete();

                    if (! empty($warnings)) {
                        $now = now();
                        $usuarioId = $payload['usuario_id'] ?? null;
                        DB::table('criterio_log')->insert([
                            'modulo' => 'gestacao',
                            'evento' => 'cobertura',
                            'referencia_id' => (int) $id,
                            'usuario_id' => $usuarioId === null ? null : (int) $usuarioId,
                            'femea_id' => (int) $payload['femea_id'],
                            'warnings' => json_encode(array_values($warnings), JSON_UNESCAPED_UNICODE),
                            'dados' => json_encode([
                                'data' => $payload['data'] ?? null,
                                'hora' => $payload['hora'] ?? null,
                                'peso_matriz' => $payload['peso_matriz'] ?? null,
                                'presenca_cio' => $payload['presenca_cio'] ?? null,
                            ], JSON_UNESCAPED_UNICODE),
                            'ocorrido_em' => $now,
                            'criado_em' => $now,
                            'atualizado_em' => $now,
                        ]);
                    }
                }

                if (Schema::hasTable('femea')) {
                    $refresh = function (int $femeaId) {
                        $last = DB::table('gestacao_cobertura')->where('femea_id', $femeaId)->max('data');
                        $update = [];
                        if (Schema::hasColumn('femea', 'data_cobertura')) {
                            $update['data_cobertura'] = $last ? Carbon::parse($last)->toDateString() : null;
                        }
                        if (Schema::hasColumn('femea', 'tipo_compra')) {
                            if ($last) {
                                $update['tipo_compra'] = 'matriz_gestante';
                            } else {
                                $current = DB::table('femea')->where('id', $femeaId)->value('tipo_compra');
                                if ((string) $current === 'matriz_gestante') {
                                    $update['tipo_compra'] = 'matriz_vazia';
                                }
                            }
                        }
                        if (! empty($update)) {
                            $update['atualizado_em'] = now();
                            DB::table('femea')->where('id', $femeaId)->update($update);
                        }
                    };

                    $refresh($newFemeaId);
                    if ($oldFemeaId !== $newFemeaId) {
                        $refresh($oldFemeaId);
                    }

                    $latestId = DB::table('gestacao_cobertura')
                        ->where('femea_id', $newFemeaId)
                        ->orderByDesc('data')
                        ->orderByDesc('hora')
                        ->value('id');

                    if ($latestId !== null && (int) $latestId === (int) $id) {
                        $updateFemea = [];
                        if (Schema::hasColumn('femea', 'localizacao') && $payload['localizacao'] !== null) {
                            $updateFemea['localizacao'] = $payload['localizacao'];
                        }
                        if (Schema::hasColumn('femea', 'baia') && $payload['baia'] !== null) {
                            $updateFemea['baia'] = $payload['baia'];
                        }
                        if (! empty($updateFemea)) {
                            $updateFemea['atualizado_em'] = now();
                            DB::table('femea')->where('id', $newFemeaId)->update($updateFemea);
                        }
                    }
                }

                if (Schema::hasTable('femea_movimento')) {
                    $now = now();
                    $idPrimaria = DB::table('femea')->where('id', $newFemeaId)->value('id_primaria');

                    DB::table('femea_movimento')
                        ->where('acao', 'cobertura')
                        ->where('observacoes', 'Cobertura #'.$id)
                        ->delete();

                    $mov = [
                        'femea_id' => (int) $newFemeaId,
                        'acao' => 'cobertura',
                        'data' => (string) ($payload['data'] ?? ''),
                        'valor' => null,
                        'peso' => $payload['peso_matriz'] ?? null,
                        'fornecedor_id' => null,
                        'observacoes' => 'Cobertura #'.$id,
                    ];

                    if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                        $mov['femea_id_primaria'] = $idPrimaria;
                    }
                    if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                        $mov['criado_em'] = $now;
                    }
                    if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                        $mov['atualizado_em'] = $now;
                    }

                    DB::table('femea_movimento')->insert($mov);
                }

                if (Schema::hasTable('macho_movimento')) {
                    DB::table('macho_movimento')
                        ->where('acao', 'cobertura')
                        ->where('observacoes', 'like', 'Cobertura #'.$id.'%')
                        ->delete();

                    $machoIds = [];
                    if (! empty($payload['macho_id'])) {
                        $machoIds[] = (int) $payload['macho_id'];
                    }
                    foreach ($montas as $m) {
                        if (($m['tipo'] ?? null) === 'macho' && ! empty($m['macho_id'])) {
                            $machoIds[] = (int) $m['macho_id'];
                        }
                    }

                    $machoIds = array_values(array_unique(array_filter($machoIds)));
                    if (! empty($machoIds)) {
                        $now = now();
                        $idPrimaria = DB::table('femea')->where('id', $newFemeaId)->value('id_primaria');
                        $obs = 'Cobertura #'.$id.' - Matriz '.(string) $idPrimaria;
                        foreach ($machoIds as $mid) {
                            $movM = [
                                'macho_id' => (int) $mid,
                                'acao' => 'cobertura',
                                'data' => (string) ($payload['data'] ?? ''),
                                'valor' => null,
                                'peso' => null,
                                'fornecedor_id' => null,
                                'causa_id' => null,
                                'comprador' => null,
                                'observacoes' => $obs,
                            ];

                            if (Schema::hasColumn('macho_movimento', 'criado_em')) {
                                $movM['criado_em'] = $now;
                            }
                            if (Schema::hasColumn('macho_movimento', 'atualizado_em')) {
                                $movM['atualizado_em'] = $now;
                            }

                            DB::table('macho_movimento')->insert($movM);
                        }
                    }
                }
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return response()->json([
                    'message' => "Para registrar o histórico no plantel, é necessário atualizar os ENUMs de movimentos para incluir as novas ações.",
                    'sql' => [$sqlEnum, $sqlEnumMacho],
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Cobertura alterada com sucesso!',
            'warnings' => $warnings,
        ]);
    }

    private function criteriosWarnings(array $payload): array
    {
        if (! Schema::hasTable('meta')) {
            return [];
        }

        $enabled = (string) (DB::table('meta')->where('chave', 'criterios_enabled')->value('valor') ?? '0');
        $enabled = in_array($enabled, ['1', 'true', 'TRUE', 'on', 'yes'], true);
        if (! $enabled) {
            return [];
        }

        $warnings = [];

        $idadeMin = DB::table('meta')->where('chave', 'criterio_cobertura_idade_min_dias')->value('valor');
        $idadeMax = DB::table('meta')->where('chave', 'criterio_cobertura_idade_max_dias')->value('valor');
        $pesoMin = DB::table('meta')->where('chave', 'criterio_cobertura_peso_min_kg')->value('valor');
        $pesoMax = DB::table('meta')->where('chave', 'criterio_cobertura_peso_max_kg')->value('valor');
        $cioReq = DB::table('meta')->where('chave', 'criterio_cobertura_presenca_cio')->value('valor');

        $idadeMin = $idadeMin === null || $idadeMin === '' ? null : (int) $idadeMin;
        $idadeMax = $idadeMax === null || $idadeMax === '' ? null : (int) $idadeMax;
        $pesoMin = $pesoMin === null || $pesoMin === '' ? null : (float) str_replace(',', '.', (string) $pesoMin);
        $pesoMax = $pesoMax === null || $pesoMax === '' ? null : (float) str_replace(',', '.', (string) $pesoMax);
        $cioReq = $cioReq === null || $cioReq === '' ? null : (string) $cioReq;

        if ($idadeMin !== null && $idadeMin <= 0) {
            $idadeMin = null;
        }
        if ($idadeMax !== null && $idadeMax <= 0) {
            $idadeMax = null;
        }
        if ($pesoMin !== null && $pesoMin <= 0) {
            $pesoMin = null;
        }
        if ($pesoMax !== null && $pesoMax <= 0) {
            $pesoMax = null;
        }

        $dataNascimento = DB::table('femea')->where('id', (int) $payload['femea_id'])->value('data_nascimento');
        if (($idadeMin !== null || $idadeMax !== null)) {
            if ($dataNascimento === null) {
                $warnings[] = 'Critério de idade: matriz sem data de nascimento cadastrada.';
            } else {
                $idadeDias = Carbon::parse($dataNascimento)->diffInDays(Carbon::parse($payload['data']));
                if ($idadeMin !== null && $idadeDias < $idadeMin) {
                    $warnings[] = "Critério de idade: {$idadeDias} dias (mínimo {$idadeMin}).";
                }
                if ($idadeMax !== null && $idadeDias > $idadeMax) {
                    $warnings[] = "Critério de idade: {$idadeDias} dias (máximo {$idadeMax}).";
                }
            }
        }

        if (($pesoMin !== null || $pesoMax !== null)) {
            $peso = $payload['peso_matriz'] ?? null;
            if ($peso === null) {
                $warnings[] = 'Critério de peso: informe o peso da matriz.';
            } else {
                $peso = (float) $peso;
                if ($pesoMin !== null && $peso < $pesoMin) {
                    $warnings[] = 'Critério de peso: '.$peso.' kg (mínimo '.$pesoMin.').';
                }
                if ($pesoMax !== null && $peso > $pesoMax) {
                    $warnings[] = 'Critério de peso: '.$peso.' kg (máximo '.$pesoMax.').';
                }
            }
        }

        if ($cioReq !== null && isset($payload['presenca_cio'])) {
            $cio = (string) $payload['presenca_cio'];
            if (in_array($cioReq, ['sim', 'nao'], true) && $cio !== $cioReq) {
                $warnings[] = 'Critério presença de cio: esperado '.($cioReq === 'sim' ? 'Sim' : 'Não').'.';
            }
        }

        return $warnings;
    }
}
