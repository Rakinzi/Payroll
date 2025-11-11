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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('admin_id')->nullable();
            $table->enum('leave_type', [
                'Ordinary',
                'Sick',
                'Study',
                'Maternity',
                'Annual',
                'Forced',
                'Special',
                'Unpaid',
                'Other'
            ]);
            $table->enum('leave_source', ['Normal Leave', 'Leave Bank'])->default('Normal Leave');
            $table->date('date_from');
            $table->date('date_to');
            $table->text('comments')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');
            $table->timestamp('date_leave_applied')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index('status');
            $table->index(['date_from', 'date_to']);
            $table->index('approved_by');

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
