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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('from_currency', 3); // USD, ZWG, ZWG, etc.
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6); // Precise exchange rate
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['from_currency', 'to_currency']);
            $table->index('effective_date');
            $table->index('is_active');

            // Unique constraint: only one active rate per currency pair per date
            $table->unique(['from_currency', 'to_currency', 'effective_date', 'deleted_at'], 'exchange_rates_unique_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
