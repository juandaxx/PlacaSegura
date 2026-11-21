<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->constrained('cobros');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('medio', 30); // ej: efectivo, tarjeta, transferencia
            $table->string('referencia', 100)->unique();
            $table->decimal('valor', 12, 2);
            $table->timestamp('fecha_hora')->useCurrent();
            $table->timestamp('pase_hasta'); // limite para salir despues de pagar
        });

        // SQLite (usado en los tests) no permite agregar CHECK con ALTER TABLE.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_valor_check CHECK (valor > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
