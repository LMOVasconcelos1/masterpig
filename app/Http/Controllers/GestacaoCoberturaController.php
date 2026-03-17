<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $items = $query->get()->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'matriz' => (string) $row->id_primaria,
                'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'macho' => $row->macho_id_primaria === null ? null : (string) $row->macho_id_primaria,
                'semen' => $row->semen === null ? null : (string) $row->semen,
                'data' => Carbon::parse($row->data)->format('d/m/Y'),
                'hora' => $row->hora === null ? null : (string) $row->hora,
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
            'data' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'presenca_cio' => ['required', 'in:sim'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'peso_matriz' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'caracteristicas' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

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

        $cioAuto = 'nao';
        if (Schema::hasTable('meta')) {
            $raw = DB::table('meta')->where('chave', 'criterio_registro_cio_automatico')->value('valor');
            if ($raw !== null && trim((string) $raw) !== '') {
                $cioAuto = (string) $raw;
            }
        }
        $cioAutoEnabled = in_array(mb_strtolower(trim((string) $cioAuto)), ['1', 'true', 'on', 'yes', 'sim'], true);

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
            if ($cycles < $coberturaCiclosMin) {
                return response()->json([
                    'message' => "Cobertura permitida a partir de {$coberturaCiclosMin} ciclos.",
                ], 422);
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

        if ($cioWindowStart) {
            $cioQuery->where('data', '>=', $cioWindowStart->toDateString());
        }

        $lastCio = $cioQuery->max('data');

        if (! $lastCio) {
            if ($cioAutoEnabled && $cioWindowStart) {
                $payload = [
                    'femea_id' => (int) $validated['femea_id'],
                    'data' => $dataCobertura->toDateString(),
                ];
                if (Schema::hasColumn('gestacao_cio', 'criado_em')) {
                    $payload['criado_em'] = now();
                }
                if (Schema::hasColumn('gestacao_cio', 'atualizado_em')) {
                    $payload['atualizado_em'] = now();
                }

                $exists = DB::table('gestacao_cio')
                    ->where('femea_id', (int) $validated['femea_id'])
                    ->where('data', $dataCobertura->toDateString())
                    ->exists();

                if (! $exists) {
                    DB::table('gestacao_cio')->insert($payload);
                }

                $lastCio = $dataCobertura->toDateString();
            } else {
                return response()->json([
                    'message' => 'A fêmea precisa estar em cio. Registre o cio antes da cobertura.',
                ], 422);
            }
        }

        $dataCio = Carbon::parse($lastCio)->startOfDay();
        if ($dataCio->diffInDays($dataCobertura) > $cioDias) {
            return response()->json([
                'message' => 'A fêmea precisa estar em cio para registrar cobertura.',
            ], 422);
        }

        $semen = trim((string) ($validated['semen'] ?? ''));
        $machoId = $validated['macho_id'] ?? null;

        if ($machoId === null && $semen === '') {
            return response()->json([
                'message' => 'Informe o macho ou o sêmen utilizado.',
            ], 422);
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
            'semen' => $semen === '' ? null : $semen,
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

        $warnings = $this->criteriosWarnings($payload);

        $coberturaId = null;

        DB::transaction(function () use ($payload, $warnings, &$coberturaId) {
            $coberturaId = (int) DB::table('gestacao_cobertura')->insertGetId($payload);

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
