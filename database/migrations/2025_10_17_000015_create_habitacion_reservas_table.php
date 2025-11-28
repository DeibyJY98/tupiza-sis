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
        Schema::create('habitacion_reservas', function (Blueprint $table) {
            $table->id();
            $table->integer('monto');
            $table->foreignId('id_reserva')->constrained('reservas')->onDelete('cascade');
            $table->foreignId('id_habitacion')->constrained('habitacions')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitacion_reservas');
    }
};
