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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('payroll_id');
            $table->string('period', 50); // e.g., "January 2025"
            $table->integer('year');
            $table->decimal('balance_bf', 10, 3)->default(0); // Balance Brought Forward
            $table->decimal('balance_cf', 10, 3)->default(0); // Balance Carried Forward
            $table->decimal('days_accrued', 10, 3)->default(0);
            $table->decimal('days_taken', 10, 3)->default(0);
            $table->decimal('days_adjusted', 10, 3)->default(0);
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('payroll_id');
            $table->index(['year', 'period']);
            $table->unique(['employee_id', 'payroll_id', 'period', 'year']);

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
