<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bus_inicial_id')->constrained('buses')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin_programada');
            $table->dateTime('hora_fin_real')->nullable();
            $table->enum('status', ['activo', 'finalizado', 'cancelado'])->default('activo');
            $table->integer('total_viajes_ida')->default(0);
            $table->integer('total_viajes_vuelta')->default(0);
            $table->decimal('total_recaudado', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['driver_id', 'fecha']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('turnos');
    }
};
