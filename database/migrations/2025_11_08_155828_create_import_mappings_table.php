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
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('import_type', ['employees', 'salaries', 'transactions', 'banking'])->default('employees');
            $table->string('spreadsheet_column');
            $table->string('database_field');
            $table->string('data_type')->default('string');
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('transformation_rules')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['import_type', 'is_active']);
            $table->unique(['import_type', 'spreadsheet_column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
    }
};
