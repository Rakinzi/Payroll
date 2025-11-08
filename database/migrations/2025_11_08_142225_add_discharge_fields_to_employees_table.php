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
            $table->string('is_ex_by')->nullable()->after('is_ex_on'); // Name of person who discharged employee
            $table->date('reinstated_date')->nullable()->after('discharge_notes'); // Date of reinstatement
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['is_ex_by', 'reinstated_date']);
        });
    }
};
