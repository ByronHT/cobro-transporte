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
        // Agregar 'refund_reversal' al ENUM de tipos de transacción
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('fare', 'recharge', 'refund', 'refund_reversal') NOT NULL");

        // Agregar passenger_id si no existe
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'passenger_id')) {
                $table->foreignId('passenger_id')->nullable()->after('driver_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('fare', 'recharge', 'refund') NOT NULL");

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'passenger_id')) {
                $table->dropForeign(['passenger_id']);
                $table->dropColumn('passenger_id');
            }
        });
    }
};
