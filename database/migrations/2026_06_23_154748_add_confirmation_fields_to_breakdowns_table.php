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
        Schema::table('breakdowns', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('minutes')->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->integer('confirmed_minutes')->nullable()->after('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('breakdowns', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_by', 'confirmed_at', 'confirmed_minutes']);
        });
    }
};
