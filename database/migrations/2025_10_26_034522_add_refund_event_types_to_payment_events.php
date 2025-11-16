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
        DB::statement("ALTER TABLE payment_events MODIFY COLUMN event_type ENUM('success', 'insufficient_balance', 'invalid_card', 'inactive_card', 'error', 'recharge', 'refund_approved', 'refund_rejected', 'refund_completed') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE payment_events MODIFY COLUMN event_type ENUM('success', 'insufficient_balance', 'invalid_card', 'inactive_card', 'error', 'recharge') NOT NULL");
    }
};
