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
        Schema::create('encuestas', function (Blueprint $table) {
            $table->uuid('id_encuesta')->primary();
            $table->uuid('id_solicitud')->unique();
            $table->integer('calificacion_rapidez'); // 1 a 5
            $table->integer('calificacion_calidad'); // 1 a 5
            $table->integer('calificacion_amabilidad'); // 1 a 5
            $table->text('comentarios')->nullable();
            $table->timestamps();

            $table->foreign('id_solicitud')->references('id_solicitud')->on('solicitudes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
