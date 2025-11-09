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
        Schema::create('custom_transactions_tbl', function (Blueprint $table) {
            $table->id('custom_id');
            $table->uuid('center_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('worked_hours', 8, 2);
            $table->decimal('base_hours', 8, 2)->default(176.00);
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->boolean('use_basic')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('center_id')
                ->references('id')
                ->on('cost_centers')
                ->onDelete('cascade');

            $table->foreign('period_id')
                ->references('period_id')
                ->on('payroll_accounting_periods')
                ->onDelete('cascade');

            // Indexes
            $table->index('period_id');
            $table->index('center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_transactions_tbl');
    }
};
