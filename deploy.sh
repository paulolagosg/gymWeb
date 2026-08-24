#!/usr/bin/env bash
#
# Despliegue repetible de gymWeb (Laravel) a producción.
#
# Qué resuelve: hoy el código se sube archivo por archivo a mano, lo que ya causó un
# incidente real (Bloque 22: la migración se olvidó y produjo errores 500 en
# producción hasta corregirlo). Este script sincroniza exactamente el conjunto de
# archivos versionados en git bajo private/ — nunca se olvida ninguno porque la lista
# la genera git, no una copia manual — y encadena los pasos que siempre hay que correr
# después (composer, migrate, limpiar caché de config, verificar con app:doctor).
#
# No requiere que el servidor tenga git ni que hayas hecho commit local: usa el
# contenido actual del disco (git solo se usa acá, en tu máquina, para saber qué
# archivos pertenecen al proyecto y respetar el .gitignore — así jamás sube .env,
# vendor/, node_modules ni storage real).
#
# Configuración (editar una sola vez):
SSH_TARGET="CAMBIAR_usuario@host"                       # el mismo que usas para SSH hoy
REMOTE_PATH="/home/ampara_cl/gym.ampaya.cl/private"

# Uso:
#   ./deploy.sh              # sincroniza y despliega
#   ./deploy.sh --dry-run    # muestra qué se copiaría, sin tocar el servidor ni correr nada remoto

set -euo pipefail

if [[ "$SSH_TARGET" == "CAMBIAR_usuario@host" ]]; then
  echo "ERROR: edita SSH_TARGET en deploy.sh antes de usarlo (línea con CAMBIAR_usuario@host)." >&2
  exit 1
fi

REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$REPO_ROOT"

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=1
  echo "==> DRY RUN: no se va a modificar nada en el servidor"
fi

if [[ -n "$(git status --porcelain -- private/)" ]]; then
  echo "AVISO: hay cambios en private/ sin commitear en git. Este script sube el"
  echo "estado actual del disco (no el último commit), así que se van a subir de"
  echo "todas formas — pero conviene que sepas que no coinciden con git en este momento."
  echo
fi

echo "==> Sincronizando archivos versionados de private/ con el servidor"
# Sin --delete a propósito: el destino tiene archivos esenciales que nunca están en
# git (.env, vendor/, storage/ real, public/build, public/storage) porque el .gitignore
# los excluye. Con --files-from, --delete borraría todo eso por no estar en la lista.
# Costo aceptado: si algún día se elimina un archivo del repo (raro), hay que borrarlo
# a mano del servidor una vez — mucho más seguro que arriesgar borrar en cada deploy.
RSYNC_FLAGS=(-av)
if [[ "$DRY_RUN" -eq 1 ]]; then
  RSYNC_FLAGS+=(--dry-run)
fi

git ls-files -z -- private/ \
  | rsync "${RSYNC_FLAGS[@]}" --files-from=- --from0 "$REPO_ROOT/" "${SSH_TARGET}:$(dirname "$REMOTE_PATH")/"

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo "==> Dry run terminado. No se ejecutó nada en el servidor."
  exit 0
fi

echo "==> Ejecutando pasos remotos: composer, migraciones, limpieza de caché, app:doctor"
ssh "$SSH_TARGET" "REMOTE_PATH='$REMOTE_PATH' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail
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
