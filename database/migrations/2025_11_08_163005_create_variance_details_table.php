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
        Schema::create('variance_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('analysis_id');
            $table->uuid('transaction_code_id')->nullable();
            $table->string('item_name');
            $table->decimal('baseline_amount', 15, 2)->default(0);
            $table->decimal('comparison_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->decimal('variance_percentage', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('analysis_id')->references('id')->on('variance_analysis')->onDelete('cascade');
            $table->foreign('transaction_code_id')->references('id')->on('transaction_codes')->onDelete('set null');

            $table->index('analysis_id');
            $table->index('transaction_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variance_details');
    }
};
