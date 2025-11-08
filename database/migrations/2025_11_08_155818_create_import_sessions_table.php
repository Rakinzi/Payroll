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
        Schema::create('import_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('import_type', ['employees', 'salaries', 'transactions', 'banking'])->default('employees');
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->enum('status', ['uploaded', 'processing', 'preview', 'completed', 'failed'])->default('uploaded');
            $table->uuid('imported_by');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('imported_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['import_type', 'status']);
            $table->index('imported_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
