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
        Schema::table('company_details', function (Blueprint $table) {
            $table->string('registration_number', 50)->nullable()->after('physical_address');
            $table->string('tax_number', 50)->nullable()->after('registration_number');
            $table->string('industry', 100)->nullable()->after('tax_number');
            $table->string('website', 255)->nullable()->after('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_details', function (Blueprint $table) {
            $table->dropColumn(['registration_number', 'tax_number', 'industry', 'website']);
        });
    }
};
