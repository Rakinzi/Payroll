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
        Schema::create('tax_cell_accumulative_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cell_accumulative_id');
            $table->uuid('employee_id');
            $table->string('employee_name');
            $table->string('nat_id')->nullable();
            $table->string('tax_bracket');
            $table->decimal('bracket_min', 15, 2)->default(0);
            $table->decimal('bracket_max', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('ytd_income_in_bracket', 15, 2)->default(0);
            $table->decimal('ytd_tax_in_bracket', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('cell_accumulative_id')->references('id')->on('tax_cell_accumulatives')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index('cell_accumulative_id');
            $table->index('employee_id');
            $table->index('tax_bracket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_cell_accumulative_details');
    }
};
