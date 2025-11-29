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
        Schema::create('driver_time_records', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->onDelete('set null');
            $table->foreignId('trip_ida_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->foreignId('trip_vuelta_id')->nullable()->constrained('trips')->onDelete('set null');

            // Columna IDA
            $table->datetime('inicio_ida')->nullable();

            // Columna VUELTA
            $table->datetime('fin_ida')->nullable();
            $table->datetime('inicio_vuelta')->nullable();
            $table->datetime('fin_vuelta_estimado')->nullable();
            $table->datetime('fin_vuelta_real')->nullable();

            // Estado y retraso
            $table->enum('estado', ['en_curso', 'normal', 'retrasado'])->default('en_curso');
            $table->integer('tiempo_retraso_minutos')->nullable();

            // Control
            $table->boolean('es_ultimo_viaje')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_time_records');
    }
};
