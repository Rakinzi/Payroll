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
        Schema::create('retirement_warnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('generated_by');
            $table->integer('warning_threshold_months')->default(12);
            $table->integer('total_warnings')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('payroll_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_warnings');
    }
};
