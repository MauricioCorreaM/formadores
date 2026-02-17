<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campus_user', function (Blueprint $table) {
            $table->unsignedBigInteger('focalization_id')->nullable()->after('campus_id');
            $table->foreign('focalization_id')->references('id')->on('focalizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campus_user', function (Blueprint $table) {
            $table->dropForeign(['focalization_id']);
            $table->dropColumn('focalization_id');
        });
    }
};
