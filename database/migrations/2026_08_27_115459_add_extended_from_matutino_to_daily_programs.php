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
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->boolean('extended_from_matutino')->default(false)->after('program_id')
                  ->comment('Indica si el programa vespertino fue creado por extension del matutino');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropColumn('extended_from_matutino');
        });
    }
};
