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
        Schema::create('habitacion_servicio_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_habitacion_reserva')->constrained('habitacion_reservas')->onDelete('cascade');
            $table->foreignId('id_servicio_extra')->constrained('servicio_extras')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitacion_servicio_extras');
    }
};
