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
        Schema::create('transaction_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code_number', 3)->unique(); // 3-digit code
            $table->string('code_name');
            $table->enum('code_category', ['Earning', 'Deduction', 'Contribution']);
            $table->boolean('is_benefit')->default(false);
            $table->decimal('code_amount', 10, 2)->nullable(); // Fixed amount in USD
            $table->decimal('minimum_threshold', 10, 2)->nullable();
            $table->decimal('maximum_threshold', 10, 2)->nullable();
            $table->decimal('code_percentage', 5, 4)->nullable(); // e.g., 15.5000%
            $table->boolean('is_editable')->default(true); // System vs user-defined
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_codes');
    }
};
