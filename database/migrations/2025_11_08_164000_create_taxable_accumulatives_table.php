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
        Schema::create('taxable_accumulatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('generated_by');
            $table->integer('tax_year');
            $table->string('currency', 10);
            $table->decimal('total_taxable_income', 15, 2)->default(0);
            $table->decimal('total_tax_paid', 15, 2)->default(0);
            $table->decimal('total_outstanding_tax', 15, 2)->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('payroll_id');
            $table->index('tax_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxable_accumulatives');
    }
};
