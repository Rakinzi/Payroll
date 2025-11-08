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
        Schema::create('third_party_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id');
            $table->uuid('employee_id');
            $table->string('employee_name');
            $table->string('nat_id')->nullable();
            $table->decimal('contribution_amount', 15, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('third_party_reports')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index('report_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_details');
    }
};
