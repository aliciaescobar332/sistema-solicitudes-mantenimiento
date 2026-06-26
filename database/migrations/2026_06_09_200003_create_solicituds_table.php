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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->uuid('id_solicitud')->primary();
            $table->uuid('id_usuario_solicitante')->index();
            $table->uuid('id_unidad')->index();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->enum('prioridad', ['Baja', 'Media', 'Alta', 'Crítica'])->nullable(); // Asignada por el coordinador
            $table->enum('estado_solicitud', ['Abierta', 'Asignada', 'En Proceso', 'Validada', 'Cerrada', 'Devuelta', 'Cancelada'])->default('Abierta');
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamps();

            $table->foreign('id_usuario_solicitante')->references('id_usuario')->on('users')->onDelete('cascade');
            $table->foreign('id_unidad')->references('id_unidad')->on('unidades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
