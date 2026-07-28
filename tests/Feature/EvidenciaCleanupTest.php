<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Unidad;
use App\Models\Solicitud;
use App\Models\Evidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EvidenciaCleanupTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Unidad $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        $rolSolicitante = Rol::create([
            'nombre_rol' => 'Solicitante',
            'descripcion' => 'Usuario Normal'
        ]);

        $this->usuario = User::create([
            'id_rol'   => $rolSolicitante->id_rol,
            'nombre'   => 'Juan',
            'apellido' => 'Perez',
            'correo'   => 'juan@rchhospital.sv',
            'password' => bcrypt('password123'),
            'estado'   => 'Activo'
        ]);

        $sede = Sede::create([
            'nombre_sede' => 'Sede Central',
            'direccion'   => 'Calle Falsa 123'
        ]);

        $this->unidad = Unidad::create([
            'nombre_unidad' => 'UCI',
            'id_sede'       => $sede->id_sede
        ]);
    }

    public function test_las_evidencias_se_eliminan_fisicamente_al_cancelar_una_solicitud(): void
    {
        Storage::fake('public');

        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->usuario->id_usuario,
            'id_unidad'              => $this->unidad->id_unidad,
            'titulo'                 => 'Aire acondicionado dañado',
            'descripcion'            => 'No enfría nada',
            'estado_solicitud'       => 'Abierta',
            'fecha_apertura'         => now()
        ]);

        // Simular subir un archivo de evidencia
        $file = UploadedFile::fake()->image('evidencia.jpg');
        $path = $file->store('evidencias', 'public');

        $evidencia = Evidencia::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'url_recurso'  => Storage::url($path),
            'tipo_archivo' => 'image/jpeg'
        ]);

        // Verificar que el archivo existe físicamente en el storage fake
        Storage::disk('public')->assertExists($path);

        // Cancelar la solicitud (esto debería disparar la eliminación)
        $solicitud->update(['estado_solicitud' => 'Cancelada']);

        // Verificar que el registro de evidencia en la base de datos ya no existe
        $this->assertDatabaseMissing('evidencias', [
            'id_evidencia' => $evidencia->id_evidencia
        ]);

        // Verificar que el archivo fue eliminado físicamente del storage
        Storage::disk('public')->assertMissing($path);
    }

    public function test_la_evidencia_se_elimina_fisicamente_al_borrar_el_modelo(): void
    {
        Storage::fake('public');

        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->usuario->id_usuario,
            'id_unidad'              => $this->unidad->id_unidad,
            'titulo'                 => 'Aire acondicionado dañado',
            'descripcion'            => 'No enfría nada',
            'estado_solicitud'       => 'Abierta',
            'fecha_apertura'         => now()
        ]);

        $file = UploadedFile::fake()->image('evidencia2.jpg');
        $path = $file->store('evidencias', 'public');

        $evidencia = Evidencia::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'url_recurso'  => Storage::url($path),
            'tipo_archivo' => 'image/jpeg'
        ]);

        Storage::disk('public')->assertExists($path);

        // Borrar el modelo de evidencia
        $evidencia->delete();

        // Verificar que el archivo fue eliminado del disco
        Storage::disk('public')->assertMissing($path);
    }
}
