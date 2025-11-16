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
        Schema::create('refund_verifications', function (Blueprint $table) {
            $table->id();

            // Relación con la solicitud de devolución
            $table->foreignId('refund_request_id')->constrained('refund_requests')->onDelete('cascade');

            // Acción realizada: approved, rejected
            $table->enum('action', ['approved', 'rejected']);

            // Información del usuario que realizó la acción
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pasajero que verificó
            $table->string('ip_address')->nullable(); // IP desde donde se verificó
            $table->text('user_agent')->nullable(); // Navegador/dispositivo

            // Datos adicionales
            $table->text('comments')->nullable(); // Comentarios del pasajero (opcional)

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
        Schema::dropIfExists('refund_verifications');
    }
};
