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
        Schema::create('custom_transactions_tag_tbl', function (Blueprint $table) {
            $table->id('tag_id');
            $table->unsignedBigInteger('custom_id');
            $table->uuid('code_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('custom_id')
                ->references('custom_id')
                ->on('custom_transactions_tbl')
                ->onDelete('cascade');

            $table->foreign('code_id')
                ->references('id')
                ->on('transaction_codes')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['custom_id', 'code_id']);

            // Indexes
            $table->index('custom_id');
            $table->index('code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_transactions_tag_tbl');
    }
};
