<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('transactions', function (Blueprint $table) {
$table->id();
$table->enum('type', ['fare','recharge']);
$table->decimal('amount', 10, 2);
$table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
$table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
$table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
$table->foreignId('bus_id')->nullable()->constrained('buses')->nullOnDelete();
$table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
$table->string('description')->nullable();
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('transactions'); }
};