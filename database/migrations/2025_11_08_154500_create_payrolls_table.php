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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payroll_name')->unique();
            $table->enum('payroll_type', ['Period', 'Daily', 'Hourly'])->default('Period');
            $table->integer('payroll_period')->default(12); // 12=Monthly, 26=Bi-weekly, 52=Weekly
            $table->date('start_date');
            $table->string('tax_method', 100)->default('FDS Average');
            $table->string('payroll_currency', 100)->default('USD + ZWL');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'payroll_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
