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
            $table->boolean('manually_edited_by_supervisor')->default(false)->after('engineering_edited_by')
                  ->comment('Indica si el programa fue editado manualmente por supervisor de área');
            $table->timestamp('supervisor_edited_at')->nullable()->after('manually_edited_by_supervisor')
                  ->comment('Cuándo se editó manualmente por supervisor');
            $table->foreignId('supervisor_edited_by')->nullable()->after('supervisor_edited_at')
                  ->constrained('users')
                  ->comment('Usuario (supervisor) que editó manualmente el programa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropForeign(['supervisor_edited_by']);
            $table->dropColumn([
                'manually_edited_by_supervisor',
                'supervisor_edited_at',
                'supervisor_edited_by'
            ]);
        });
    }
};
