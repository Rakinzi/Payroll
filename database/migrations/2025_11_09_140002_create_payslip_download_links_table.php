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
        Schema::create('payslip_download_links', function (Blueprint $table) {
            $table->id('link_id');
            $table->uuid('payslip_id');
            $table->uuid('employee_id');
            $table->string('token', 64)->unique();
            $table->string('download_method')->default('link'); // link, email, sms, whatsapp
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('accessed_at')->nullable();
            $table->string('access_ip')->nullable();
            $table->string('access_user_agent')->nullable();
            $table->integer('access_count')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('payslip_id')
                ->references('id')
                ->on('payslips')
                ->onDelete('cascade');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // Indexes
            $table->index('token');
            $table->index('payslip_id');
            $table->index('employee_id');
            $table->index('expires_at');
            $table->index('is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_download_links');
    }
};
