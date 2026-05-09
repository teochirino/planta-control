// database/migrations/2024_01_01_000004_create_production_lines_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_work_center')->constrained('work_centers')->onDelete('cascade');
            $table->string('title');
            $table->integer('installed_capacity');
            $table->decimal('cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lines');
    }
};