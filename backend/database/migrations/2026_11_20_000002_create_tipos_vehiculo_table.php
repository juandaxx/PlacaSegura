<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('patron_placa', 100); // expresion regular, ej: ^[A-Z]{3}[0-9]{3}$
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_vehiculo');
    }
};
