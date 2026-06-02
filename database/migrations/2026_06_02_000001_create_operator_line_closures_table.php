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
        Schema::create('operator_line_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_daily_program')->constrained('daily_programs')->onDelete('cascade');
            $table->foreignId('id_production_line')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('closed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['id_daily_program', 'id_production_line'], 'operator_line_closures_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_line_closures');
    }
};
