// database/migrations/2024_01_01_000002_add_fields_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_main_id')->nullable()->after('id');
            $table->tinyInteger('id_profile')->default(5)->after('email');
            $table->foreign('id_profile')->references('id_profile')->on('profiles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_main_id', 'id_profile']);
        });
    }
};