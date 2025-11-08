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
        Schema::create('nec_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('grade_name')->unique();
            $table->uuid('t_code_id'); // Transaction code foreign key
            $table->enum('contribution', ['Amount', 'Percentage']);
            $table->decimal('employee_contr_amount', 10, 2)->nullable();
            $table->decimal('employer_contr_amount', 10, 2)->nullable();
            $table->decimal('employee_contr_percentage', 5, 4)->nullable();
            $table->decimal('employer_contr_percentage', 5, 4)->nullable();
            $table->decimal('min_threshold', 12, 2)->nullable();
            $table->decimal('max_threshold', 12, 2)->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('t_code_id')->references('id')->on('transaction_codes')->onDelete('cascade');
        });

        // Pivot table for employee-grade relationships
        Schema::create('nec_grades_employees', function (Blueprint $table) {
            $table->uuid('grade_id');
            $table->uuid('employee_id');
            $table->timestamps();

            $table->foreign('grade_id')->references('id')->on('nec_grades')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->primary(['grade_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nec_grades_employees');
        Schema::dropIfExists('nec_grades');
    }
};
