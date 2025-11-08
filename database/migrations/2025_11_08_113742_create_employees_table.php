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
            $table->string('emp_system_id')->unique()->nullable(); // Nullable to allow auto-generation
            $table->string('firstname');
            $table->string('surname');
            $table->string('othername')->nullable();
            $table->string('emp_email')->unique();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->uuid('center_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_ex')->default(false)->comment('Is ex-employee');
            $table->date('is_ex_on')->nullable()->comment('Date employee left');
            $table->date('hire_date')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('center_id')->references('id')->on('cost_centers')->onDelete('cascade');

            // Indexes
            $table->index('center_id');
            $table->index('is_active');
            $table->index('is_ex');
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
