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
        Schema::create('cost_breakdown_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cache_id');
            $table->string('category_name');
            $table->enum('category_type', ['department', 'designation', 'code', 'leave_type']);
            $table->decimal('zwg_amount', 15, 2)->default(0);
            $table->decimal('usd_amount', 15, 2)->default(0);
            $table->integer('employee_count')->default(0);
            $table->decimal('percentage_of_total', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('cache_id')->references('id')->on('cost_analysis_cache')->onDelete('cascade');

            $table->index('cache_id');
            $table->index('category_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_breakdown_details');
    }
};
