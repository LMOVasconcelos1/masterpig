<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CriteriosLogsController extends Controller
{
    public function page()
    {
        return view('admin.ajustes.criterios_logs');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('criterio_log')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela criterio_log não existe no banco. Execute o banco_versions/0.7.sql.',
            ]);
        }

        $limit = max(1, min(500, (int) $request->query('limit', 100)));
        $evento = trim((string) $request->query('evento', ''));
        $usuarioId = $request->query('usuario_id');
        $inicio = $request->query('inicio');
        $fim = $request->query('fim');

        $query = DB::table('criterio_log as cl')
            ->leftJoin('usuario as u', 'u.id', '=', 'cl.usuario_id')
            ->leftJoin('femea as f', 'f.id', '=', 'cl.femea_id')
            ->select([
                'cl.id',
                'cl.modulo',
                'cl.evento',
                'cl.referencia_id',
                'cl.ocorrido_em',
                'cl.warnings',
                'u.nome as usuario_nome',
                'f.id_primaria as femea_id_primaria',
            ])
            ->orderByDesc('cl.ocorrido_em')
            ->limit($limit);

        if ($evento !== '') {
            $query->where('cl.evento', $evento);
        }

        if (is_numeric($usuarioId)) {
            $query->where('cl.usuario_id', (int) $usuarioId);
        }

        if (! empty($inicio)) {
            $query->whereDate('cl.ocorrido_em', '>=', Carbon::parse($inicio)->toDateString());
        }

        if (! empty($fim)) {
            $query->whereDate('cl.ocorrido_em', '<=', Carbon::parse($fim)->toDateString());
        }

        $items = $query->get()->map(function ($row) {
            $warnings = [];
            $raw = $row->warnings === null ? '' : (string) $row->warnings;
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $warnings = array_values(array_filter(array_map('strval', $decoded), fn ($v) => trim($v) !== ''));
                } else {
                    $warnings = array_values(array_filter(array_map('trim', preg_split("/\r?\n/", $raw) ?: []), fn ($v) => $v !== ''));
                }
            }

            return [
                'id' => (int) $row->id,
                'modulo' => (string) $row->modulo,
                'evento' => (string) $row->evento,
                'referencia_id' => $row->referencia_id === null ? null : (int) $row->referencia_id,
                'ocorrido_em' => $row->ocorrido_em === null ? null : Carbon::parse($row->ocorrido_em)->format('d/m/Y H:i'),
                'usuario' => $row->usuario_nome === null ? null : (string) $row->usuario_nome,
                'matriz' => $row->femea_id_primaria === null ? null : (string) $row->femea_id_primaria,
                'warnings' => $warnings,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }
}
