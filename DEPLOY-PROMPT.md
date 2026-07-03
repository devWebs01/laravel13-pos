# DEPLOY LARAVEL KE INFINITYFREE — PROMPT TEMPLATE

> Copy-paste prompt ini ke AI assistant untuk deploy aplikasi Laravel baru ke InfinityFree.
> Tinggal ganti value yang di kurung siku `[...]`.

---

## INSTRUKSI UNTUK AI

Saya ingin deploy aplikasi Laravel ke InfinityFree.
Bantu saya setup auto-deploy via GitHub Actions menggunakan FTP.

### DATA AKUN INFINITYFREE

- FTP Host: `ftpupload.net`
- FTP Port: `21`
- FTP Username: `[if0_xxxxx]`
- FTP Password: `[password_ftp]`
- Domain: `[domain-anda.com]`
- Home Directory: `/home/vol[xx]/infinityfree.com/if0_[xxxxx]/`

### DATA APLIKASI

- Nama App: `[Nama Aplikasi]`
- APP_URL: `https://[domain-anda.com]`
- Lokasi file: `.github/deploy-scripts/htdocs-index.php`
- Branch: `main` (trigger auto-deploy)

### GITHUB SECRETS YANG DIBUTUHKAN

| Secret | Keterangan |
|--------|------------|
| `FTP_USERNAME` | Username FTP InfinityFree |
| `FTP_PASSWORD` | Password FTP InfinityFree |
| `APP_KEY` | Hasil dari `php artisan key:generate --show` |
| `DB_HOST` | Host MySQL dari InfinityFree |
| `DB_DATABASE` | Nama database |
| `DB_USERNAME` | User database |
| `DB_PASSWORD` | Password database |

### STRUKTUR DEPLOY

```
domain-anda.com/
├── htdocs/   ← web root (public/)
└── laravel/  ← source code (luar web root)
```

### FILE YANG DIBUTUHKAN

**1. `.github/deploy-scripts/htdocs-index.php`**
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

**2. `.github/workflows/deploy.yml`** — Buatkan workflow dengan:
- Trigger: push ke `main` + workflow_dispatch
- PHP 8.4, Node 22
- `composer install --no-dev`
- `npm ci && npm run build`
- Prepare: `deploy/htdocs/` (dari public/) dan `deploy/laravel/` (source)
- Buat .env dari GitHub Secrets
- `php artisan config:cache`, `route:cache`, `view:cache`, `event:cache`
- Upload via `SamKirkland/FTP-Deploy-Action@v4.4.0`
- `server-dir: nama-domain.anda/` (relative path dari root FTP, BUKAN absolute)
- `security: loose`
- Exclude: `.git*`, `node_modules/`, `tests/`

**3. Update `.gitignore`** — Tambahkan:
```
.lerd.yaml
.mcp.json
.env.deploy
```

---

### ATURAN PENTING

1. **`server-dir` HARUS relative path** — Contoh: `domain-anda.com/`
   - ❌ Jangan pakai absolute: `/home/vol.../if0_xxxxx/domain-anda.com/`
2. **JANGAN buat step "Create remote directories"** — FTP action auto-create folder
3. **JANGAN generate key ulang** — APP_KEY dari secret, bukan `php artisan key:generate`
4. **`${{ secrets.xxx }}` akan di-expand** oleh GitHub Actions — boleh di dalam heredoc
5. **`\${APP_NAME}`** di .env — escape `$` agar tidak di-expand shell
6. **Domain document root** = `domain-anda.com/htdocs`
7. **Root FTP** = `/home/vol.../infinityfree.com/if0_xxxxx/`

---

### SETELAH DEPLOY

1. Cek `https://domain-anda.com`
2. Jika 500: cek APP_KEY, storage permission
3. Jika 404 (selain /): cek .htaccess
4. Jika error database: cek DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD

---

**Selesai. Buatkan semua file dan instruksi untuk proyek ini.** 🚀
