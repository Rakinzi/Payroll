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
        Schema::create('payslip_notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->uuid('payslip_id');
            $table->uuid('employee_id');
            $table->uuid('sent_by');
            $table->enum('channel', ['email', 'sms', 'whatsapp']);
            $table->string('recipient'); // phone number or email
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable(); // SMS/WhatsApp provider message ID
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
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

            $table->foreign('sent_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('payslip_id');
            $table->index('employee_id');
            $table->index('channel');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_notifications');
    }
};
