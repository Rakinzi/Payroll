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
        // Add soft deletes to payslip_transactions
        Schema::table('payslip_transactions', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to accounting periods
        Schema::table('payroll_accounting_periods', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to default transactions
        Schema::table('default_transactions', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to custom transactions
        Schema::table('custom_transactions_tbl', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to leave balances
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to leave applications
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to transaction codes
        Schema::table('transaction_codes', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to departments
        Schema::table('departments', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to positions
        Schema::table('positions', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payroll_accounting_periods', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('default_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('custom_transactions_tbl', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('transaction_codes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
