#!/usr/bin/env bash
set -euo pipefail

# ============================================
# Deploy POS-DW to InfinityFree via FTP
# ============================================
# Usage:
#   bash deploy.sh                  # build + deploy
#   bash deploy.sh --skip-build     # deploy only
#   FTP_PASS=xxx bash deploy.sh     # override password
# ============================================

# ---- Konfigurasi (baca dari .env.deploy atau gunakan default) ----
if [ -f ".env.deploy" ]; then
    set -a
    source .env.deploy
    set +a
fi

FTP_HOST="${FTP_HOST:-ftpupload.net}"
FTP_PORT="${FTP_PORT:-21}"
FTP_USER="${FTP_USER:-}"
FTP_PASS="${FTP_PASS:-}"
REMOTE_DIR="${REMOTE_DIR:-}"
TEMP_DIR="/tmp/pos-dw-deploy"

# ---- Warna output ----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

# ---- Validasi ----
command -v ftp >/dev/null 2>&1 || { error "ftp command tidak ditemukan. Install: apt install ftp"; exit 1; }
command -v rsync >/dev/null 2>&1 || { error "rsync tidak ditemukan. Install: apt install rsync"; exit 1; }

if [ -z "$FTP_USER" ] || [ -z "$FTP_PASS" ]; then
    error "FTP_USER dan FTP_PASS harus diisi!"
    echo ""
    echo "  Buat file .env.deploy (copy dari .env.deploy.example):"
    echo "    cp .env.deploy.example .env.deploy"
    echo "    nano .env.deploy"
    echo ""
    echo "  Atau gunakan environment variable:"
    echo "    FTP_PASS=password bash deploy.sh"
    echo ""
    exit 1
fi

if [ -z "$REMOTE_DIR" ]; then
    error "REMOTE_DIR harus diisi di .env.deploy!"
    exit 1
fi

# ---- Cleanup previous deploy ----
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"/{htdocs,laravel}

# ---- Build frontend ----
if [[ "${1:-}" != "--skip-build" ]]; then
    info "Build frontend assets..."
    npm ci --silent 2>/dev/null || npm install --silent
    npm run build
else
    warn "Skipping frontend build (--skip-build)"
fi

# ---- Copy Laravel source ----
info "Menyiapkan struktur deployment..."
rsync -a \
    --exclude='public' \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.ddev' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='deploy.sh' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='storage/app' \
    --exclude='storage/debugbar' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache/data' \
    --exclude='vendor' \
    . "$TEMP_DIR/laravel/"

# ---- Copy vendor (pastikan sudah install) ----
if [ -d "vendor" ]; then
    cp -r vendor "$TEMP_DIR/laravel/vendor"
else
    info "vendor/ tidak ditemukan. Install dependencies dulu..."
    composer install --no-dev --optimize-autoloader --no-interaction
    cp -r vendor "$TEMP_DIR/laravel/vendor"
fi

# ---- Ensure storage directories ----
mkdir -p "$TEMP_DIR/laravel/storage"/{app/public,framework/{cache,sessions,views},logs}
mkdir -p "$TEMP_DIR/laravel/bootstrap/cache"

# ---- Copy public/ contents to htdocs/ ----
rsync -a public/ "$TEMP_DIR/htdocs/"

# ---- Create optimized htdocs/index.php ----
info "Membuat htdocs/index.php yang dioptimalkan..."
cp deploy-scripts/htdocs-index.php "$TEMP_DIR/htdocs/index.php"

# ---- Copy .env.production jika ada, atau buat dari .env ----
if [ -f ".env.production" ]; then
    cp .env.production "$TEMP_DIR/laravel/.env"
    info "Menggunakan .env.production"
elif [ -f ".env" ]; then
    cp .env "$TEMP_DIR/laravel/.env"
    info "Menggunakan .env (pastikan sudah benar untuk production)"
else
    warn ".env tidak ditemukan. Buat dulu file .env untuk production!"
    cat > "$TEMP_DIR/laravel/.env" << 'ENVFILE'
APP_NAME="POS-DW"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos-dw.site.je
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@pos-dw.site.je"
MAIL_FROM_NAME="${APP_NAME}"
ENVFILE
    warn "ISI DULU konfigurasi database di $TEMP_DIR/laravel/.env sebelum upload!"
    warn "Kemudian jalankan: php artisan key:generate di folder tersebut"
fi

# ---- Optimasi Laravel ----
cd "$TEMP_DIR/laravel"
php artisan key:generate --force 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true
cd "$OLDPWD"

# ---- Upload via FTP ----
info "Upload ke InfinityFree via FTP ($FTP_HOST)..."
echo ""

ftp -n "$FTP_HOST" "$FTP_PORT" << FTP_SCRIPT
quote USER $FTP_USER
quote PASS $FTP_PASS
binary
prompt off

cd $REMOTE_DIR

# Hapus dulu direktori lama
delete laravel/.env
rmdir laravel
rmdir htdocs

# Buat direktori baru
mkdir laravel
mkdir htdocs

# Upload Laravel source
lcd $TEMP_DIR/laravel
cd laravel
mput -r *
cd $REMOTE_DIR

# Upload public contents
lcd $TEMP_DIR/htdocs
cd htdocs
mput -r *

quit
FTP_SCRIPT

echo ""
info "Upload selesai!"

# ---- Bersihkan ----
rm -rf "$TEMP_DIR"
info "Selesai! Cek https://adpwork.page.gd"
