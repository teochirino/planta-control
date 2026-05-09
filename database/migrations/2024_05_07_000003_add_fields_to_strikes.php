<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('strikes', function (Blueprint $table) {
            if (!Schema::hasColumn('strikes', 'date')) {
                $table->date('date')->after('id_production_lines');
            }
            if (!Schema::hasColumn('strikes', 'id_daily_program')) {
                $table->unsignedBigInteger('id_daily_program')->nullable()->after('date');
                $table->foreign('id_daily_program')->references('id')->on('daily_programs')->onDelete('set null');
            }
            if (!Schema::hasColumn('strikes', 'start_time')) {
                $table->time('start_time')->after('description');
            }
            if (!Schema::hasColumn('strikes', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (!Schema::hasColumn('strikes', 'cost')) {
                $table->decimal('cost', 10, 2)->default(0)->after('minutes')->comment('Costo del paro');
            }
        });
        
        // Modificar minutes a nullable en una operación separada
        Schema::table('strikes', function (Blueprint $table) {
            $table->integer('minutes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('strikes', function (Blueprint $table) {
            $table->dropForeign(['id_daily_program']);
            $table->dropColumn(['date', 'id_daily_program', 'start_time', 'end_time', 'cost']);
        });
    }
};
