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
        Schema::create('company_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('center_id');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->string('branch_code', 10);
            $table->text('account_number'); // Encrypted
            $table->enum('account_type', ['Current', 'Nostro', 'FCA']);
            $table->enum('account_currency', ['RTGS', 'ZWG', 'USD']);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('center_id')->references('id')->on('cost_centers')->onDelete('cascade');

            // Indexes
            $table->index('center_id');
            $table->index('is_default');
            $table->index('account_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_bank_details');
    }
};
