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
        Schema::create('notices', function (Blueprint $table) {
            $table->id('notice_id');
            $table->string('employee_id', 50);
            $table->string('notice_title');
            $table->string('file_name');
            $table->bigInteger('file_size')->unsigned();
            $table->timestamps();

            // Foreign key
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // Indexes
            $table->index('employee_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
