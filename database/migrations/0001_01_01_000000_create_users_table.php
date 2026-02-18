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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('document_type')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('second_name', 100)->nullable();
            $table->string('first_last_name', 100)->nullable();
            $table->string('second_last_name', 100)->nullable();
            $table->string('corregimiento')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex_at_birth', 50)->nullable();
            $table->string('gender_identity', 50)->nullable();
            $table->string('sexual_orientation', 50)->nullable();
            $table->string('ethnic_belonging', 50)->nullable();
            $table->string('disability', 50)->nullable();
            $table->boolean('is_peasant')->default(false);
            $table->boolean('is_migrant_population')->default(false);
            $table->boolean('is_social_barra')->default(false);
            $table->boolean('is_private_freedom_population')->default(false);
            $table->boolean('is_human_rights_defender')->default(false);
            $table->foreignId('primary_node_id')->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
