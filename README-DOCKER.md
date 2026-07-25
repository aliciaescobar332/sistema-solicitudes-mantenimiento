# Cómo levantar el proyecto Hospital con Docker

## 1. Copiar los archivos a tu proyecto

Copia estos 5 archivos a la raíz de la carpeta:
`C:\laragon\www\hospital\hospital-salud-integral-source\`

- `Dockerfile`
- `docker-compose.yml`
- `.dockerignore`
- La carpeta `docker/` completa (con `docker/nginx/default.conf` adentro)
- `.env.docker` → renómbralo a `.env` (reemplaza el que ya está ahí)

## 2. Requisitos previos

- Tener **Docker Desktop** instalado y corriendo en Windows.
- Cerrar Laragon (o al menos parar MySQL/Apache de Laragon) para que no
  choquen los puertos 3306 y 8000 con los contenedores de Docker.

## 3. Levantar todo

Abre una terminal (PowerShell o la terminal integrada del editor) dentro de:
```
C:\laragon\www\hospital\hospital-salud-integral-source\
```

Y ejecuta:
```
docker compose up --build
```

La primera vez puede tardar varios minutos (descarga imágenes, instala
dependencias de PHP y compila el frontend con Vite).

## 4. Ejecutar migraciones (crear las tablas en la base de datos)

En otra terminal, con los contenedores ya corriendo:
```
docker compose exec app php artisan migrate
```

Si el repo trae seeders con datos de prueba:
```
docker compose exec app php artisan db:seed
```

## 5. Acceder a la aplicación

- **App (Laravel + Vue):** http://localhost:8000
- **phpMyAdmin** (administrar la base de datos visualmente): http://localhost:8080
  - Usuario: `root`
  - Contraseña: `root_pass`

## 6. Comandos útiles

Detener todo:
```
docker compose down
```

Detener y borrar también los datos de la base de datos:
```
docker compose down -v
```

Ver logs de un servicio:
```
docker compose logs -f app
```

Entrar a la consola del contenedor de la app:
```
docker compose exec app sh
```

## 7. Notas

- La base de datos se llama `hospital`, usuario `hospital_user`,
  contraseña `hospital_pass` (definidos en `docker-compose.yml` y `.env`).
  Puedes cambiarlos antes de levantar el proyecto si quieres otros valores.
- Como mencionaste que la base de datos local solo tenía datos de prueba,
  no se migró nada — el contenedor arranca con una base de datos limpia.
  Usa `php artisan db:seed` (paso 4) si el proyecto trae datos de ejemplo.
