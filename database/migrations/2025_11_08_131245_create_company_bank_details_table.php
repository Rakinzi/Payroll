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
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('branch_name')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('swift_code')->nullable();
            $table->enum('currency', ['USD', 'ZWG']);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['currency', 'is_primary', 'is_active']);
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
