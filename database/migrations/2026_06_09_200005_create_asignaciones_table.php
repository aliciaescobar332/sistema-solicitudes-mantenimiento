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
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->uuid('id_asignacion')->primary();
            $table->uuid('id_solicitud')->unique(); // Relación 1:1 con solicitud activa
            $table->uuid('id_usuario_tecnico')->index();
            $table->uuid('id_usuario_coordinador')->index();
            $table->timestamp('fecha_asignacion');
            $table->timestamps();

            $table->foreign('id_solicitud')->references('id_solicitud')->on('solicitudes')->onDelete('cascade');
            $table->foreign('id_usuario_tecnico')->references('id_usuario')->on('users')->onDelete('cascade');
            $table->foreign('id_usuario_coordinador')->references('id_usuario')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
