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
        Schema::create('payslip_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payslip_id');
            $table->uuid('transaction_code_id')->nullable();
            $table->string('description');
            $table->enum('transaction_type', ['earning', 'deduction']);
            $table->integer('display_order')->default(0);

            // Amounts in ZWG
            $table->decimal('amount_zwg', 15, 2)->default(0);

            // Amounts in USD
            $table->decimal('amount_usd', 15, 2)->default(0);

            // Flags
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_manual')->default(false);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payslip_id')->references('id')->on('payslips')->onDelete('cascade');
            $table->foreign('transaction_code_id')->references('id')->on('transaction_codes')->onDelete('set null');

            $table->index('payslip_id');
            $table->index('transaction_type');
            $table->index('transaction_code_id');
            $table->index(['payslip_id', 'transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_transactions');
    }
};
