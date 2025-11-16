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
        // Eliminar la clave foránea
        DB::statement('ALTER TABLE payment_events DROP FOREIGN KEY payment_events_trip_id_foreign');

        // Modificar la columna para que sea nullable
        DB::statement('ALTER TABLE payment_events MODIFY trip_id BIGINT UNSIGNED NULL');

        // Volver a agregar la clave foránea
        DB::statement('ALTER TABLE payment_events ADD CONSTRAINT payment_events_trip_id_foreign FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar la clave foránea
        DB::statement('ALTER TABLE payment_events DROP FOREIGN KEY payment_events_trip_id_foreign');

        // Volver a hacer la columna NOT NULL
        DB::statement('ALTER TABLE payment_events MODIFY trip_id BIGINT UNSIGNED NOT NULL');

        // Restaurar la clave foránea
        DB::statement('ALTER TABLE payment_events ADD CONSTRAINT payment_events_trip_id_foreign FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE');
    }
};
