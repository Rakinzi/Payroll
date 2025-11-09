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
        Schema::table('employees', function (Blueprint $table) {
            // Payslip password for secure downloads
            $table->string('payslip_password')->nullable()->after('emp_email');

            // Notification preferences
            $table->boolean('sms_notifications_enabled')->default(true)->after('payslip_password');
            $table->boolean('whatsapp_notifications_enabled')->default(false)->after('sms_notifications_enabled');
            $table->boolean('email_notifications_enabled')->default(true)->after('whatsapp_notifications_enabled');

            // WhatsApp opt-in tracking
            $table->timestamp('whatsapp_opted_in_at')->nullable()->after('whatsapp_notifications_enabled');
            $table->string('whatsapp_phone_number')->nullable()->after('whatsapp_opted_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'payslip_password',
                'sms_notifications_enabled',
                'whatsapp_notifications_enabled',
                'email_notifications_enabled',
                'whatsapp_opted_in_at',
                'whatsapp_phone_number',
            ]);
        });
    }
};
