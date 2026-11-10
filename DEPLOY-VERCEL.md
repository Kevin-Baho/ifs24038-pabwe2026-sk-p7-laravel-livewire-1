# Deploy ifs24038 ke Vercel (database dari HeidiSQL)

Database `pabwe_p7_stock` kamu ada di **localhost** (MariaDB 12.3.3). Vercel berjalan di cloud,
jadi **tidak bisa** membaca localhost. Database harus dipindah ke MySQL/MariaDB online
(gratis: Aiven, TiDB Cloud, Railway, FreeDB, dll).

## 1. Export database dari HeidiSQL
1. Klik kanan `pabwe_p7_stock` -> **Export database as SQL**
2. Centang semua tabel (10 tabel): Struktur + Data, output: *Single .sql file*
3. Simpan, mis. `pabwe_p7_stock.sql`

## 2. Buat database online & import
1. Buat MySQL/MariaDB gratis di salah satu provider di atas.
2. Catat: Host, Port, Database, Username, Password (+ `ca.pem` jika SSL wajib).
3. Di HeidiSQL: **New session** -> isi data di atas (tab *SSL* jika perlu) -> connect.
4. Pilih database online -> menu **File -> Run SQL file** -> pilih `pabwe_p7_stock.sql`.

## 3. Push ke GitHub
```
git init
git add .
git commit -m "ifs24038"
git branch -M main
git remote add origin https://github.com/<username>/ifs24038-pabwe2026-sk-p7-laravel-livewire.git
git push -u origin main
```

## 4. Import ke Vercel
Vercel -> Add New Project -> pilih repo -> **Framework Preset: Other** -> sebelum Deploy,
isi **Environment Variables**:

| Key | Value |
|---|---|
| DB_HOST | host database online |
| DB_PORT | port (mis. 3306) |
| DB_DATABASE | nama database |
| DB_USERNAME | username |
| DB_PASSWORD | password |
| APP_KEY | hasil `php artisan key:generate --show` (disarankan ganti) |
| MYSQL_ATTR_SSL_CA | (opsional) path ca.pem, atau taruh file di `certs/ca.pem` |
| DB_SSL_VERIFY | `false` jika error verifikasi sertifikat |

Klik **Deploy**.

## Jalan lokal (HeidiSQL)
```
copy .env.example .env
composer install
php artisan key:generate
php artisan serve
```
`.env.example` sudah memakai `DB_DATABASE=pabwe_p7_stock`, user `root`, password kosong.

## Catatan
- Upload cover gambar disimpan di `/tmp` Vercel (hilang saat instance restart).
- Login seeder: admin / admin, member / member (jika tabel `users` kamu masih berisi data itu).
