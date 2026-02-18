<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stg_municipalities_schools_raw', function (Blueprint $table) {
            $table->text('departamento')->nullable()->after('row_num');
        });
    }

    public function down(): void
    {
        Schema::table('stg_municipalities_schools_raw', function (Blueprint $table) {
            $table->dropColumn('departamento');
        });
    }
};
