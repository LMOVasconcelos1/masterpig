<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maternidade_desmame')) {
            return;
        }

        Schema::table('maternidade_desmame', function (Blueprint $table) {
            if (!Schema::hasColumn('maternidade_desmame', 'lote_destino')) {
                $table->string('lote_destino', 60)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'localizacao_destino')) {
                $table->string('localizacao_destino', 80)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'destino_matriz')) {
                $table->string('destino_matriz', 80)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'baia_matriz')) {
                $table->string('baia_matriz', 80)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'peso_matriz')) {
                $table->decimal('peso_matriz', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'escore_corporal')) {
                $table->string('escore_corporal', 30)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'caracteristicas_desmame')) {
                $table->string('caracteristicas_desmame', 500)->nullable();
            }
            if (!Schema::hasColumn('maternidade_desmame', 'funcionario')) {
                $table->string('funcionario', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('maternidade_desmame')) {
            return;
        }

        Schema::table('maternidade_desmame', function (Blueprint $table) {
            $cols = [
                'lote_destino',
                'localizacao_destino',
                'destino_matriz',
                'baia_matriz',
                'peso_matriz',
                'escore_corporal',
                'caracteristicas_desmame',
                'funcionario',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('maternidade_desmame', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
