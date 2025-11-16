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
        Schema::create('bus_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained('buses')->cascadeOnDelete();
            $table->enum('command', ['start_trip', 'end_trip']); // Comando a ejecutar
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); // Quién solicitó el comando
            $table->text('error_message')->nullable(); // Si falla, guardamos el error
            $table->timestamp('executed_at')->nullable(); // Cuándo se ejecutó
            $table->timestamps();

            // Índices para mejorar consultas
            $table->index(['bus_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_commands');
    }
};
