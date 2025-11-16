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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('card_id')->constrained('cards')->onDelete('cascade');

            // Datos de la solicitud
            $table->decimal('amount', 10, 2); // Monto a devolver
            $table->text('reason'); // Razón de la devolución (ej: "Cobro duplicado")
            $table->string('card_uid'); // UID de la tarjeta para búsqueda

            // Estados: pending, verified, completed, rejected, cancelled
            $table->enum('status', ['pending', 'verified', 'completed', 'rejected', 'cancelled'])->default('pending');

            // Token de verificación único
            $table->string('verification_token')->unique();

            // Fechas importantes
            $table->timestamp('verified_at')->nullable(); // Cuando el pasajero verificó
            $table->timestamp('completed_at')->nullable(); // Cuando se completó la devolución
            $table->timestamp('expires_at')->nullable(); // Expiración del token (24 horas)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refund_requests');
    }
};
