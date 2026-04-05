# Nexa Dom Framework

Platform aplikasi PHP (Nexa Core). Proyek ini dapat di-clone manual atau di-install sebagai kerangka baru lewat Composer.

## Prasyarat

- **PHP 8** (wajib)
- **Composer** - https://getcomposer.org/download/
- **Git** (disarankan, untuk `nexa git` dan dependensi)

## Instalasi proyek baru dengan Composer

```bash
composer create-project nexadom/framework nama-folder-proyek
```

Contoh:

```bash
composer create-project nexadom/framework nexadom
cd nexadom
```

Untuk folder kosong saat ini (setelah `cd` ke direktori tujuan):

```bash
composer create-project nexadom/framework .
```

Paket `nexadom/framework` harus ada di Packagist atau repositori Composer Anda.

## CLI nexa (terminal)

Di **Windows** gunakan `nexa.bat` (dipanggil sebagai `.\nexa`) bersama PowerShell; di **Linux** dan **macOS** gunakan skrip Bash `nexa` di root proyek (tanpa `.bat`). Keduanya memanggil PHP yang sama untuk `make`, `migrate`, dan **`start`** (server pengembangan).

### Windows (PowerShell)

Jalankan **sekali tanpa argumen** di root proyek agar `nexa-setup.ps1` jalan (PATH, Git, fungsi `nexa` di profil PowerShell):

```powershell
cd C:\path\ke\root-proyek
.\nexa
```

### Linux / macOS (bash / zsh)

Skrip `nexa` ada di root repo. Beri izin eksekusi sekali, lalu panggil dengan `./`. Pastikan **PHP** dan **git** ada di `PATH`.

```bash
cd /path/ke/root-proyek
chmod +x nexa
./nexa
```

Contoh perintah: `./nexa migrate run`, `./nexa make 1/Product`, `./nexa git status`. Opsional: tambahkan folder proyek ke `PATH` di `~/.bashrc` atau `~/.zshrc` agar bisa mengetik `nexa` tanpa `./`.

### Penjelasan: titik, spasi, lalu $PROFILE (Windows)

Setelah setup, fungsi **nexa** ditulis ke **profil PowerShell** (skrip yang dibaca saat terminal baru dibuka).

- **$PROFILE** â€” variabel berisi path file profil Anda (contoh path: Documents/PowerShell/Microsoft.PowerShell_profile.ps1).
- **Titik di depan** â€” itu perintah *dot-source*: jalankan file profil di sesi terminal ini, supaya fungsi nexa langsung aktif tanpa tutup buka terminal.

Contoh (satu baris perintah pertama adalah titik + spasi + $PROFILE):

```powershell
. $PROFILE
nexa make 1/Product
```

Jika Anda belum memuat profil, di terminal lama perintah **nexa** bisa belum ada sampai tab baru dibuka atau Anda menjalankan contoh di atas. Tanpa itu, tetap bisa pakai **.\nexa** dari folder root proyek.

**Alternatif:** panggil **.\nexa** dari root proyek tanpa mengandalkan fungsi di profil.

**Linux / macOS:** tidak ada `$PROFILE` PowerShell; jalankan `./nexa` dari root atau pastikan direktori proyek ada di `PATH`.

### nexa make (generator controller)

- Admin: `nexa make 1/Product` atau `nexa make Admin/User`
- Api: prefiks `2/...`, Frontend: prefiks `3/...`
- Tanpa argumen: mode interaktif

### nexa migrate

```powershell
nexa migrate run
nexa migrate rollback
nexa migrate status
nexa migrate create CreateProductsTable
nexa migrate createdb nama_database
```

Perintah **createdb** membuat database MySQL (`CREATE DATABASE IF NOT EXISTS`) memakai kredensial `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT` dari `.env`, lalu memperbarui baris **`DB_DATABASE`** di `.env` ke nama yang Anda berikan.

Tanpa argumen: menu interaktif (termasuk opsi **5** = createdb; Anda akan diminta nama database).

### nexa git

- **Windows:** argumen diteruskan ke `git.exe` (Git for Windows).
- **Linux / macOS:** argumen diteruskan ke perintah `git` di sistem (contoh: `status`, `add`, `commit`, `push`).

### nexa start

Menjalankan **PHP built-in server** dengan **`system/bin/router.php`** (MIME statis, routing ke framework; cwd = root proyek).

- **Windows:** memanggil **`system/bin/start-server.bat`** (cek `vendor`, pilihan port, localhost vs jaringan). Contoh: `nexa start` atau `nexa start 3000`.
- **Linux / macOS:** `php -S localhost:PORT system/bin/router.php` â€” port default **8000** jika tidak ada argumen. Contoh: `./nexa start` atau `./nexa start 3000`.

Hentikan server dengan **Ctrl+C**.

## Ringkasan cepat

### Windows (PowerShell)

| Kebutuhan | Perintah |
| --- | --- |
| Proyek baru | composer create-project nexadom/framework folder |
| Setup + fungsi nexa | .\nexa (di root proyek) |
| Muat profil di sesi ini | titik spasi $PROFILE, lalu nexa ... |
| Controller | nexa make 1/Nama |
| Migrasi | nexa migrate run |
| Buat DB + set `DB_DATABASE` di `.env` | nexa migrate createdb nama_db |
| Server dev (PHP + system/bin/router.php) | nexa start atau nexa start 3000 |

### Linux / macOS (dari root proyek)

| Kebutuhan | Perintah |
| --- | --- |
| Izinkan skrip nexa | chmod +x nexa |
| Bantuan / cek CLI | ./nexa |
| Controller | ./nexa make 1/Nama |
| Migrasi | ./nexa migrate run |
| Buat DB + set `DB_DATABASE` di `.env` | ./nexa migrate createdb nama_db |
| Server dev (PHP + system/bin/router.php) | ./nexa start atau ./nexa start 3000 |
| Git | ./nexa git status (setara git status) |

**Windows:** jalankan `nexa` tanpa argumen untuk bantuan singkat. **Linux / macOS:** jalankan `./nexa` tanpa argumen. Pastikan PHP tersedia di `PATH`.