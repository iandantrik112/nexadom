# Tulis README.md sebagai UTF-8 (tanpa BOM) agar preview Cursor/VS Code tidak rusak.
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$path = Join-Path $root 'README.md'
$content = @'
# Nexa / NexaDOM

Platform aplikasi PHP (Nexa Core). Proyek ini dapat di-clone manual atau di-install sebagai kerangka baru lewat Composer.

## Prasyarat

- **PHP** (sesuai versi yang didukung framework)
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

Jalankan **sekali tanpa argumen** di root proyek agar `nexa-setup.ps1` jalan (PATH, Git, fungsi `nexa` di profil PowerShell):

```powershell
cd C:\path\ke\root-proyek
.\nexa
```

### Penjelasan: titik, spasi, lalu $PROFILE

Setelah setup, fungsi **nexa** ditulis ke **profil PowerShell** (skrip yang dibaca saat terminal baru dibuka).

- **$PROFILE** — variabel berisi path file profil Anda (contoh path: Documents/PowerShell/Microsoft.PowerShell_profile.ps1).
- **Titik di depan** — itu perintah *dot-source*: jalankan file profil di sesi terminal ini, supaya fungsi nexa langsung aktif tanpa tutup buka terminal.

Contoh (satu baris perintah pertama adalah titik + spasi + $PROFILE):

```powershell
. $PROFILE
nexa make 1/Product
```

Jika Anda belum memuat profil, di terminal lama perintah **nexa** bisa belum ada sampai tab baru dibuka atau Anda menjalankan contoh di atas. Tanpa itu, tetap bisa pakai **.\nexa** dari folder root proyek.

**Alternatif:** panggil **.\nexa** dari root proyek tanpa mengandalkan fungsi di profil.

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
```

Tanpa argumen: menu interaktif.

### nexa git

Argumen diteruskan ke `git.exe` (contoh: `status`, `add`, `commit`, `push`).

## Ringkasan cepat

| Kebutuhan | Perintah |
| --- | --- |
| Proyek baru | composer create-project nexadom/framework folder |
| Setup + fungsi nexa | .\nexa (di root proyek) |
| Muat profil di sesi ini | titik spasi $PROFILE, lalu nexa ... |
| Controller | nexa make 1/Nama |
| Migrasi | nexa migrate run |

Jalankan `nexa` tanpa argumen untuk bantuan singkat di terminal.
'@
$utf8 = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($path, $content, $utf8)
Write-Host "OK: $path (UTF-8, no BOM)"
