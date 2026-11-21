<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franjas', function (Blueprint $table) {
            $table->id();
            $table->enum('nombre', ['diurna', 'nocturna']);
            $table->time('hora_inicio');
            $table->time('hora_fin'); // la franja nocturna puede cruzar la medianoche
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franjas');
    }
};
