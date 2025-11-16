<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Eliminar todas las transacciones de devolución con monto positivo
        // (estas fueron creadas con el flujo antiguo antes de la corrección)
        DB::table('transactions')
            ->where('type', 'refund')
            ->where('amount', '>', 0)
            ->delete();

        // Nota: Las nuevas transacciones de refund se crearán correctamente
        // con monto negativo cuando el chofer apruebe una solicitud de devolución
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No se puede revertir la eliminación de datos
    }
};
