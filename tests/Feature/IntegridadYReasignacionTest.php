<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Unidad;
use App\Models\Solicitud;
use App\Models\Asignacion;
use App\Models\SolicitudReasignacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class IntegridadYReasignacionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $solicitanteUser;
    protected $tecnicoUser1;
    protected $tecnicoUser2;
    protected $coordinadorUser;
    protected $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear los Roles obligatorios
        $rolAdmin = Rol::create(['nombre_rol' => 'Administrador']);
        $rolSolicitante = Rol::create(['nombre_rol' => 'Solicitante']);
        $rolTecnico = Rol::create(['nombre_rol' => 'Técnico']);
        $rolCoordinador = Rol::create(['nombre_rol' => 'Coordinador']);

        // 2. Crear usuarios de prueba con roles específicos
        $this->adminUser = User::create([
            'id_rol' => $rolAdmin->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'General',
            'correo' => 'admin@hospital.sv',
            'password' => bcrypt('password'),
            'estado' => 'Activo',
        ]);

        $this->solicitanteUser = User::create([
            'id_rol' => $rolSolicitante->id_rol,
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'correo' => 'juan.perez@hospital.sv',
            'password' => bcrypt('password'),
            'estado' => 'Activo',
        ]);

        $this->tecnicoUser1 = User::create([
            'id_rol' => $rolTecnico->id_rol,
            'nombre' => 'Carlos',
            'apellido' => 'Tecnico',
            'correo' => 'carlos@hospital.sv',
            'password' => bcrypt('password'),
            'estado' => 'Activo',
        ]);

        $this->tecnicoUser2 = User::create([
            'id_rol' => $rolTecnico->id_rol,
            'nombre' => 'Luis',
            'apellido' => 'Tecnico',
            'correo' => 'luis@hospital.sv',
            'password' => bcrypt('password'),
            'estado' => 'Activo',
        ]);

        $this->coordinadorUser = User::create([
            'id_rol' => $rolCoordinador->id_rol,
            'nombre' => 'Marta',
            'apellido' => 'Coord',
            'correo' => 'marta@hospital.sv',
            'password' => bcrypt('password'),
            'estado' => 'Activo',
        ]);

        // 3. Crear infraestructura real de prueba (Sede y Unidad)
        $sede = Sede::create([
            'nombre_sede' => 'Hospital Central',
            'direccion' => 'San Salvador, El Salvador',
        ]);

        $this->unidad = Unidad::create([
            'id_sede' => $sede->id_sede,
            'nombre_unidad' => 'Quirófano Quirúrgico A',
        ]);
    }

    /**
     * Test 1: Verificar el cifrado de datos sensibles en la base de datos.
     */
    public function test_cifrado_de_campos_sensibles_en_base_de_datos(): void
    {
        $tituloOriginal = 'Fuga de Gas Oxígeno';
        $descripcionOriginal = 'Hay un fuerte olor a gas cerca de las mangueras de anestesia.';

        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->solicitanteUser->id_usuario,
            'id_unidad' => $this->unidad->id_unidad,
            'titulo' => $tituloOriginal,
            'descripcion' => $descripcionOriginal,
            'estado_solicitud' => 'Abierta',
        ]);

        $solId = $solicitud->id_solicitud;

        // 1. Recuperar directamente usando DB Query Builder (crudo, sin ORM Laravel que descifra al vuelo)
        $rawDb = DB::table('solicitudes')->where('id_solicitud', $solId)->first();

        // 2. Comprobar que en la BD cruda el texto NO es legible como texto plano
        $this->assertNotEquals($tituloOriginal, $rawDb->titulo);
        $this->assertNotEquals($descripcionOriginal, $rawDb->descripcion);

        // 3. Comprobar que el descifrado manual usando Eloquent funciona correctamente
        $solicitudRecuperada = Solicitud::find($solId);
        $this->assertEquals($tituloOriginal, $solicitudRecuperada->titulo);
        $this->assertEquals($descripcionOriginal, $solicitudRecuperada->descripcion);
    }

    /**
     * Test 2: Verificar la firma criptográfica HMAC-SHA256 y detección de manipulación de datos.
     */
    public function test_firma_hmac_detecta_manipulacion_de_datos_y_bloquea_acceso(): void
    {
        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->solicitanteUser->id_usuario,
            'id_unidad' => $this->unidad->id_unidad,
            'titulo' => 'Falla de Tomacorriente',
            'descripcion' => 'No enciende la pantalla de monitoreo.',
            'estado_solicitud' => 'Abierta',
        ]);

        $solId = $solicitud->id_solicitud;

        // 1. Validar integridad inicial: Debe pasar
        $this->assertTrue($solicitud->validarIntegridad());

        // 2. Simular manipulación directa en la Base de Datos (bypass del ORM)
        DB::table('solicitudes')->where('id_solicitud', $solId)->update([
            'estado_solicitud' => 'Validada'
        ]);

        // 3. Al intentar recuperar usando el endpoint show, el middleware de integridad debe abortar con 500
        $response = $this->actingAs($this->solicitanteUser)
            ->getJson("/api/solicitante/solicitudes/{$solId}");

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'error_code' => 'INTEGRITY_VERIFICATION_FAILED'
        ]);
    }

    /**
     * Test 3: Validar que el inicio de atención en sitio exija y valide el OTP.
     */
    public function test_inicio_atencion_exige_y_valida_codigo_otp(): void
    {
        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->solicitanteUser->id_usuario,
            'id_unidad' => $this->unidad->id_unidad,
            'titulo' => 'Clima Caliente Quirófano',
            'descripcion' => 'El aire acondicionado no enfría.',
            'estado_solicitud' => 'Asignada',
        ]);

        $asignacion = Asignacion::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_usuario_tecnico' => $this->tecnicoUser1->id_usuario,
            'id_usuario_coordinador' => $this->coordinadorUser->id_usuario,
            'fecha_asignacion' => now(),
        ]);

        $otpOriginal = $solicitud->codigo_verificacion;
        $asigId = $asignacion->id_asignacion;

        // 1. Intentar iniciar atención sin OTP
        $response = $this->actingAs($this->tecnicoUser1)
            ->patchJson("/api/tecnico/asignaciones/{$asigId}/iniciar", []);
        $response->assertStatus(422);

        // 2. Intentar iniciar atención con OTP incorrecto
        $response = $this->actingAs($this->tecnicoUser1)
            ->patchJson("/api/tecnico/asignaciones/{$asigId}/iniciar", [
                'codigo_verificacion' => '000000'
            ]);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'mensaje' => 'El código de verificación física ingresado es incorrecto. Favor solicitar el OTP correcto al solicitante.'
        ]);

        // 3. Iniciar atención con OTP correcto
        $response = $this->actingAs($this->tecnicoUser1)
            ->patchJson("/api/tecnico/asignaciones/{$asigId}/iniciar", [
                'codigo_verificacion' => $otpOriginal
            ]);
        $response->assertStatus(200);

        // Validar que el ticket cambió de estado a "En Proceso"
        $this->assertEquals('En Proceso', Solicitud::find($solicitud->id_solicitud)->estado_solicitud);
    }

    /**
     * Test 4: Validar aislamiento estricto de datos.
     */
    public function test_aislamiento_de_datos_restringe_acceso_no_autorizado(): void
    {
        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->solicitanteUser->id_usuario,
            'id_unidad' => $this->unidad->id_unidad,
            'titulo' => 'Filtro HEPA Obstruido',
            'descripcion' => 'Presión de aire inestable.',
            'estado_solicitud' => 'Asignada',
        ]);

        $asignacion = Asignacion::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_usuario_tecnico' => $this->tecnicoUser1->id_usuario,
            'id_usuario_coordinador' => $this->coordinadorUser->id_usuario,
            'fecha_asignacion' => now(),
        ]);

        $solId = $solicitud->id_solicitud;

        // Un técnico ajeno (tecnicoUser2) no debe poder ver el ticket
        $response = $this->actingAs($this->tecnicoUser2)
            ->getJson("/api/solicitudes/{$solId}");
        $response->assertStatus(403);

        // El técnico asignado (tecnicoUser1) sí puede ver el ticket
        $response = $this->actingAs($this->tecnicoUser1)
            ->getJson("/api/solicitudes/{$solId}");
        $response->assertStatus(200);
    }

    /**
     * Test 5: Flujo completo de reasignación de técnicos.
     */
    public function test_flujo_completo_de_reasignacion_de_tecnicos(): void
    {
        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $this->solicitanteUser->id_usuario,
            'id_unidad' => $this->unidad->id_unidad,
            'titulo' => 'Cortocircuito Lámpara Quirófano',
            'descripcion' => 'La lámpara principal emite chispas.',
            'estado_solicitud' => 'En Proceso',
        ]);

        $asignacion = Asignacion::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_usuario_tecnico' => $this->tecnicoUser1->id_usuario,
            'id_usuario_coordinador' => $this->coordinadorUser->id_usuario,
            'fecha_asignacion' => now(),
        ]);

        $asigId = $asignacion->id_asignacion;
        $solId = $solicitud->id_solicitud;

        // 1. El Técnico solicita reasignación
        $response = $this->actingAs($this->tecnicoUser1)
            ->postJson("/api/tecnico/asignaciones/{$asigId}/solicitar-reasignacion", [
                'motivo' => 'Exceso de tickets asignados en Quirófano B, requiero reasignación.',
                'id_usuario_tecnico_propuesto' => $this->tecnicoUser2->id_usuario
            ]);
        $response->assertStatus(201);
        $reasigId = $response->json('solicitud_reasignacion.id_solicitud_reasignacion');

        // 2. El Coordinador consulta solicitudes pendientes y carga de trabajo
        $response = $this->actingAs($this->coordinadorUser)
            ->getJson("/api/coordinador/reasignaciones/pendientes");
        $response->assertStatus(200);
        $response->assertJsonCount(1);

        $response = $this->actingAs($this->coordinadorUser)
            ->getJson("/api/coordinador/tecnicos/carga-trabajo");
        $response->assertStatus(200);

        // 3. El Coordinador aprueba la reasignación
        $response = $this->actingAs($this->coordinadorUser)
            ->patchJson("/api/coordinador/reasignaciones/{$reasigId}/resolver", [
                'decision' => 'Aprobada',
                'id_usuario_tecnico_nuevo' => $this->tecnicoUser2->id_usuario,
                'comentarios_coordinador' => 'Reasignación autorizada por alta prioridad del ticket.'
            ]);
        $response->assertStatus(200);

        // 4. Validar que la asignación se transfirió al Técnico 2
        $this->assertEquals($this->tecnicoUser2->id_usuario, Asignacion::where('id_solicitud', $solId)->first()->id_usuario_tecnico);

        // 5. Validar que el estado del ticket volvió a "Asignada" (para reiniciar flujo OTP con el nuevo técnico)
        $this->assertEquals('Asignada', Solicitud::find($solId)->estado_solicitud);
    }
}
