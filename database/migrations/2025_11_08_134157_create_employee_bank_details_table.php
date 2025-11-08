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
        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');

            // Bank Information
            $table->string('bank_name');
            $table->string('branch_name');
            $table->string('branch_code')->nullable();

            // Account Details (encrypted)
            $table->text('account_number'); // Will be encrypted
            $table->string('account_name')->nullable();
            $table->enum('account_type', ['Current', 'Savings', 'FCA'])->default('Current');
            $table->enum('account_currency', ['USD', 'ZWL', 'ZiG'])->default('USD');

            // Capacity & Status
            $table->decimal('capacity', 5, 2)->default(100.00)->comment('Percentage allocation for salary splitting');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Indexes
            $table->index('employee_id');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_bank_details');
    }
};
