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
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('payroll_id');
            $table->uuid('created_by');
            $table->string('payslip_number')->unique();
            $table->integer('period_month');
            $table->integer('period_year');
            $table->date('payment_date');
            $table->enum('status', ['draft', 'finalized', 'distributed', 'cancelled'])->default('draft');

            // Totals in ZWG
            $table->decimal('gross_salary_zwg', 15, 2)->default(0);
            $table->decimal('total_deductions_zwg', 15, 2)->default(0);
            $table->decimal('net_salary_zwg', 15, 2)->default(0);

            // Totals in USD
            $table->decimal('gross_salary_usd', 15, 2)->default(0);
            $table->decimal('total_deductions_usd', 15, 2)->default(0);
            $table->decimal('net_salary_usd', 15, 2)->default(0);

            // Year-to-date totals
            $table->decimal('ytd_gross_zwg', 15, 2)->default(0);
            $table->decimal('ytd_gross_usd', 15, 2)->default(0);
            $table->decimal('ytd_paye_zwg', 15, 2)->default(0);
            $table->decimal('ytd_paye_usd', 15, 2)->default(0);

            // Exchange rate used
            $table->decimal('exchange_rate', 10, 4)->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('employee_id');
            $table->index('payroll_id');
            $table->index('status');
            $table->index(['period_month', 'period_year']);
            $table->index(['employee_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
