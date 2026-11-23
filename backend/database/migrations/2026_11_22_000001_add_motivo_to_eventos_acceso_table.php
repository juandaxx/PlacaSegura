<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decision tomada en el carril para cada lectura. Con ella se sabe que
     * vehiculos quedaron adentro (cupos, F-07) y que lecturas esperan la
     * validacion del operador (F-06).
     */
    public function up(): void
    {
        Schema::table('eventos_acceso', function (Blueprint $table) {
            $table->enum('motivo', ['OK', 'PASE_VIGENTE', 'LECTURA_MANUAL', 'SIN_CUPO', 'COBRO_PENDIENTE'])
                ->default('OK');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_acceso', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
