<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_type')->nullable()->after('email');
            $table->string('document_number', 100)->nullable()->after('document_type');
            $table->string('first_name', 100)->nullable()->after('document_number');
            $table->string('second_name', 100)->nullable()->after('first_name');
            $table->string('first_last_name', 100)->nullable()->after('second_name');
            $table->string('second_last_name', 100)->nullable()->after('first_last_name');
            $table->date('birth_date')->nullable()->after('second_last_name');
            $table->string('sex_at_birth', 50)->nullable()->after('birth_date');
            $table->string('gender_identity', 50)->nullable()->after('sex_at_birth');
            $table->string('sexual_orientation', 50)->nullable()->after('gender_identity');
            $table->string('ethnic_belonging', 50)->nullable()->after('sexual_orientation');
            $table->string('disability', 50)->nullable()->after('ethnic_belonging');
            $table->boolean('is_peasant')->default(false)->after('disability');
            $table->boolean('is_migrant_population')->default(false)->after('is_peasant');
            $table->boolean('is_social_barra')->default(false)->after('is_migrant_population');
            $table->boolean('is_private_freedom_population')->default(false)->after('is_social_barra');
            $table->boolean('is_human_rights_defender')->default(false)->after('is_private_freedom_population');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'document_number',
                'first_name',
                'second_name',
                'first_last_name',
                'second_last_name',
                'birth_date',
                'sex_at_birth',
                'gender_identity',
                'sexual_orientation',
                'ethnic_belonging',
                'disability',
                'is_peasant',
                'is_migrant_population',
                'is_social_barra',
                'is_private_freedom_population',
                'is_human_rights_defender',
            ]);
        });
    }
};
