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
        Schema::create('vehicle_benefit_bands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('engine_capacity_min'); // in cc
            $table->integer('engine_capacity_max')->nullable(); // NULL for top band
            $table->decimal('benefit_amount', 12, 2);
            $table->enum('currency', ['USD', 'ZWG']);
            $table->enum('period', ['monthly', 'annual']);
            $table->text('description')->nullable();
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
        Schema::dropIfExists('vehicle_benefit_bands');
    }
};
