<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualizar o ENUM da coluna 'acao' para incluir 'cio' e 'salta_cio'
        DB::statement("ALTER TABLE femea_movimento MODIFY COLUMN acao ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o ENUM original (apenas se não houver registros com 'cio' ou 'salta_cio')
        DB::statement("ALTER TABLE femea_movimento MODIFY COLUMN acao ENUM('compra', 'morte', 'descarte', 'venda') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }
};
