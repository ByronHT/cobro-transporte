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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            // Relaciones con otras tablas
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->foreignId('bus_id')->nullable()->constrained('buses')->onDelete('set null');
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->onDelete('set null');

            // Detalles de la queja
            $table->text('reason'); // Motivo de la queja
            $table->string('photo_path')->nullable(); // Ruta de la foto (si existe)

            // Estado y metadatos
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_response')->nullable(); // Respuesta del admin
            $table->timestamp('reviewed_at')->nullable(); // Fecha de revisión
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin que revisó

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('passenger_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complaints');
    }
};
