# Panduan Deploy Laravel ke InfinityFree

## Prasyarat
- Domain sudah ditambahkan di InfinityFree
- Akun FTP sudah aktif
- GitHub repo sudah dibuat

---

## Step 1 — Buat Struktur File

### 1a. Buat `deploy-scripts/htdocs-index.php`

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../laravel/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../laravel/vendor/autoload.php';

$app = require_once __DIR__.'/../laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

**Simpan di:** `.github/deploy-scripts/htdocs-index.php`

### 1b. Buat `.github/workflows/deploy.yml`

```yaml
name: Deploy to InfinityFree

on:
  push:
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: mbstring, pdo_mysql, gd, intl, zip
          tools: composer:v2

      - name: Install PHP dependencies
        run: |
          composer install --no-dev --optimize-autoloader --no-interaction

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: 22

      - name: Build assets
        run: |
          npm ci
          npm run build

      - name: Prepare deployment structure
        run: |
          mkdir -p deploy/htdocs deploy/laravel
          rsync -a --exclude='public' --exclude='deploy' --exclude='.git' --exclude='.github' --exclude='.ddev' --exclude='node_modules' --exclude='tests' --exclude='storage/app' --exclude='storage/debugbar' --exclude='storage/logs' --exclude='storage/framework/cache/data' . deploy/laravel/
          mkdir -p deploy/laravel/storage/app/public
          mkdir -p deploy/laravel/storage/framework/cache
          mkdir -p deploy/laravel/storage/framework/sessions
          mkdir -p deploy/laravel/storage/framework/views
          mkdir -p deploy/laravel/storage/logs
          mkdir -p deploy/laravel/bootstrap/cache
          rsync -a public/ deploy/htdocs/
          cp .github/deploy-scripts/htdocs-index.php deploy/htdocs/index.php

      - name: Create .env for production
        run: |
          cat > deploy/laravel/.env << EOF
          APP_NAME="APP_NAME_ANDA"
          APP_ENV=production
          APP_DEBUG=false
          APP_URL=https://domain-anda.com

          APP_LOCALE=id
          APP_FALLBACK_LOCALE=id
          APP_FAKER_LOCALE=id_ID

          DB_CONNECTION=mysql
          DB_HOST=\${{ secrets.DB_HOST }}
          DB_PORT=3306
          DB_DATABASE=\${{ secrets.DB_DATABASE }}
          DB_USERNAME=\${{ secrets.DB_USERNAME }}
          DB_PASSWORD=\${{ secrets.DB_PASSWORD }}

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
          MAIL_FROM_ADDRESS="noreply@domain-anda.com"
          MAIL_FROM_NAME="\${APP_NAME}"

          APP_KEY=\${{ secrets.APP_KEY }}

          VITE_APP_NAME="\${APP_NAME}"
          EOF

      - name: Cache Laravel config & routes
        run: |
          cd deploy/laravel
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan event:cache

      - name: Deploy to InfinityFree via FTP
        uses: SamKirkland/FTP-Deploy-Action@v4.4.0
        with:
          server: ftpupload.net
          username: \${{ secrets.FTP_USERNAME }}
          password: \${{ secrets.FTP_PASSWORD }}
          local-dir: deploy/
          server-dir: nama-domain-anda/
          protocol: ftp
          port: 21
          security: loose
          log-level: verbose
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/tests/**
```

---

## Step 2 — Setup Database di InfinityFree

1. Login [control.infinityfree.com](https://control.infinityfree.com)
2. Klik **MySQL** → **New Database**
3. Catat: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

---

## Step 3 — Generate APP_KEY

```bash
php artisan key:generate --show
# Output: base64:abc123def456...
```

---

## Step 4 — Tambah GitHub Secrets

Buka: `https://github.com/username/repo/settings/secrets/actions`

Tambahkan 6 secrets:

| Name | Value |
|------|-------|
| `FTP_USERNAME` | `if0_xxxxx` |
| `FTP_PASSWORD` | Password FTP |
| `APP_KEY` | `base64:abc...` |
| `DB_HOST` | Dari MySQL InfinityFree |
| `DB_DATABASE` | Dari MySQL InfinityFree |
| `DB_USERNAME` | Dari MySQL InfinityFree |
| `DB_PASSWORD` | Dari MySQL InfinityFree |

---

## Step 5 — Push ke main

```bash
git checkout main
git merge dev
git push origin main
```

Buka **GitHub → Actions** untuk pantau progress.

---

## Step 6 — Verifikasi

Buka `https://domain-anda.com`

**Error umum:**
| Error | Penyebab | Solusi |
|-------|----------|--------|
| 500 | `.env` atau `APP_KEY` salah | Cek APP_KEY secret |
| 404 (selain /) | `.htaccess` tidak ada | Pastikan `.htaccess` ikut terupload |
| No such file... | `vendor/` tidak lengkap | Cek composer install step |
| Blank page | `storage/` permission | Set 755 di folder `storage/framework/` |

---

## ⚠️ Aturan Penting InfinityFree

1. **`server-dir` pakai RELATIVE path** dari root FTP, **bukan absolute** Linux
   - ✅ `server-dir: domain-anda.com/`
   - ❌ `server-dir: /home/vol.../if0_xxxxx/domain-anda.com/`

2. **Root FTP** langsung berada di `/home/vol.../if0_xxxxx/`

3. **Struktur folder domain:**
   ```
   domain-anda.com/
   ├── htdocs/   ← web root (public/)
   └── laravel/  ← source code (di luar web root)
   ```

4. **Domain document root** = `domain-anda.com/htdocs`

5. **File `htdocs/index.php`** harus load Laravel dari `../laravel/`:
   ```php
   require __DIR__.'/../laravel/vendor/autoload.php';
   ```

6. **FTP action** akan auto-create folder yang belum ada (tidak perlu step `mkdir` manual)

---

## Struktur Final di Server

```
/home/vol.../if0_xxxxx/
├── htdocs/                    ← (default, tidak dipakai)
├── domain-anda.com/
│   ├── htdocs/                ← web root
│   │   ├── index.php          ← loader Laravel
│   │   ├── .htaccess
│   │   ├── build/
│   │   └── ...
│   └── laravel/               ← source code
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── resources/
│       ├── routes/
│       ├── storage/
│       ├── vendor/
│       ├── .env
│       └── artisan
└── domain-lain.com/
    └── ...
```
