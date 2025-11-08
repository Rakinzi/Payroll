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
        Schema::create('leave_balance_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_balance_id');
            $table->decimal('old_balance_bf', 10, 3);
            $table->decimal('new_balance_bf', 10, 3);
            $table->decimal('old_balance_cf', 10, 3);
            $table->decimal('new_balance_cf', 10, 3);
            $table->uuid('adjusted_by');
            $table->string('adjustment_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('leave_balance_id');
            $table->index('adjusted_by');

            // Foreign keys
            $table->foreign('leave_balance_id')->references('id')->on('leave_balances')->onDelete('cascade');
            $table->foreign('adjusted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');
    }
};
