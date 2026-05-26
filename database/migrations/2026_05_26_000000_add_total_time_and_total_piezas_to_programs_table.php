<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->decimal('total_time', 8, 5)->default(0)->after('created_by')->comment('Sumatoria total del tiempo del programa');
            $table->integer('total_piezas')->unsigned()->default(0)->after('total_time')->comment('Sumatoria total de piezas del programa');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['total_time', 'total_piezas']);
        });
    }
};
