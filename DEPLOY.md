# Deploy ke InfinityFree — Panduan Lengkap

## Prasyarat

- Domain sudah di-pointing di InfinityFree: `adpwork.page.gd`
- GitHub repo: `devWebs01/POS-DW`
- Branch utama: `dev`

---

## 1. Cek & Simpan APP_KEY

Jalankan di terminal lokal:

```bash
ddev php artisan key:generate --show
```

Output: `base64:6Z25Rpd3EP6Xn77XAWXbF1Gddfv/JPA0QdjwNcJWu5I=`

**Simpan file backup** di `storage/app/private/APP_KEY.txt`. File ini sudah ada, isinya:

```
APP_KEY untuk POS-DW (Production)
Generated: 2026-07-03
Key: base64:6Z25Rpd3EP6Xn77XAWXbF1Gddfv/JPA0QdjwNcJWu5I=
```

> `storage/app/private/` adalah folder private Laravel — tidak bisa diakses publik, aman untuk menyimpan key cadangan.

---

## 2. Tambah Secrets ke GitHub

Buka browser: `https://github.com/devWebs01/POS-DW/settings/secrets/actions`

### Buka halaman Secrets
1. Buka repo `devWebs01/POS-DW`
2. Klik tab **Settings**
3. Klik **Secrets and variables** di sidebar kiri
4. Klik **Actions**

### Tambah secret satu per satu
Klik tombol **New repository secret**. Isi Name + Secret lalu klik **Add secret**.

| No | Name | Secret |
|----|------|--------|
| 1 | `FTP_PASSWORD` | `wwvhy8giASTCd` |
| 2 | `APP_KEY` | `base64:6Z25Rpd3EP6Xn77XAWXbF1Gddfv/JPA0QdjwNcJWu5I=` |
| 3 | `DB_HOST` | *(lihat panel InfinityFree → MySQL)* |
| 4 | `DB_DATABASE` | *(lihat panel InfinityFree → MySQL)* |
| 5 | `DB_USERNAME` | *(lihat panel InfinityFree → MySQL)* |
| 6 | `DB_PASSWORD` | *(lihat panel InfinityFree → MySQL)* |

---

## 3. Setup Database di InfinityFree

1. Login ke [control.infinityfree.com](https://control.infinityfree.com)
2. Klik **MySQL**
3. Klik **New Database**
4. Isi nama database (contoh: `posdw`)
5. Catat semua credentials yang muncul:
   - **Host**: `sqlXXX.infinityfree.com`
   - **Database**: `if0_42168223_posdw`
   - **Username**: `if0_42168223`
   - **Password**: *(tampil sekali saja — simpan!)*

---

## 4. Edit `.env` di Server (optional)

File `.env` akan dibuat otomatis oleh GitHub Actions dari secrets. Pastikan isinya sesuai dengan credentials dari InfinityFree MySQL. File template ada di `.github/workflows/deploy.yml`.

---

## 5. Push ke branch dev

Setelah semua secret terisi:

```bash
git add .
git commit -m "setup: persiapan deploy ke InfinityFree"
git push origin dev
```

---

## 6. Pantau Proses Deploy

1. Buka repo di GitHub
2. Klik tab **Actions**
3. Klik workflow yang sedang berjalan
4. Tunggu sampai semua step hijau (✓)

---

## 7. Verifikasi

- Buka `https://adpwork.page.gd`
- Jika error `500` — cek APP_KEY (generate ulang untuk production)
- Jika error database — cek `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` di secrets
- Jika `404` — cek Domain → `adpwork.page.gd` → document root harus `/htdocs`
