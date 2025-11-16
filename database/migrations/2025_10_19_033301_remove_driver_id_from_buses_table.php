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
        Schema::table('buses', function (Blueprint $table) {
            // Eliminar la foreign key constraint primero
            $table->dropForeign(['driver_id']);
            // Eliminar la columna driver_id (los choferes se asignan temporalmente via bus_assignments)
            $table->dropColumn('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buses', function (Blueprint $table) {
            // Restaurar columna si se revierte
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
