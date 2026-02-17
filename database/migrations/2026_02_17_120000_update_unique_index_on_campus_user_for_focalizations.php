<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campus_user', function (Blueprint $table) {
            $table->dropUnique(['campus_id', 'user_id']);
            $table->unique(['campus_id', 'user_id', 'focalization_id'], 'campus_user_campus_user_focalization_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campus_user', function (Blueprint $table) {
            $table->dropUnique('campus_user_campus_user_focalization_unique');
            $table->unique(['campus_id', 'user_id']);
        });
    }
};
