<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una sola franja diurna y una sola nocturna: el ajuste de horarios y el
     * calculo de la tarifa dependen de ello.
     */
    public function up(): void
    {
        Schema::table('franjas', function (Blueprint $table) {
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('franjas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
    }
};
