<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_habitacion_caracteristicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_habitacion')->constrained('tipo_habitacions')->onDelete('cascade');
            $table->foreignId('id_caracteristica')->constrained('caracteristicas')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_habitacion_caracteristicas');
    }
};
