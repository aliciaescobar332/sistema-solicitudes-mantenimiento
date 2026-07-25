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
        Schema::create('solicitudes_reasignacion', function (Blueprint $table) {
            $table->uuid('id_solicitud_reasignacion')->primary();
            $table->uuid('id_solicitud')->index();
            $table->uuid('id_usuario_tecnico_solicitante')->index();
            $table->uuid('id_usuario_tecnico_propuesto')->nullable()->index();
            $table->text('motivo');
            $table->enum('estado', ['Pendiente', 'Aprobada', 'Rechazada'])->default('Pendiente');
            $table->uuid('id_usuario_coordinador')->nullable()->index();
            $table->text('comentarios_coordinador')->nullable();
            $table->timestamps();

            $table->foreign('id_solicitud')->references('id_solicitud')->on('solicitudes')->onDelete('cascade');
            $table->foreign('id_usuario_tecnico_solicitante')->references('id_usuario')->on('users')->onDelete('cascade');
            $table->foreign('id_usuario_tecnico_propuesto')->references('id_usuario')->on('users')->onDelete('set null');
            $table->foreign('id_usuario_coordinador')->references('id_usuario')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_reasignacion');
    }
};
