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
            $table->boolean('manually_edited_by_engineering')->default(false)->after('balance_processed_by')
                  ->comment('Indica si el programa fue editado manualmente por ingeniería de procesos');
            $table->timestamp('engineering_edited_at')->nullable()->after('manually_edited_by_engineering')
                  ->comment('Cuándo se editó manualmente por ingeniería');
            $table->foreignId('engineering_edited_by')->nullable()->after('engineering_edited_at')
                  ->constrained('users')
                  ->comment('Usuario (ingeniero) que editó manualmente el programa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropForeign(['engineering_edited_by']);
            $table->dropColumn([
                'manually_edited_by_engineering',
                'engineering_edited_at',
                'engineering_edited_by'
            ]);
        });
    }
};
