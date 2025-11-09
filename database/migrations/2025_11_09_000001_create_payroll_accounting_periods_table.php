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
        Schema::create('payroll_accounting_periods', function (Blueprint $table) {
            $table->id('period_id');
            $table->uuid('payroll_id');
            $table->string('month_name', 20); // January, February, etc.
            $table->integer('period_year');
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            // Foreign keys
            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->onDelete('cascade');

            // Indexes
            $table->index(['payroll_id', 'period_year', 'month_name'], 'payroll_period_lookup');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_accounting_periods');
    }
};
