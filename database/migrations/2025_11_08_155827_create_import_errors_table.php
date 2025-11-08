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
        Schema::create('import_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->integer('row_number');
            $table->string('column_name')->nullable();
            $table->text('error_message');
            $table->text('raw_value')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('import_sessions')->onDelete('cascade');
            $table->index(['session_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
