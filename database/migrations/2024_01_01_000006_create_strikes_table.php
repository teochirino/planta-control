// database/migrations/2024_01_01_000006_create_strikes_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_production_lines')->constrained('production_lines')->onDelete('cascade');
            $table->text('description');
            $table->integer('minutes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strikes');
    }
};