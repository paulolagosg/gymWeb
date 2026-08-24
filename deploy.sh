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

echo "==> Enviando a GitHub (git push)"
git push origin main

echo "==> Copiando el build compilado al servidor"
rsync -a private/public/build/ "${SSH_TARGET}:${REMOTE_PATH}/public/build/"

echo "==> Desplegando en el servidor"
ssh "$SSH_TARGET" "REMOTE_PATH='$REMOTE_PATH' REMOTE_STAGING='$REMOTE_STAGING' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail

# Una sesión SSH no interactiva (como esta) no carga .bashrc/.bash_profile, que es
# donde el hosting suele agregar composer al PATH — sin esto, "composer" no se
# encuentra aunque funcione perfecto cuando te conectas a mano. El "|| true" es
# necesario porque set -e cortaría el script si el archivo no existe.
[ -f ~/.bash_profile ] && source ~/.bash_profile || true
[ -f ~/.bashrc ] && source ~/.bashrc || true
[ -f ~/.profile ] && source ~/.profile || true

echo "--- git pull en el staging"
cd "$REMOTE_STAGING"
git pull origin main

echo "--- copiando al directorio real (sin borrar nada no versionado, sin pisar public/build recién copiado)"
rsync -a --exclude='public/build/' "$REMOTE_STAGING/private/" "$REMOTE_PATH/"

cd "$REMOTE_PATH"

echo "--- composer install"
composer install --no-dev --optimize-autoloader --no-interaction

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
