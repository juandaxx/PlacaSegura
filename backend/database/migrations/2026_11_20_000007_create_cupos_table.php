<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo');
            $table->integer('total');

            $table->unique(['sede_id', 'tipo_vehiculo_id']);
        });

        // SQLite (usado en los tests) no permite agregar CHECK con ALTER TABLE.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cupos ADD CONSTRAINT cupos_total_check CHECK (total >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cupos');
    }
};
