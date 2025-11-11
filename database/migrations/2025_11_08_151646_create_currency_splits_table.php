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
        Schema::create('currency_splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('center_id');
            $table->decimal('zwg_percentage', 5, 2)->default(0);
            $table->decimal('usd_percentage', 5, 2)->default(100);
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('center_id')->references('id')->on('cost_centers')->onDelete('cascade');

            // Indexes
            $table->index('center_id');
            $table->index('effective_date');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_splits');
    }
};
