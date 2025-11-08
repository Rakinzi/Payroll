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
        Schema::create('tax_bands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('currency', ['USD', 'ZWG']);
            $table->enum('period', ['monthly', 'annual']);
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2)->nullable(); // NULL for top band
            $table->decimal('tax_rate', 5, 4); // e.g., 20.0000%
            $table->decimal('tax_amount', 12, 2)->default(0); // Fixed deduction
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['currency', 'period', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_bands');
    }
};
