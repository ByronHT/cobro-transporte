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
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('turno_id')->nullable()->after('driver_id')->constrained('turnos')->nullOnDelete();
            $table->enum('tipo_viaje', ['ida', 'vuelta'])->default('ida')->after('turno_id');
            $table->dateTime('hora_salida_programada')->nullable()->after('tipo_viaje');
            $table->dateTime('hora_salida_real')->nullable()->after('hora_salida_programada');
            $table->dateTime('hora_llegada_programada')->nullable()->after('hora_salida_real');
            $table->dateTime('hora_llegada_real')->nullable()->after('hora_llegada_programada');
            $table->boolean('finalizado_en_parada')->default(true)->after('status');
            $table->boolean('cambio_bus')->default(false)->after('finalizado_en_parada');
            $table->foreignId('nuevo_bus_id')->nullable()->after('cambio_bus')->constrained('buses')->nullOnDelete();
            $table->json('recorrido_gps')->nullable()->after('reporte');
            $table->decimal('total_recaudado', 10, 2)->default(0)->after('recorrido_gps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['turno_id']);
            $table->dropForeign(['nuevo_bus_id']);
            $table->dropColumn(['turno_id','tipo_viaje','hora_salida_programada','hora_salida_real','hora_llegada_programada','hora_llegada_real','finalizado_en_parada','cambio_bus','nuevo_bus_id','recorrido_gps','total_recaudado']);
        });
    }
};
