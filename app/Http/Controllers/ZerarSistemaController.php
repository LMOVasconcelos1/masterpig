<?php

namespace App\Http\Controllers;

use App\Support\TenantDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ZerarSistemaController extends Controller
{
    public function page(): View
    {
        return view('admin.ajustes.zerar');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cnpj' => ['required', 'string'],
        ]);

        $expected = (string) $request->session()->get('tenant_cnpj', '');
        $expectedDigits = TenantDatabase::normalizeCnpj($expected);
        $expectedDb = (string) $request->session()->get('tenant_db', '');
        $expectedUser = (string) $request->session()->get('tenant_user', '');

        $typedDigits = TenantDatabase::normalizeCnpj((string) $validated['cnpj']);

        if (! preg_match('/^\d{14}$/', $expectedDigits) || $typedDigits !== $expectedDigits) {
            return response()->json([
                'message' => 'Confirmação de CNPJ inválida.',
            ], 422);
        }

        $calcDb = TenantDatabase::databaseNameFromCnpj($expectedDigits);
        $calcUser = TenantDatabase::usernameFromCnpj($expectedDigits);
        if ($expectedDb === '' || $expectedUser === '' || $calcDb !== $expectedDb || $calcUser !== $expectedUser) {
            return response()->json([
                'message' => 'Não foi possível validar o banco deste CNPJ.',
            ], 422);
        }

        $tables = [
            'gestacao_perda',
            'gestacao_cobertura',
            'gestacao_cio',
            'gestacao_salta_cio',
            'criterio_log',
            'femea_movimento',
            'macho_movimento',
            'femea',
            'macho',
        ];

        try {
            TenantDatabase::applyDatabase($expectedDb, $expectedUser);
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement('TRUNCATE TABLE `'.$table.'`');
                }
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível zerar o sistema.',
            ], 500);
        } finally {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'Sistema zerado com sucesso. Todos os animais e lançamentos foram removidos.',
        ]);
    }
}
