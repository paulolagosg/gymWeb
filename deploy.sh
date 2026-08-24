#!/usr/bin/env bash
#
# Despliegue repetible de gymWeb (Laravel) a producción, vía git.
#
# Requisito de una sola vez: el servidor debe tener /home/ampara_cl/gym.ampaya.cl/private
# ya adoptado como clon git de este repo (ver instrucciones más abajo — no lo hace este
# script). Una vez hecho eso, cada deploy es: hacer push de lo commiteado localmente, y
# decirle al servidor que haga git pull + composer + migrate + limpiar caché + app:doctor.
#
# Esto reemplaza el mecanismo anterior de subir archivos sueltos a mano, que ya causó un
# incidente real (Bloque 22: una migración se quedó sin subir y produjo errores 500 en
# producción hasta corregirlo). Con git, eso deja de ser posible: el servidor siempre
# termina con exactamente el mismo árbol de archivos que hay en el commit desplegado.
#
# Configuración (editar una sola vez):
SSH_TARGET="CAMBIAR_usuario@host"                       # el mismo que usas para SSH hoy
REMOTE_PATH="/home/ampara_cl/gym.ampaya.cl/private"

# Uso:
#   ./deploy.sh              # push + despliegue
#
# ---------------------------------------------------------------------------
# CONFIGURACIÓN INICIAL EN EL SERVIDOR (correr una sola vez, a mano, por SSH):
#
#   cd /home/ampara_cl/gym.ampaya.cl/private
#   git init
#   git remote add origin https://github.com/paulolagosg/gymWeb.git
#   git fetch origin
#   git checkout -f -b main origin/main
#
# Esto NO borra archivos que ya no están en git (.env, vendor/, storage/ real,
# public/build, public/storage) — git checkout solo toca los archivos que sí están
# versionados. Verificar después con `git status` (debería mostrar solo esos archivos
# ignorados como "untracked", nada más) y con `php artisan app:doctor`.
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

echo "==> Enviando a GitHub (git push)"
git push origin main

echo "==> Desplegando en el servidor"
ssh "$SSH_TARGET" "REMOTE_PATH='$REMOTE_PATH' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail
cd "$REMOTE_PATH"

echo "--- git fetch + reset --hard a origin/main"
git fetch origin
git reset --hard origin/main

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
