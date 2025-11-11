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
            // Add foreign key constraints after dependent tables exist
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('position_id')->references('id')->on('work_position')->onDelete('set null');
            $table->foreign('occupation_id')->references('id')->on('occupations')->onDelete('set null');
            $table->foreign('paypoint_id')->references('id')->on('paypoints')->onDelete('set null');
            $table->foreign('nec_grade_id')->references('id')->on('nec_grades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['occupation_id']);
            $table->dropForeign(['paypoint_id']);
            $table->dropForeign(['nec_grade_id']);
        });
    }
};
