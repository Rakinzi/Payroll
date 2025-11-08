<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('leave_entitlement', 10, 3)->default(30)->after('termination_date');
            $table->decimal('leave_accrual_rate', 10, 3)->default(2.5)->after('leave_entitlement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['leave_entitlement', 'leave_accrual_rate']);
        });
    }
};
