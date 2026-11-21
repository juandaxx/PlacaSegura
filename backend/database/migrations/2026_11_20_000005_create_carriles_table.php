<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->string('nombre', 50);
            $table->enum('tipo', ['entrada', 'salida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriles');
    }
};
