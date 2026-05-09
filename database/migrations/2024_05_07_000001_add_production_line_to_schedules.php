<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'id_production_line')) {
                $table->unsignedBigInteger('id_production_line')->after('id_daily_program');
                $table->foreign('id_production_line')->references('id')->on('production_lines')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['id_production_line']);
            $table->dropColumn('id_production_line');
        });
    }
};
