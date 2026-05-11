<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creche_compras', function (Blueprint $table) {
            $table->id();
            $table->date('data_compra');
            $table->foreignId('lote_id')->constrained('creche_lotes')->cascadeOnDelete();
            $table->string('localizacao', 120)->nullable();
            $table->unsignedInteger('quantidade');
            $table->decimal('peso_total', 10, 2);
            $table->date('data_nascimento');
            $table->decimal('valor_compra', 12, 2)->nullable();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedor');
            $table->string('nota_fiscal', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creche_compras');
    }
};

