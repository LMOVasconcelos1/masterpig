<?php

namespace App\Http\Controllers;

use App\Support\SchemaUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchemaUpdateController extends Controller
{
    /**
     * Exibe a tela de atualização do banco de dados.
     */
    public function index(Request $request): Response
    {
        $updater = new SchemaUpdater();
        $pending = $updater->pendingVersions();

        // Se não há migrações pendentes, redireciona para o dashboard
        if (empty($pending)) {
            return redirect()->route('dashboard');
        }

        return response()->view('schema-update.index', [
            'total' => count($pending),
            'firstVersion' => $pending[0]['version'] ?? '',
        ]);
    }

    /**
     * Retorna o status das migrações pendentes.
     */
    public function status(Request $request): JsonResponse
    {
        $updater = new SchemaUpdater();
        $pending = $updater->pendingVersions();
        $applied = $updater->appliedVersions();

        return response()->json([
            'pending' => count($pending),
            'applied' => count($applied),
            'nextVersion' => $pending[0]['version'] ?? null,
            'hasPending' => ! empty($pending),
        ]);
    }

    /**
     * Aplica a próxima migração pendente.
     */
    public function runNext(Request $request): JsonResponse
    {
        $updater = new SchemaUpdater();
        $pendingBefore = $updater->pendingVersions();

        if (empty($pendingBefore)) {
            return response()->json([
                'success' => true,
                'done' => true,
                'message' => 'Todas as migrações foram aplicadas.',
                'remaining' => 0,
            ]);
        }

        $result = $updater->applyNext();
        $remaining = count($updater->pendingVersions());

        return response()->json([
            'success' => $result['success'],
            'done' => $remaining === 0,
            'version' => $result['version'],
            'message' => $result['message'],
            'statements' => $result['statements'],
            'remaining' => $remaining,
        ]);
    }
}
