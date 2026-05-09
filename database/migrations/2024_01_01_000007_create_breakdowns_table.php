// database/migrations/2024_01_01_000007_create_breakdowns_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_machine')->constrained('machines')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->integer('minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdowns');
    }
};