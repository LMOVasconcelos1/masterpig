<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creche_lotes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->text('caracteristicas')->nullable();
            $table->string('situacao', 20)->default('aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creche_lotes');
    }
};

