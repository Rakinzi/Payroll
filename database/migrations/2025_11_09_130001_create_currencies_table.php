<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id('currency_id');
            $table->string('code', 10)->unique(); // ZWG, USD, EUR, etc.
            $table->string('name'); // Zimbabwe Gold, US Dollar, etc.
            $table->string('symbol', 10); // ZWG, $, €, etc.
            $table->decimal('exchange_rate', 15, 4)->default(1.0000); // Exchange rate to base currency
            $table->boolean('is_base')->default(false); // Is this the base currency
            $table->boolean('is_active')->default(true);
            $table->integer('decimal_places')->default(2);
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('is_base');
        });

        // Insert default currencies
        DB::table('currencies')->insert([
            [
                'code' => 'ZWG',
                'name' => 'Zimbabwe Gold',
                'symbol' => 'ZWG',
                'exchange_rate' => 1.0000,
                'is_base' => true,
                'is_active' => true,
                'decimal_places' => 2,
                'description' => 'Zimbabwe Gold - Base Currency',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0000,
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'description' => 'United States Dollar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
