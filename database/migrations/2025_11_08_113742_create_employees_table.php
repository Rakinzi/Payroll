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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('emp_system_id')->unique()->nullable(); // Auto-generated if not provided

            // Personal Information
            $table->string('title')->nullable(); // Mr, Mrs, Ms, Dr, etc.
            $table->string('firstname');
            $table->string('surname');
            $table->string('othername')->nullable();
            $table->string('nationality')->nullable();
            $table->string('nat_id')->nullable()->unique(); // National ID
            $table->string('nassa_number')->nullable(); // NSSA number
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();

            // Contact Information
            $table->text('home_address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('emp_email')->unique();
            $table->string('personal_email_address')->nullable();

            // Identification Documents
            $table->string('passport')->nullable();
            $table->string('driver_license')->nullable();

            // Employment Information
            $table->date('hire_date')->nullable(); // joining_date
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();
            $table->uuid('occupation_id')->nullable();
            $table->uuid('paypoint_id')->nullable();
            $table->uuid('center_id'); // Cost center
            $table->integer('average_working_days')->nullable();
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->enum('payment_basis', ['monthly', 'hourly', 'daily'])->default('monthly');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque'])->default('bank_transfer');

            // Compensation & Benefits
            $table->decimal('basic_salary', 15, 2)->default(0); // ZWG salary
            $table->decimal('basic_salary_usd', 15, 2)->default(0); // USD salary
            $table->decimal('leave_entitlement', 5, 2)->nullable(); // Annual leave days
            $table->decimal('leave_accrual', 5, 2)->nullable(); // Leave accrual rate

            // Tax Configuration
            $table->string('tax_directives')->nullable();
            $table->boolean('disability_status')->default(false);
            $table->integer('dependents')->default(0);
            $table->integer('vehicle_engine_capacity')->nullable(); // For vehicle benefit tax

            // Currency Splitting
            $table->decimal('zwl_percentage', 5, 2)->default(0); // Percentage of salary in ZWG
            $table->decimal('usd_percentage', 5, 2)->default(100); // Percentage of salary in USD

            // NEC Integration
            $table->uuid('nec_grade_id')->nullable();

            // Employee Role & Access
            $table->string('emp_role')->nullable(); // System role

            // Status & Lifecycle
            $table->boolean('is_active')->default(true);
            $table->boolean('is_ex')->default(false); // Is ex-employee
            $table->date('is_ex_on')->nullable(); // Date employee left
            $table->enum('employment_status', [
                'active',
                'END CONTRACT',
                'RESIGNED',
                'DISMISSED',
                'DECEASED',
                'SUSPENDED'
            ])->default('active');
            $table->text('discharge_notes')->nullable();

            // Audit Trail
            $table->timestamp('last_login_time')->nullable();
            $table->string('last_login_ip')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys - cost_centers already exists, others will be added in a later migration
            $table->foreign('center_id')->references('id')->on('cost_centers')->onDelete('cascade');

            // Indexes
            $table->index('center_id');
            $table->index('department_id');
            $table->index('position_id');
            $table->index('occupation_id');
            $table->index('paypoint_id');
            $table->index('nec_grade_id');
            $table->index('is_active');
            $table->index('is_ex');
            $table->index('employment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
