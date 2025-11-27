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
        Schema::table('rutas', function (Blueprint $table) {
            $table->string('linea_numero', 50)->nullable()->after('id');
            $table->text('ruta_ida_descripcion')->nullable()->after('descripcion');
            $table->json('ruta_ida_waypoints')->nullable()->after('ruta_ida_descripcion');
            $table->text('ruta_vuelta_descripcion')->nullable()->after('ruta_ida_waypoints');
            $table->json('ruta_vuelta_waypoints')->nullable()->after('ruta_vuelta_descripcion');
            $table->decimal('tarifa_adulto', 10, 2)->default(2.30)->after('tarifa_base');
            $table->decimal('tarifa_descuento', 10, 2)->default(1.00)->after('tarifa_adulto');
            $table->boolean('activa')->default(true)->after('tarifa_descuento');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn(['linea_numero','ruta_ida_descripcion','ruta_ida_waypoints','ruta_vuelta_descripcion','ruta_vuelta_waypoints','tarifa_adulto','tarifa_descuento','activa']);
        });
    }
};
