<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_acceso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carril_id')->constrained('carriles');
            $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios'); // nulo en lecturas automaticas (LPR)
            $table->string('placa', 10);
            $table->enum('tipo', ['ingreso', 'salida']);
            $table->decimal('confianza', 5, 2)->nullable(); // confianza del LPR (0-100); nula en lecturas manuales
            $table->boolean('lectura_manual')->default(false);
            $table->timestamp('fecha_hora')->useCurrent();
        });

        // SQLite (usado en los tests) no permite agregar CHECK con ALTER TABLE.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE eventos_acceso ADD CONSTRAINT eventos_acceso_confianza_check CHECK (confianza IS NULL OR confianza BETWEEN 0 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_acceso');
    }
};
