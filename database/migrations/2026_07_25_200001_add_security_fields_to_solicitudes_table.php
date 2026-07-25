<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('codigo_verificacion', 6)->after('estado_solicitud')->nullable();
            $table->string('firma_integridad', 64)->after('codigo_verificacion')->nullable();
        });

        // Modificar columna titulo a TEXT para que soporte el tamaño del cifrado simétrico
        DB::statement('ALTER TABLE solicitudes MODIFY titulo TEXT NOT NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a VARCHAR(200)
        DB::statement('ALTER TABLE solicitudes MODIFY titulo VARCHAR(200) NOT NULL;');

        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn(['codigo_verificacion', 'firma_integridad']);
        });
    }
};
