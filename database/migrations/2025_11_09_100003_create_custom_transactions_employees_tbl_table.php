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
        Schema::create('custom_transactions_employees_tbl', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('custom_id');
            $table->uuid('employee_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('custom_id')
                ->references('custom_id')
                ->on('custom_transactions_tbl')
                ->onDelete('cascade');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['custom_id', 'employee_id']);

            // Indexes
            $table->index('custom_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_transactions_employees_tbl');
    }
};
