#!/usr/bin/env bash
#
# Despliegue repetible de gymWeb (Laravel) a producción, vía git.
#
# Diseño: el repo gymWeb (este) tiene el código Laravel dentro de su propia carpeta
# private/ — igual que el directorio real de producción se llama "private". Clonar el
# repo directo DENTRO de /home/ampara_cl/gym.ampaya.cl/private crea un private/private/
# anidado (ya pasó una vez). Para evitarlo, el repo se clona en un directorio de staging
# aparte (gymWeb-repo, hermano de private/), y de ahí se copia (rsync, sin --delete) el
# contenido de su private/ hacia el private/ real — así el git nunca corre directamente
# sobre el directorio que sirve las peticiones, y nunca hay ambigüedad de rutas.
#
# Requisito de una sola vez: el setup inicial en el servidor (ver más abajo). Una vez
# hecho eso, cada deploy es: push local -> pull en el staging del servidor -> rsync al
# directorio real -> composer -> migrate -> limpiar caché -> app:doctor.
#
# Esto reemplaza el mecanismo anterior de subir archivos sueltos a mano, que ya causó un
# incidente real (Bloque 22: una migración se quedó sin subir y produjo errores 500 en
# producción hasta corregirlo).
#
# Configuración (editar una sola vez):
SSH_TARGET="ampara_cl@ampaya.cl"                # el mismo que usas para SSH hoy
REMOTE_PATH="/home/ampara_cl/gym.ampaya.cl/private"     # directorio real que sirve la app
REMOTE_STAGING="/home/ampara_cl/gym.ampaya.cl/gymWeb-repo"  # clon git, aparte

# Uso:
#   ./deploy.sh              # push + despliegue
#
# ---------------------------------------------------------------------------
# CONFIGURACIÓN INICIAL EN EL SERVIDOR (correr una sola vez, a mano, por SSH):
#
#   mkdir -p /home/ampara_cl/gym.ampaya.cl/gymWeb-repo
#   cd /home/ampara_cl/gym.ampaya.cl/gymWeb-repo
#   git clone https://github.com/paulolagosg/gymWeb.git .
#   rsync -av /home/ampara_cl/gym.ampaya.cl/gymWeb-repo/private/ /home/ampara_cl/gym.ampaya.cl/private/
#
# El rsync sin --delete solo agrega/actualiza archivos versionados — nunca toca .env,
# vendor/, storage/ real, public/build ni public/storage, porque esos ni siquiera
# existen dentro del clon limpio (están en el .gitignore del repo).
#
# Si el servidor ya tiene un intento previo fallido (un private/private/ anidado o un
# .git suelto dentro del private/ real), avísame antes de correr esto — hay que limpiar
# ese resto primero, con cuidado de no tocar los archivos reales de la app (.env,
# storage/, vendor/, y el app/routes/resources actuales).
# ---------------------------------------------------------------------------

set -euo pipefail

if [[ "$SSH_TARGET" == "CAMBIAR_usuario@host" ]]; then
  echo "ERROR: edita SSH_TARGET en deploy.sh antes de usarlo (línea con CAMBIAR_usuario@host)." >&2
  exit 1
fi

REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$REPO_ROOT"

if [[ -n "$(git status --porcelain -- private/)" ]]; then
  echo "ERROR: hay cambios sin commitear en private/. Commitea (y opcionalmente" >&2
  echo "revisa con 'git diff') antes de desplegar — este script despliega lo que" >&2
  echo "está commiteado, no lo que hay suelto en el disco." >&2
  exit 1
fi

# public/build (CSS/JS compilado por Vite/Tailwind) está en .gitignore a propósito
# (es un artefacto generado, no código fuente) — así que git nunca lo lleva al
# servidor. Hay que compilarlo aquí y copiarlo aparte, con rsync directo.
#
# Este hosting enruta TODA petición a través de Laravel, sin excepción — ni siquiera
# un archivo suelto existente en el disco se sirve directo (confirmado con una prueba:
# un .php de diagnóstico en la raíz también devolvió el 404 de Laravel). No hay bypass
# de archivos estáticos a nivel de servidor, así que copiar a una carpeta "build/" al
# nivel del document root no sirve de nada. La solución fue agregar una ruta explícita
# en Laravel (`Route::get('/build/{path}', ...)`, routes/web.php) que entrega el
# archivo desde public_path('build/...') — por eso ahora basta con UNA sola copia, a
# la ubicación estándar de Laravel.
echo "==> Compilando CSS/JS (npm run build)"
(cd private && npm run build)

