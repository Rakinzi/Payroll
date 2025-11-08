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
        Schema::create('taxable_accumulative_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accumulative_id');
            $table->uuid('employee_id');
            $table->string('employee_name');
            $table->string('nat_id')->nullable();
            $table->decimal('ytd_taxable_income', 15, 2)->default(0);
            $table->decimal('ytd_tax_paid', 15, 2)->default(0);
            $table->decimal('outstanding_tax', 15, 2)->default(0);
            $table->decimal('current_month_income', 15, 2)->default(0);
            $table->decimal('current_month_tax', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('accumulative_id')->references('id')->on('taxable_accumulatives')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index('accumulative_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxable_accumulative_details');
    }
};
