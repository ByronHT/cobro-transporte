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
        Schema::create('bus_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained('buses')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->decimal('latitude', 10, 8); // Precisión GPS: ej: -17.78345678
            $table->decimal('longitude', 11, 8); // Precisión GPS: ej: -63.18234567
            $table->decimal('speed', 5, 2)->nullable(); // Velocidad en km/h
            $table->decimal('heading', 5, 2)->nullable(); // Dirección en grados (0-360)
            $table->decimal('accuracy', 6, 2)->nullable(); // Precisión del GPS en metros
            $table->boolean('is_active')->default(true); // Si el bus está en viaje activo
            $table->timestamp('recorded_at'); // Timestamp del GPS
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['bus_id', 'recorded_at']);
            $table->index(['trip_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']); // Para búsquedas por proximidad
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_locations');
    }
};
