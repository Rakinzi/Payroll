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
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('rate', 15, 4);
            $table->decimal('previous_rate', 15, 4)->nullable();
            $table->string('source', 50)->default('manual'); // manual, api, system
            $table->unsignedBigInteger('updated_by')->nullable(); // User ID
            $table->text('notes')->nullable();
            $table->timestamp('effective_date');
            $table->timestamps();

            // Foreign keys
            $table->foreign('currency_id')
                ->references('currency_id')
                ->on('currencies')
                ->onDelete('cascade');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('currency_id');
            $table->index('effective_date');
            $table->index('source');
            $table->index(['currency_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
    }
};
