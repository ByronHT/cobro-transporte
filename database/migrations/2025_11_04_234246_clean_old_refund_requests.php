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
        // Eliminar todas las solicitudes de devolución pendientes del flujo antiguo
        // (las que fueron creadas por el chofer, no por el pasajero)
        DB::table('refund_requests')
            ->where('status', 'pending')
            ->whereNotNull('created_at')
            ->delete();

        // También limpiar las verificaciones asociadas si las hay
        DB::table('refund_verifications')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('refund_requests')
                      ->whereColumn('refund_verifications.refund_request_id', 'refund_requests.id');
            })
            ->delete();
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
