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
        Schema::create('default_transactions', function (Blueprint $table) {
            $table->id('default_id');
            $table->uuid('code_id');
            $table->unsignedBigInteger('period_id');
            $table->uuid('center_id');
            $table->enum('transaction_effect', ['+', '-']);
            $table->decimal('employee_amount', 15, 2);
            $table->decimal('employer_amount', 15, 2)->default(0);
            $table->decimal('hours_worked', 8, 2)->default(0);
            $table->enum('transaction_currency', ['ZWG', 'USD']);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('code_id')
                ->references('id')
                ->on('transaction_codes')
                ->onDelete('cascade');

            $table->foreign('period_id')
                ->references('period_id')
                ->on('payroll_accounting_periods')
                ->onDelete('cascade');

            $table->foreign('center_id')
                ->references('id')
                ->on('cost_centers')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['code_id', 'period_id', 'center_id', 'transaction_currency'], 'unique_default_transaction');

            // Indexes
            $table->index('period_id');
            $table->index('center_id');
            $table->index('transaction_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_transactions');
    }
};
