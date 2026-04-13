# Operacion local con Docker

## Objetivo

Esta configuracion permite levantar Laravel desde private, pero sirviendo la raiz publica del proyecto desde la carpeta superior.

El punto de entrada HTTP queda en la raiz del repositorio y Laravel sigue corriendo desde private.

## Requisitos

- Docker Desktop levantado
- Estar ubicado en la carpeta private

## Comandos rapidos

Usar el wrapper local:

```bash
./sail-local up -d
./sail-local build
./sail-local exec laravel.test php artisan migrate
./sail-local exec laravel.test npm run build
```

Usa `build` o `up -d --build` solo cuando cambie el Dockerfile o si todavia no existe la imagen local.

Si quieres Vite en modo desarrollo:

```bash
./sail-local exec laravel.test npm run dev -- --host 0.0.0.0
```

## Flujo recomendado de primera ejecucion

1. Levantar contenedores.

```bash
./sail-local up -d --build
```

En ejecuciones normales posteriores:

```bash
./sail-local up -d
```

2. Instalar dependencias PHP si fuese necesario.

```bash
./sail-local exec laravel.test composer install
```

3. Instalar dependencias Node si fuese necesario.

```bash
./sail-local exec laravel.test npm install
```

4. Ejecutar migraciones.

```bash
./sail-local exec laravel.test php artisan migrate
```

5. Generar assets.

Opcion A, compilado estatico:

```bash
./sail-local exec laravel.test npm run build
```

Opcion B, Vite en caliente:

```bash
./sail-local exec laravel.test npm run dev -- --host 0.0.0.0
```

## Accesos locales

- Aplicacion: http://localhost:9999
- MySQL: localhost:3766
- Vite: http://localhost:5173

## Comandos utiles

Ver logs:

```bash
./sail-local logs -f
```

Entrar al contenedor:

```bash
./sail-local exec laravel.test bash
```

Detener contenedores:

```bash
./sail-local down
```

Reiniciar desde cero conservando la configuracion:

```bash
./sail-local down
./sail-local up -d --build
```

Eliminar tambien la base de datos local del contenedor:

```bash
./sail-local down -v
```

## Notas de esta estructura

- El compose monta la raiz del repositorio en el contenedor.
- Laravel se ejecuta desde /var/www/html/private.
- El servidor embebido de PHP usa [router.php](router.php) para servir primero los archivos estaticos reales de la raiz publica.
- Los assets compilados salen desde private/public/build y se publican como /build.
- Los archivos publicos de storage se exponen mediante el enlace simbolico de la raiz apuntando a private/storage/app/public.
- El archivo .env real se mantiene en la raiz del proyecto y private/.env debe ser un enlace simbolico a ../.env.
- El runtime local publicado en `docker/8.4` evita el `apt-get upgrade` completo para acelerar la primera build.
- La build local usa un perfil minimo por defecto. Los paquetes pesados quedan como opcionales mediante variables de entorno.
- Node y npm se instalan desde el binario oficial de Node.js para evitar el fallo de npm detectado con NodeSource en arm64 y evitar la cascada pesada del paquete npm de Ubuntu.

## Paquetes opcionales

Por defecto la imagen local instala solo lo necesario para Laravel, MySQL, Composer, Node, Redis y Xdebug.

Si necesitas herramientas extras, puedes activarlas en [/.env](.env):

```bash
DOCKER_INSTALL_EXTRA_TOOLS=true
DOCKER_INSTALL_POSTGRES_SUPPORT=true
DOCKER_INSTALL_EXTRA_PHP_EXTENSIONS=true
```

Cada variable hace esto:

- `DOCKER_INSTALL_EXTRA_TOOLS`: instala python3, dnsutils, librsvg2-bin, fswatch, ffmpeg y nano.
- `DOCKER_INSTALL_POSTGRES_SUPPORT`: instala `php8.4-pgsql` y el cliente de PostgreSQL.
- `DOCKER_INSTALL_EXTRA_PHP_EXTENSIONS`: instala mongodb, imap, ldap, msgpack, igbinary, swoole, memcached, pcov e imagick.

Si cambias cualquiera de estas variables, reconstruye la imagen:

```bash
./sail-local build --no-cache
./sail-local up -d
```

## Si algo falla

Si la app abre sin estilos o sin JavaScript:

```bash
./sail-local exec laravel.test npm run build
```

Si Laravel no refleja cambios de entorno:

```bash
./sail-local exec laravel.test php artisan config:clear
./sail-local exec laravel.test php artisan cache:clear
```

Si las migraciones ya existian y quieres empezar limpio:

```bash
./sail-local exec laravel.test php artisan migrate:fresh
```
