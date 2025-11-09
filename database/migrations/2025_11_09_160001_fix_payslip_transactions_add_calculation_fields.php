<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds fields needed for:
     * - Short time/overtime calculations (days, hours, rates)
     * - Proper calculation persistence
     * - Manual overrides tracking
     */
    public function up(): void
    {
        Schema::table('payslip_transactions', function (Blueprint $table) {
            // Calculation input fields (for short time, overtime, etc.)
            $table->decimal('days', 8, 2)->nullable()
                ->comment('Number of days (for short time, overtime, etc.)')
                ->after('amount_usd');

            $table->decimal('hours', 8, 2)->nullable()
                ->comment('Number of hours (for overtime, etc.)')
                ->after('days');

            $table->decimal('rate', 12, 2)->nullable()
                ->comment('Rate per day/hour')
                ->after('hours');

            $table->decimal('quantity', 12, 2)->nullable()
                ->comment('General quantity field for calculations')
                ->after('rate');

            // Calculation metadata
            $table->enum('calculation_basis', ['days', 'hours', 'amount', 'percentage'])->nullable()
                ->comment('How this transaction was calculated')
                ->after('quantity');

            $table->boolean('is_calculated')->default(false)
                ->comment('Was this auto-calculated or manually entered?')
                ->after('calculation_basis');

            $table->boolean('manual_override')->default(false)
                ->comment('Was amount manually overridden after calculation?')
                ->after('is_calculated');

            $table->json('calculation_metadata')->nullable()
                ->comment('Stores calculation details for audit/debugging')
                ->after('manual_override');
        });

        // Add indexes for performance
        Schema::table('payslip_transactions', function (Blueprint $table) {
            $table->index(['payslip_id', 'transaction_type']);
            $table->index(['payslip_id', 'is_manual']);
            $table->index('calculation_basis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_transactions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['payslip_id', 'transaction_type']);
            $table->dropIndex(['payslip_id', 'is_manual']);
            $table->dropIndex(['calculation_basis']);

            // Drop columns
            $table->dropColumn([
                'days',
                'hours',
                'rate',
                'quantity',
                'calculation_basis',
                'is_calculated',
                'manual_override',
                'calculation_metadata',
            ]);
        });
    }
};
