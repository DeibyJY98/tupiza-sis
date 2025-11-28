<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            //atributos de mi tabla de base de datos
            $table->id(); //primary key, autoincremental, tipo biginteger
            $table->string("nombre");
            $table->timestamps(); //create_at, update_at
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
