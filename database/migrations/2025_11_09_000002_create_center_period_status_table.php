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
        Schema::create('center_period_status', function (Blueprint $table) {
            $table->id('status_id');
            $table->unsignedBigInteger('period_id');
            $table->uuid('center_id');
            $table->enum('period_currency', ['ZWL', 'USD', 'DEFAULT'])->default('DEFAULT');
            $table->dateTime('period_run_date')->nullable();
            $table->dateTime('pay_run_date')->nullable();
            $table->boolean('is_closed_confirmed')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('period_id')
                ->references('period_id')
                ->on('payroll_accounting_periods')
                ->onDelete('cascade');

            $table->foreign('center_id')
                ->references('id')
                ->on('cost_centers')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['period_id', 'center_id']);

            // Indexes
            $table->index('period_currency');
            $table->index('period_run_date');
            $table->index('pay_run_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_period_status');
    }
};
