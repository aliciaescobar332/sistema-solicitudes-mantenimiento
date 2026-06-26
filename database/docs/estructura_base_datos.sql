-- =====================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS OFICIAL (MYSQL 8.x)
-- Proyecto: Sistema de Control de Solicitudes de Servicio General y Mantenimiento
-- Cliente: Hospital Privado Salud Integral (RCH Salud Integral)
-- Autor: Equipo 18 / Grupo 4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS laravel;
USE laravel;

-- Desactivar temporalmente restricciones de llaves foráneas para evitar problemas al reconstruir
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. TABLA: roles
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id_rol CHAR(36) NOT NULL,
    nombre_rol VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY roles_nombre_rol_unique (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. TABLA: users (Usuarios)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id_usuario CHAR(36) NOT NULL,
    id_rol CHAR(36) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    apellido VARCHAR(255) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Activo', -- Activo / Inactivo
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY users_correo_unique (correo),
    KEY users_id_rol_index (id_rol),
    CONSTRAINT fk_users_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. TABLA: sedes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS sedes;
CREATE TABLE sedes (
    id_sede CHAR(36) NOT NULL,
    nombre_sede VARCHAR(100) NOT NULL,
    direccion TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_sede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. TABLA: unidades
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS unidades;
CREATE TABLE unidades (
    id_unidad CHAR(36) NOT NULL,
    id_sede CHAR(36) NOT NULL,
    nombre_unidad VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_unidad),
    KEY unidades_id_sede_index (id_sede),
    CONSTRAINT fk_unidades_sede FOREIGN KEY (id_sede) REFERENCES sedes (id_sede) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. TABLA: solicitudes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS solicitudes;
CREATE TABLE solicitudes (
    id_solicitud CHAR(36) NOT NULL,
    id_usuario_solicitante CHAR(36) NOT NULL,
    id_unidad CHAR(36) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('Baja', 'Media', 'Alta', 'Crítica') DEFAULT NULL,
    estado_solicitud ENUM('Abierta', 'Asignada', 'En Proceso', 'Validada', 'Cerrada', 'Devuelta', 'Cancelada') NOT NULL DEFAULT 'Abierta',
    fecha_apertura TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_solicitud),
    KEY solicitudes_id_usuario_solicitante_index (id_usuario_solicitante),
    KEY solicitudes_id_unidad_index (id_unidad),
    CONSTRAINT fk_solicitudes_usuario_solicitante FOREIGN KEY (id_usuario_solicitante) REFERENCES users (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_solicitudes_unidad FOREIGN KEY (id_unidad) REFERENCES unidades (id_unidad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. TABLA: evidencias
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS evidencias;
CREATE TABLE evidencias (
    id_evidencia CHAR(36) NOT NULL,
    id_solicitud CHAR(36) NOT NULL,
    url_recurso TEXT NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL, -- Imagen / Documento
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_evidencia),
    KEY evidencias_id_solicitud_index (id_solicitud),
    CONSTRAINT fk_evidencias_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. TABLA: asignaciones
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS asignaciones;
CREATE TABLE asignaciones (
    id_asignacion CHAR(36) NOT NULL,
    id_solicitud CHAR(36) NOT NULL,
    id_usuario_tecnico CHAR(36) NOT NULL,
    id_usuario_coordinador CHAR(36) NOT NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_asignacion),
    UNIQUE KEY asignaciones_id_solicitud_unique (id_solicitud),
    KEY asignaciones_id_usuario_tecnico_index (id_usuario_tecnico),
    KEY asignaciones_id_usuario_coordinador_index (id_usuario_coordinador),
    CONSTRAINT fk_asignaciones_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud) ON DELETE CASCADE,
    CONSTRAINT fk_asignaciones_usuario_tecnico FOREIGN KEY (id_usuario_tecnico) REFERENCES users (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_asignaciones_usuario_coordinador FOREIGN KEY (id_usuario_coordinador) REFERENCES users (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. TABLA: bitacoras
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS bitacoras;
CREATE TABLE bitacoras (
    id_bitacora CHAR(36) NOT NULL,
    id_asignacion CHAR(36) NOT NULL,
    descripcion_trabajo TEXT NOT NULL,
    materiales TEXT DEFAULT NULL,
    fecha_inicio TIMESTAMP NULL DEFAULT NULL,
    fecha_fin TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_bitacora),
    KEY bitacoras_id_asignacion_index (id_asignacion),
    CONSTRAINT fk_bitacoras_asignacion FOREIGN KEY (id_asignacion) REFERENCES asignaciones (id_asignacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. TABLA: encuestas
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS encuestas;
CREATE TABLE encuestas (
    id_encuesta CHAR(36) NOT NULL,
    id_solicitud CHAR(36) NOT NULL,
    calificacion_rapidez INT NOT NULL, -- 1 a 5
    calificacion_calidad INT NOT NULL, -- 1 a 5
    calificacion_amabilidad INT NOT NULL, -- 1 a 5
    comentarios TEXT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_encuesta),
    UNIQUE KEY encuestas_id_solicitud_unique (id_solicitud),
    CONSTRAINT fk_encuestas_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. TABLA: historial_cambios (Auditoría)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS historial_cambios;
CREATE TABLE historial_cambios (
    id_historial CHAR(36) NOT NULL,
    id_solicitud CHAR(36) NOT NULL,
    id_usuario CHAR(36) NOT NULL,
    campo_modificado VARCHAR(100) NOT NULL,
    valor_anterior TEXT DEFAULT NULL,
    valor_nuevo TEXT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_historial),
    KEY historial_cambios_id_solicitud_index (id_solicitud),
    KEY historial_cambios_id_usuario_index (id_usuario),
    CONSTRAINT fk_historial_cambios_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud) ON DELETE CASCADE,
    CONSTRAINT fk_historial_cambios_usuario FOREIGN KEY (id_usuario) REFERENCES users (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rehabilitar restricciones de llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;
