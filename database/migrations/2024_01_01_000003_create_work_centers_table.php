// database/migrations/2024_01_01_000003_create_work_centers_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('installed_capacity')->comment('Capacidad instalada por hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_centers');
    }
};