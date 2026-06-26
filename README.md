# Sistema de Control de Solicitudes de Servicio General y Mantenimiento

Qué tal, cheros. Este es el repositorio del backend que armé en Laravel para el sistema de solicitudes del hospital. Les dejo esto por acá para que lo levantemos al suave con Docker y Laravel Sail, así no nos complicamos con la base de datos MySQL y lo tenemos todo listo de un solo.

---

## Requisitos Previos

Antes de que empecemos a pullear, por favor asegúrense de tener instalado esto en su compu:
*   Docker Desktop (o Docker Engine si usan Linux)
*   Git

---

## Cómo Clonar y Levantar el Proyecto

Aquí les comparto el paso a paso para clonar y levantar el proyecto en su máquina sin complicaciones:

### 1. Clonar el repositorio
```bash
git clone https://github.com/aliciaescobar332/sistema-solicitudes-mantenimiento.git
cd sistema-solicitudes-mantenimiento
```

### 2. Crear el archivo de configuración local (.env)
Hagan una copia del archivo de ejemplo para configurar sus variables locales:
```bash
cp .env.example .env
```

### 3. Iniciar los contenedores de Docker (Laravel Sail)
Para levantar el servidor y la base de datos en segundo plano, por favor corran este comando:
```bash
./vendor/bin/sail up -d
```
*(Nota: Si es la primera vez que lo corren, se va a tardar su buen ratito en lo que Docker descarga las imágenes de internet y nos levanta todo. Tengan un poquito de paciencia).*

### 4. Generar la clave de la aplicación
Ya cuando vean que los contenedores están listos, por favor corran este comando para generar la llave de seguridad del proyecto:
```bash
./vendor/bin/sail artisan key:generate
```

### 5. Crear la Base de Datos y meter los datos reales de prueba
Para crear las tablas del hospital cabalito como se necesitan y meter de un solo los datos reales de prueba (usuarios con sus roles, sedes y los tickets de ejemplo), denle viaje a este comando:
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## Acceso a la Base de Datos

*   **Esquema SQL Físico**:
    El script DDL con toda la estructura de la base de datos oficial lo he dejado guardadito en:
    `database/docs/estructura_base_datos.sql`
*   **¿Cómo entrar a la base de datos desde la terminal?**
    Si quieren entrar directo a la consola de MySQL dentro de Docker, solo tienen que poner:
    ```bash
    ./vendor/bin/sail mysql
    ```
*   **¿Cómo conectarse con un gestor gráfico?**
    Si les gusta más usar DBeaver, TablePlus o cualquier otro programa, lo pueden configurar con estos accesos sin problemas:
    *   **Host**: 127.0.0.1
    *   **Puerto**: 3306
    *   **Base de datos**: laravel
    *   **Usuario**: sail
    *   **Contraseña**: password

