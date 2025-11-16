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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->string('card_uid')->nullable(); // UID de la tarjeta que intentó pagar
            $table->foreignId('card_id')->nullable()->constrained('cards')->onDelete('set null'); // Relación con tarjeta si existe
            $table->foreignId('passenger_id')->nullable()->constrained('users')->onDelete('set null'); // Pasajero si se identificó
            $table->enum('event_type', ['success', 'insufficient_balance', 'invalid_card', 'inactive_card', 'error'])->default('success');
            $table->decimal('amount', 10, 2)->nullable(); // Monto del pago o saldo disponible en caso de error
            $table->decimal('required_amount', 10, 2)->nullable(); // Monto requerido (tarifa)
            $table->text('message'); // Mensaje descriptivo del evento
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['trip_id', 'created_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_events');
    }
};
