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
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('status');
            $table->text('reversal_reason')->nullable()->after('is_reversed');
            $table->timestamp('reversed_at')->nullable()->after('reversal_reason');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->after('reversed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropForeign(['reversed_by']);
            $table->dropColumn(['is_reversed', 'reversal_reason', 'reversed_at', 'reversed_by']);
        });
    }
};
