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
        // Aditiva: no toca 'estado' (0/1), que sigue siendo la fuente de verdad
        // para ocupación de habitaciones, solapamiento y cancelación. Esta nueva
        // columna solo describe el ciclo de vida de la estadía mientras la
        // reserva está activa (estado = 1).
        Schema::table('reservas', function (Blueprint $table) {
            $table->enum('estado_estadia', ['pendiente', 'confirmada', 'check_in', 'check_out'])
                ->default('pendiente')
                ->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('estado_estadia');
        });
    }
};
