<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('tarifa_id')->constrained('tarifas');
            $table->foreignId('evento_ingreso_id')->constrained('eventos_acceso');
            $table->string('placa', 10);
            $table->date('dia_operativo');
            $table->decimal('valor', 12, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'anulado'])->default('pendiente');

            $table->unique(['placa', 'sede_id', 'dia_operativo']);
        });

        // SQLite (usado en los tests) no permite agregar CHECK con ALTER TABLE.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cobros ADD CONSTRAINT cobros_valor_check CHECK (valor >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
