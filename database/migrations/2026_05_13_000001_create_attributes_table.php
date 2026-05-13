<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_work_center');
            $table->string('name', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->enum('color', ['rojo', 'amarillo', 'verde', 'gris'])
                  ->charset('utf8mb4')
                  ->collation('utf8mb4_unicode_ci')
                  ->default('verde');
            $table->timestamp('color_changed_at')->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
            
            $table->foreign('id_work_center')
                  ->references('id')
                  ->on('work_centers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
