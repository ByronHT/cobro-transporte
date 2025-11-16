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
            // Eliminar la foreign key constraint primero
            $table->dropForeign(['card_id']);
            // Eliminar la columna card_id (un viaje no pertenece a una tarjeta)
            $table->dropColumn('card_id');
            // Eliminar la columna fare (se calcula de las transacciones)
            $table->dropColumn('fare');
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
            // Restaurar columnas si se revierte
            $table->foreignId('card_id')->nullable()->constrained('cards')->nullOnDelete();
            $table->decimal('fare', 10, 2)->nullable();
        });
    }
};