# composer tampoco existe en este servidor (confirmado: "which composer" no devuelve
# nada ni en una sesión interactiva) — mismo problema que con Vite/Tailwind. vendor/
# se instala aquí, en local, y se copia al servidor por rsync; el servidor nunca
# necesita ejecutar composer.
#
# Tiene que instalarse en private/vendor/ real (no en un directorio temporal aparte):
# el autoloader de Composer graba rutas RELATIVAS para los archivos que se cargan por
# "files" en composer.json (ej. app/Helpers/ClientesHelper.php) — relativas a la
# posición real de vendor/composer/, dos niveles hacia arriba. Si vendor/ se instala en
# cualquier otro lugar, esa relatividad se rompe y Composer graba una ruta absoluta de
# esta máquina, que no existe en el servidor (así se rompió el primer intento). Encima,
# el post-install de Laravel (`package:discover`) ejecuta ese autoloader de inmediato,
# así que además necesita que app/ exista de verdad donde se instala — un directorio
# temporal vacío no sirve.
#
# Por eso se respalda el vendor/ de desarrollo, se instala uno nuevo --no-dev encima,
# se copia al servidor, y se restaura el de desarrollo enseguida — la ventana en la que
# tu vendor/ local queda "cambiado" es solo mientras corre este bloque.
if [[ -d private/vendor.dev-backup ]]; then
  echo "ERROR: private/vendor.dev-backup ya existe — un deploy anterior no se restauró" >&2
  echo "bien. Revisa a mano antes de continuar (probablemente haya que mover" >&2
  echo "private/vendor.dev-backup de vuelta a private/vendor)." >&2
  exit 1
fi

restore_dev_vendor() {
  if [[ -d private/vendor.dev-backup ]]; then
    rm -rf private/vendor
    mv private/vendor.dev-backup private/vendor
  fi
}
trap restore_dev_vendor EXIT

mv private/vendor private/vendor.dev-backup

echo "==> Instalando dependencias PHP para producción (composer install --no-dev)"
(cd private && composer install --no-dev --optimize-autoloader --no-interaction)

echo "==> Enviando a GitHub (git push)"
git push origin main

echo "==> Copiando el build compilado y vendor/ al servidor"
rsync -a private/public/build/ "${SSH_TARGET}:${REMOTE_PATH}/public/build/"
rsync -a --delete private/vendor/ "${SSH_TARGET}:${REMOTE_PATH}/vendor/"

echo "==> Restaurando vendor/ de desarrollo en local"
restore_dev_vendor

echo "==> Desplegando en el servidor"
ssh "$SSH_TARGET" "REMOTE_PATH='$REMOTE_PATH' REMOTE_STAGING='$REMOTE_STAGING' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail

echo "--- git pull en el staging"
cd "$REMOTE_STAGING"
git pull origin main

echo "--- copiando al directorio real (sin borrar nada no versionado, sin pisar public/build ni vendor/ recién copiados)"
rsync -a --exclude='public/build/' --exclude='vendor/' "$REMOTE_STAGING/private/" "$REMOTE_PATH/"

cd "$REMOTE_PATH"

# bootstrap/cache/packages.php y services.php son cachés generados por Laravel (no
# van en git) con la lista de proveedores de paquetes detectados. Si quedó una versión
# vieja de una instalación anterior con dependencias de desarrollo, sigue apuntando a
# clases que el vendor/ --no-dev recién copiado ya no tiene, y la app no arranca — ni
# siquiera "php artisan" logra bootear para regenerarlo él solo, porque intenta cargar
# los proveedores viejos antes de llegar a ejecutar el comando. Por eso se borra primero
# (Laravel lo reconstruye solo, sin necesitar composer) y recién después se usa artisan.
echo "--- borrando caché vieja de paquetes descubiertos"
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

echo "--- regenerando caché de paquetes descubiertos"
php artisan package:discover --ansi

echo "--- migraciones pendientes"
php artisan migrate --force

echo "--- limpiando config/rutas/vistas cacheadas"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "--- app:doctor (el deploy falla si encuentra algo roto)"
php artisan app:doctor
REMOTE_SCRIPT

echo "==> Deploy terminado."
