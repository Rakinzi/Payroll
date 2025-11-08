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
        Schema::create('retirement_warning_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warning_id');
            $table->uuid('employee_id');
            $table->string('employee_name');
            $table->string('nat_id')->nullable();
            $table->date('date_of_birth');
            $table->integer('current_age');
            $table->date('hire_date');
            $table->integer('years_of_service');
            $table->date('projected_retirement_date');
            $table->integer('months_to_retirement');
            $table->enum('warning_status', ['approaching', 'imminent', 'overdue'])->default('approaching');
            $table->timestamps();

            $table->foreign('warning_id')->references('id')->on('retirement_warnings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index('warning_id');
            $table->index('employee_id');
            $table->index('warning_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_warning_details');
    }
};
