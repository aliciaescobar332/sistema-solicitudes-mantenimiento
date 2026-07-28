<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RestablecerPasswordNotification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolSolicitante;
    private Rol $rolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear los roles necesarios
        $this->rolSolicitante = Rol::create([
            'nombre_rol' => 'Solicitante',
            'descripcion' => 'Usuario Normal'
        ]);

        $this->rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion' => 'Admin'
        ]);
    }

    public function test_un_usuario_solicitante_puede_solicitar_recuperar_su_contrasena(): void
    {
        Notification::fake();

        $usuario = User::create([
            'id_rol'   => $this->rolSolicitante->id_rol,
            'nombre'   => 'Juan',
            'apellido' => 'Perez',
            'correo'   => 'juan@rchhospital.sv',
            'password' => Hash::make('password123'),
            'estado'   => 'Activo'
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'correo' => 'juan@rchhospital.sv'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['mensaje']);

        // Verificar que se guardó el token en la BD
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'juan@rchhospital.sv'
        ]);

        // Verificar que la notificación fue enviada
        Notification::assertSentTo($usuario, RestablecerPasswordNotification::class);
    }

    public function test_un_usuario_que_no_es_solicitante_no_puede_recuperar_su_contrasena_de_forma_autonoma(): void
    {
        Notification::fake();

        $usuario = User::create([
            'id_rol'   => $this->rolAdmin->id_rol,
            'nombre'   => 'Carlos',
            'apellido' => 'Admin',
            'correo'   => 'admin@rchhospital.sv',
            'password' => Hash::make('password123'),
            'estado'   => 'Activo'
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'correo' => 'admin@rchhospital.sv'
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'mensaje' => 'La recuperación autónoma de contraseña por correo electrónico está deshabilitada para roles superiores. Por motivos de seguridad de RCH Hospital, por favor comuníquese con el Administrador o Soporte Técnico para restablecer su clave.'
            ]);

        // Verificar que no se creó el token
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'admin@rchhospital.sv'
        ]);

        // Verificar que no se envió notificación
        Notification::assertNotSentTo($usuario, RestablecerPasswordNotification::class);
    }

    public function test_un_usuario_solicitante_puede_restablecer_su_contrasena_con_un_token_valido(): void
    {
        $usuario = User::create([
            'id_rol'   => $this->rolSolicitante->id_rol,
            'nombre'   => 'Juan',
            'apellido' => 'Perez',
            'correo'   => 'juan@rchhospital.sv',
            'password' => Hash::make('password123'),
            'estado'   => 'Activo'
        ]);

        $token = 'token_de_prueba_seguro_123456';

        DB::table('password_reset_tokens')->insert([
            'email'      => 'juan@rchhospital.sv',
            'token'      => Hash::make($token),
            'created_at' => now()
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'correo'                => 'juan@rchhospital.sv',
            'password'              => 'nueva_clave_123',
            'password_confirmation' => 'nueva_clave_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'mensaje' => 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión en el sistema con tus nuevas credenciales.'
            ]);

        // Verificar que se borró el token de la base de datos
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'juan@rchhospital.sv'
        ]);

        // Verificar que la contraseña cambió
        $usuario->refresh();
        $this->assertTrue(Hash::check('nueva_clave_123', $usuario->password));
    }

    public function test_un_token_expirado_no_puede_ser_utilizado(): void
    {
        User::create([
            'id_rol'   => $this->rolSolicitante->id_rol,
            'nombre'   => 'Juan',
            'apellido' => 'Perez',
            'correo'   => 'juan@rchhospital.sv',
            'password' => Hash::make('password123'),
            'estado'   => 'Activo'
        ]);

        $token = 'token_de_prueba_seguro_123456';

        // Insertar token con fecha de creación de hace 61 minutos
        DB::table('password_reset_tokens')->insert([
            'email'      => 'juan@rchhospital.sv',
            'token'      => Hash::make($token),
            'created_at' => now()->subMinutes(61)
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'correo'                => 'juan@rchhospital.sv',
            'password'              => 'nueva_clave_123',
            'password_confirmation' => 'nueva_clave_123'
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'mensaje' => 'El enlace de recuperación ha expirado. Por favor, solicite uno nuevo.'
            ]);

        // Verificar que se eliminó el registro de token expirado
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'juan@rchhospital.sv'
        ]);
    }
}
