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
        Schema::create('stg_nodes_raw', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('row_num');
            $table->text('departamento')->nullable();
            $table->text('nodo')->nullable();
            $table->timestamps();
        });

        Schema::create('stg_municipalities_schools_raw', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('row_num');
            $table->text('secretaria')->nullable();
            $table->text('codigo_dane_municipio')->nullable();
            $table->text('municipio')->nullable();
            $table->text('codigo_dane')->nullable();
            $table->text('nombre_establecimiento')->nullable();
            $table->text('codigo_dane_sede')->nullable();
            $table->text('nombre_sede')->nullable();
            $table->timestamps();
        });

        Schema::create('stg_campuses_raw', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('row_num');
            $table->text('codigo_dane')->nullable();
            $table->text('codigo_dane_sede')->nullable();
            $table->text('nombre_sede')->nullable();
            $table->text('zona')->nullable();
            $table->text('nodo')->nullable();
            $table->text('focalizacion')->nullable();
            $table->timestamps();
        });

        Schema::create('stg_check_raw', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('row_num');
            $table->text('codigo_dane_municipio')->nullable();
            $table->text('divipola_municipio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_check_raw');
        Schema::dropIfExists('stg_campuses_raw');
        Schema::dropIfExists('stg_municipalities_schools_raw');
        Schema::dropIfExists('stg_nodes_raw');
    }
};
