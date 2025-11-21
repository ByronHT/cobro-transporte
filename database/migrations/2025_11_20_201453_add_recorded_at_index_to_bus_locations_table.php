<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Optimización para limpieza automática de datos GPS antiguos
     * - Agregar índice compuesto para búsquedas por fecha
     * - Mejorar performance de consultas de cleanup
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bus_locations', function (Blueprint $table) {
            // Índice compuesto para limpieza eficiente de registros antiguos
            // Permite DELETE rápido de registros por fecha
            $table->index('recorded_at', 'idx_recorded_at_cleanup');

            // Índice compuesto para búsquedas de buses activos recientes
            // Mejora queries de /api/admin/realtime/active-buses
            $table->index(['is_active', 'recorded_at'], 'idx_active_recorded');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bus_locations', function (Blueprint $table) {
            $table->dropIndex('idx_recorded_at_cleanup');
            $table->dropIndex('idx_active_recorded');
        });
    }
};
