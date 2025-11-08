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
        Schema::create('employee_requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('generated_by');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_active_employees')->default(0);
            $table->integer('total_terminated')->default(0);
            $table->integer('total_hired')->default(0);
            $table->decimal('turnover_rate', 5, 2)->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('payroll_id');
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_requisitions');
    }
};
