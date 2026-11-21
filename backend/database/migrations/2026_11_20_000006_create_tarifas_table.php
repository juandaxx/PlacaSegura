<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo');
            $table->foreignId('franja_id')->constrained('franjas');
            $table->decimal('valor', 12, 2);

            $table->unique(['sede_id', 'tipo_vehiculo_id', 'franja_id']);
        });

        // SQLite (usado en los tests) no permite agregar CHECK con ALTER TABLE.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tarifas ADD CONSTRAINT tarifas_valor_check CHECK (valor >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
