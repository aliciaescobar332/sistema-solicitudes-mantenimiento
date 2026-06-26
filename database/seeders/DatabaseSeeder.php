<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Unidad;
use App\Models\Solicitud;
use App\Models\Asignacion;
use App\Models\Bitacora;
use App\Models\Encuesta;
use App\Models\HistorialCambio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1. ROLES DEL SISTEMA (según el PDF del proyecto)
        // ------------------------------------------------------------------
        $rolAdmin   = Rol::firstOrCreate(['nombre_rol' => 'Administrador'],   ['descripcion' => 'Acceso total al sistema y configuraciones']);
        $rolCoordi  = Rol::firstOrCreate(['nombre_rol' => 'Coordinador'],     ['descripcion' => 'Asigna, clasifica y supervisa el trabajo de mantenimiento']);
        $rolTecnico = Rol::firstOrCreate(['nombre_rol' => 'Técnico'],         ['descripcion' => 'Ejecuta las reparaciones y reporta bitácoras']);
        $rolSolicit = Rol::firstOrCreate(['nombre_rol' => 'Solicitante'],     ['descripcion' => 'Reporta daños y califica el servicio final']);

        // ------------------------------------------------------------------
        // 2. SEDES (Red de Clínicas y Hospitales RCH)
        // ------------------------------------------------------------------
        $sedeCentral = Sede::firstOrCreate(
            ['nombre_sede' => 'Hospital Central RCH'],
            ['direccion'   => 'Km. 4.5 Carretera al Puerto, San Salvador']
        );

        $sedeEscalon = Sede::firstOrCreate(
            ['nombre_sede' => 'Clínica Médica Escalón'],
            ['direccion'   => 'Paseo General Escalón, San Salvador']
        );

        $sedeSantaElena = Sede::firstOrCreate(
            ['nombre_sede' => 'Clínica Médica Santa Elena'],
            ['direccion'   => 'Boulevard Santa Elena, Antiguo Cuscatlán']
        );

        // ------------------------------------------------------------------
        // 3. UNIDADES ORGANIZATIVAS (Específicas de la sede principal)
        // ------------------------------------------------------------------
        $unidades = [
            'UCI (Intensivos)',
            'Quirófano 1',
            'Quirófano 2',
            'Quirófano 4',
            'Área Quirúrgica',
            'Emergencias',
            'Radiología',
            'Administración',
            'Maternidad',
            'Pabellón A',
            'Laboratorio Clínico',
            'Farmacia'
        ];

        $unidadesModels = [];
        foreach ($unidades as $nombre) {
            $unidadesModels[$nombre] = Unidad::firstOrCreate(
                ['nombre_unidad' => $nombre, 'id_sede' => $sedeCentral->id_sede]
            );
        }

        // ------------------------------------------------------------------
        // 4. CUENTAS DE USUARIO REALES
        // ------------------------------------------------------------------
        $userAdmin = User::firstOrCreate(
            ['correo' => 'admin@rchhospital.sv'],
            [
                'id_rol'   => $rolAdmin->id_rol,
                'nombre'   => 'Carlos',
                'apellido' => 'Administrador',
                'password' => Hash::make('Admin2024!'),
                'estado'   => 'Activo',
            ]
        );

        $userCoordi = User::firstOrCreate(
            ['correo' => 'coordinador@rchhospital.sv'],
            [
                'id_rol'   => $rolCoordi->id_rol,
                'nombre'   => 'María',
                'apellido' => 'García',
                'password' => Hash::make('Coord2024!'),
                'estado'   => 'Activo',
            ]
        );

        $userTecnico = User::firstOrCreate(
            ['correo' => 'tecnico@rchhospital.sv'],
            [
                'id_rol'   => $rolTecnico->id_rol,
                'nombre'   => 'Jorge',
                'apellido' => 'Martínez',
                'password' => Hash::make('Tec2024!'),
                'estado'   => 'Activo',
            ]
        );

        $userSolicit1 = User::firstOrCreate(
            ['correo' => 'solicitante@rchhospital.sv'],
            [
                'id_rol'   => $rolSolicit->id_rol,
                'nombre'   => 'Ana',
                'apellido' => 'López',
                'password' => Hash::make('Sol2024!'),
                'estado'   => 'Activo',
            ]
        );

        $userSolicit2 = User::firstOrCreate(
            ['correo' => 'roberto.gomez@rchhospital.sv'],
            [
                'id_rol'   => $rolSolicit->id_rol,
                'nombre'   => 'Roberto',
                'apellido' => 'Gómez',
                'password' => Hash::make('Doctor2024!'),
                'estado'   => 'Activo',
            ]
        );

        // ------------------------------------------------------------------
        // 5. SOLICITUDES Y TICKETS REALES (Según los mockups del PDF)
        // ------------------------------------------------------------------

        // Solicitud 1: Aire Acondicionado Quirófano 4 (Pendiente)
        $solicitud1 = Solicitud::firstOrCreate(
            ['titulo' => 'Reparación Aire Acondicionado - Quirófano 4'],
            [
                'id_usuario_solicitante' => $userSolicit1->id_usuario,
                'id_unidad'              => $unidadesModels['Quirófano 4']->id_unidad,
                'descripcion'            => 'Falla en la Unidad Central de Enfriamiento del Quirófano 4. La temperatura ambiental supera los 25 grados y pone en riesgo las cirugías programadas.',
                'prioridad'              => 'Alta',
                'estado_solicitud'       => 'Abierta',
                'fecha_apertura'         => Carbon::now()->subDays(2),
            ]
        );

        // Solicitud 2: Limpieza Profunda Área Quirúrgica (En Proceso y Asignada)
        $solicitud2 = Solicitud::firstOrCreate(
            ['titulo' => 'Limpieza Profunda - Quirófano 1'],
            [
                'id_usuario_solicitante' => $userSolicit1->id_usuario,
                'id_unidad'              => $unidadesModels['Quirófano 1']->id_unidad,
                'descripcion'            => 'Protocolo de Esterilización profundo post-cirugía cardiovascular de alta complejidad en Quirófano 1.',
                'prioridad'              => 'Media',
                'estado_solicitud'       => 'En Proceso',
                'fecha_apertura'         => Carbon::now()->subDays(1),
            ]
        );

        // Crear asignación para Solicitud 2
        $asignacion2 = Asignacion::firstOrCreate(
            ['id_solicitud' => $solicitud2->id_solicitud],
            [
                'id_usuario_tecnico'     => $userTecnico->id_usuario,
                'id_usuario_coordinador' => $userCoordi->id_usuario,
                'fecha_asignacion'       => Carbon::now()->subDays(1)->addHours(2),
            ]
        );

        // Solicitud 3: Revisión Eléctrica Pabellón A (Cerrada y Evaluada)
        $solicitud3 = Solicitud::firstOrCreate(
            ['titulo' => 'Revisión Eléctrica Pabellón A'],
            [
                'id_usuario_solicitante' => $userSolicit1->id_usuario,
                'id_unidad'              => $unidadesModels['Pabellón A']->id_unidad,
                'descripcion'            => 'Mantenimiento Preventivo Mensual de tomacorrientes e iluminación general en Pabellón A.',
                'prioridad'              => 'Baja',
                'estado_solicitud'       => 'Cerrada',
                'fecha_apertura'         => Carbon::now()->subDays(5),
            ]
        );

        // Asignación de Solicitud 3
        $asignacion3 = Asignacion::firstOrCreate(
            ['id_solicitud' => $solicitud3->id_solicitud],
            [
                'id_usuario_tecnico'     => $userTecnico->id_usuario,
                'id_usuario_coordinador' => $userCoordi->id_usuario,
                'fecha_asignacion'       => Carbon::now()->subDays(5)->addHours(1),
            ]
        );

        // Bitácora de Solicitud 3
        $bitacora3 = Bitacora::firstOrCreate(
            ['id_asignacion' => $asignacion3->id_asignacion],
            [
                'descripcion_trabajo' => 'Se revisaron todos los contactos eléctricos del Pabellón A, sustituyendo tres tomacorrientes dañados y una luminaria de pasillo que parpadeaba.',
                'materiales'          => '3 Tomacorrientes dobles, 1 Luminaria LED 24W.',
                'fecha_inicio'        => Carbon::now()->subDays(4)->addHours(8),
                'fecha_fin'           => Carbon::now()->subDays(4)->addHours(12),
            ]
        );

        // Encuesta de Solicitud 3
        $encuesta3 = Encuesta::firstOrCreate(
            ['id_solicitud' => $solicitud3->id_solicitud],
            [
                'calificacion_rapidez'    => 5,
                'calificacion_calidad'    => 4,
                'calificacion_amabilidad' => 5,
                'comentarios'             => 'El trabajo se realizó de forma muy profesional, ordenada y en tiempo récord.',
            ]
        );

        // Solicitud 4: Reparación de Monitor de Signos Vitales UCI (En Proceso con Bitácoras Avanzadas)
        $solicitud4 = Solicitud::firstOrCreate(
            ['titulo' => 'Reparación de Monitor de Signos Vitales'],
            [
                'id_usuario_solicitante' => $userSolicit2->id_usuario, // Dr. Roberto Gómez
                'id_unidad'              => $unidadesModels['UCI (Intensivos)']->id_unidad,
                'descripcion'            => 'Fallo intermitente en el encendido y conexión de energía en el Monitor B40 (GE Healthcare) de la UCI Cama 4. El personal de enfermería reporta que el cable parece tener un falso contacto en la entrada posterior del equipo.',
                'prioridad'              => 'Alta',
                'estado_solicitud'       => 'En Proceso',
                'fecha_apertura'         => Carbon::now()->subHours(6),
            ]
        );

        // Asignación de Solicitud 4
        $asignacion4 = Asignacion::firstOrCreate(
            ['id_solicitud' => $solicitud4->id_solicitud],
            [
                'id_usuario_tecnico'     => $userTecnico->id_usuario,
                'id_usuario_coordinador' => $userCoordi->id_usuario,
                'fecha_asignacion'       => Carbon::now()->subHours(5),
            ]
        );

        // Bitácora 4.1: Inicio del Diagnóstico
        $bitacora4_1 = Bitacora::firstOrCreate(
            [
                'id_asignacion'       => $asignacion4->id_asignacion,
                'descripcion_trabajo' => 'Equipo recibido en taller para revisión profunda e inicio del diagnóstico.',
            ],
            [
                'materiales'   => null,
                'fecha_inicio' => Carbon::now()->subHours(4),
                'fecha_fin'    => Carbon::now()->subHours(3),
            ]
        );

        // Bitácora 4.2: Hallazgo y material requerido
        $bitacora4_2 = Bitacora::firstOrCreate(
            [
                'id_asignacion'       => $asignacion4->id_asignacion,
                'descripcion_trabajo' => 'Se procedió a desmontar la cubierta trasera del monitor. Se observa acumulación de polvo y desgaste en el pin central del conector de alimentación. Se procede a realizar soldadura y cambio de pieza.',
            ],
            [
                'materiales'   => 'Conector DC Hembra 12V (1x), Soldadura de Plata (gr) (5g).',
                'fecha_inicio' => Carbon::now()->subHours(2),
                'fecha_fin'    => Carbon::now()->subHours(1),
            ]
        );
    }
}