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
        Schema::create('third_party_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('generated_by');
            $table->enum('report_type', ['standard_levy', 'zimdef', 'zimra_p2']);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('currency', 10);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('submission_status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->string('submission_reference')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('payroll_id');
            $table->index('report_type');
            $table->index('submission_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_reports');
    }
};
