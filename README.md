# MEDITRONIK - Sistem Informasi Manajemen Elektromedis & Kalibrasi Rumah Sakit (SIM-RS)

Website manajemen alat elektromedis profesional, estetik, dan elegan dengan tema dominan biru medis (*Hospital Deep Blue & Sapphire*), lengkap dengan otentikasi login/registrasi, pelacakan jadwal kalibrasi (Permenkes RI No. 54 / 2015), kartu riwayat alat, cetak label stiker laik pakai, serta ekspor laporan.

---

## 🌟 Fitur Utama Sistem

1. **Dashboard & Analitik Visual Estetik**:
   - Tema warna biru proporsional (*Deep Medical Blue, Cyan, Slate*).
   - Metrik statistik real-time: Total Alat Terdata, Laik Pakai, Segera Kalibrasi (≤ 30 Hari), Kadaluarsa, dan Alat Dalam Maintenance/Rusak.
   - Grafik interaktif (*Chart.js*): Distribusi Kategori Alat & Persentase Status Kelaikan Kalibrasi.
   - Peringatan dini (*Urgent Calibration Alert Banner*) untuk alat yang mendekati jatuh tempo atau telah lewat masa uji.

2. **Sistem Login & Registrasi Akun Proporsional**:
   - Form Login & Registrasi elegan dengan transisi tab halus.
   - Keamanan enkripsi password menggunakan `password_hash()` (Bcrypt).
   - Pengelompokan peran (*Role-Based Access*):
     - **Admin IPSRS / Elektromedis** (Hak akses penuh kelola inventaris)
     - **Teknisi Elektromedis** (Pencatatan pemeliharaan & uji fungsi)
     - **Kepala Ruangan / Unit RS** (Monitoring kelaikan alat di ruangannya)
   - Tombol *Demo Fast Login* untuk pengujian langsung tanpa mengetik.

3. **Data Alat Elektromedis Lengkap & Terstandar Kemenkes RI**:
   - **Nama Alat**: Defibrillator Biphasic, Ventilator Mekanik, Patient Monitor, ESU Couter, Mesin Anestesi, Infusion Pump, Syringe Pump, USG 4D Doppler, Mobile X-Ray, Hemodialysis, Incubator Bayi, Autoclave, Centrifuge, dll.
   - **Jenis / Kategori**: *Life Support* (Penyokong Hidup), *Monitoring Pasien*, *Diagnostik & Pencitraan*, *Terapi & Bedah*, *Laboratorium Klinis*, *Sterilisasi & CSSD*.
   - **Tahun Pengadaan** & **Merek Pabrikan** (Mindray, GE Healthcare, Dräger, Philips, B. Braun, dll).
   - **Nomor Seri (Serial Number / SN)**: Sangat krusial untuk kalibrasi alat elektromedis.
   - **Kode ASPAK / Inventaris**: Format standar rumah sakit (contoh: `EM-ICU-001`).
   - **Lokasi Ruangan RS**: ICU, IGD, OK Bedah 1 & 2, NICU/PICU, Rawat Inap Melati, Poli Jantung, Radiologi, Hemodialisa, Lab, CSSD.
   - **Tingkat Risiko (ECRI Standard)**: Tinggi (*Life Support*), Sedang (*Medium Risk*), Rendah (*Low Risk*).
   - **Konsumsi Kelistrikan / Daya (Watt)**: Proteksi keselamatan listrik sesuai *IEC 62353 / IEC 60601-1*.
   - **Kondisi Fisik**: Baik (Laik Operasi), Rusak Ringan (Maintenance), Rusak Berat (Afkir).

4. **Modul Jadwal Kalibrasi & Sertifikasi Pengujian**:
   - Pencatatan Tanggal Kalibrasi Terakhir & Perhitungan Otomatis Tanggal Jatuh Tempo (+1 Tahun sesuai Permenkes 54/2015).
   - Badge countdown sisa hari kalibrasi (*Sisa 14 Hari*, *Terlambat 15 Hari*, *Aktif 240 Hari*).
   - Lembaga Penguji: Balai Pengamanan Fasilitas Kesehatan (BPFK Jakarta/Surabaya) & Laboratorium Kalibrasi Terakreditasi KAN.
   - Nomor Sertifikat Kalibrasi resmi.

5. **Fitur Ekspor & Cetak Berstandar Rumah Sakit**:
   - **Cetak Stiker Kalibrasi (Label Fisik)**: Stiker resmi laik pakai warna hijau (atau merah jika tidak laik) lengkap dengan QR Code, tanggal kalibrasi, batas waktu, dan paraf petugas penguji untuk ditempel di fisik alat.
   - **Kartu Riwayat Alat Elektromedis (Detail View)**: Rekam jejak inspeksi teknis lengkap.
   - **Cetak Rekapitulasi Laporan (PDF Ready)**: Dilengkapi Kop Surat Resmi Rumah Sakit dan lembar tanda tangan pejabat IPSRS.
   - **Ekspor Data ke Excel / CSV**: Unduh seluruh inventaris dalam 1 klik dengan encoding UTF-8 BOM rapi di Microsoft Excel.

6. **Fitur Pelacakan Log Aktivitas & Pengguna (Audit Trail)**:
   - **Mengetahui Siapa yang Registrasi**: Buka menu **Data Pengguna** (`users.php`) untuk melihat daftar seluruh akun terdaftar beserta tanggal registrasinya.
   - **Mengetahui Siapa yang Masuk (Login) & Mengedit Data**: Buka menu **Log Aktivitas** (`log_aktivitas.php`) atau lihat widget **Aktivitas Terkini** di Dashboard untuk melihat rekam jejak siapa yang login, jam berapa, alat apa yang diedit atau ditambah, dan IP address-nya.
   - **Audit Trail Pembaruan Alat**: Pada halaman detail alat (`detail.php`), tercatat tanggal/jam pembaruan terakhir dan nama teknisi yang mengubahnya (`updated_by`).

---

## 🚀 Cara Menjalankan Aplikasi

Aplikasi ini sudah dipasang di dua direktori yang siap digunakan:
1. **Di Folder XAMPP Anda**: `C:\xampp\htdocs\crud\`
2. **Di Workspace Project**: `C:\Users\CBT-011\.gemini\antigravity\scratch\elektromedis-app\`

### Opsi 1: Melalui Browser Langsung (Standalone App - Tanpa Server)
Buka file berikut di Google Chrome atau Microsoft Edge:
`C:\xampp\htdocs\crud\app.html`  
*(Cukup klik dua kali file `app.html` di File Explorer, aplikasi langsung berjalan lengkap dengan demo data, grafik, pencarian, dan pencetakan stiker)*.

### Opsi 2: Melalui Server PHP (Sedang Berjalan di Port 8080)
Web server PHP saat ini sedang aktif:
- Buka browser dan kunjungi: **`http://localhost:8080/login.php`** atau **`http://localhost:8080/index.php`**

### Opsi 3: Melalui Apache XAMPP Biasa
1. Buka **XAMPP Control Panel**, pastikan **Apache** menyala (*Start*).
2. Buka browser dan buka: **`http://localhost/crud/login.php`**

---

## 🔑 Akun Demo Siap Pakai

| Peran (Role) | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin IPSRS** | `admin` | `admin123` | Akses penuh inventaris, tambah, edit, hapus, cetak |
| **Teknisi Medis** | `teknisi` | `teknisi123` | Akses teknisi elektromedis |
| **Kepala Ruangan** | `perawat` | `perawat123` | Monitoring kelaikan alat di ruangan |

*(Tersedia tombol 1-klik di halaman login untuk mengisi akun demo secara instan).*

---

## 🗄️ Dukungan Basis Data (Database)

- **Otomatis Fallback SQLite & MySQL**: File `config.php` telah dirancang secara cerdas. Jika MySQL aktif dan terkoneksi, sistem menggunakan database MySQL `simrs`. Jika MySQL belum terkonfigurasi atau port sedang dipakai, sistem otomatis beralih ke database SQLite bawaan `simrs.db` sehingga aplikasi **selalu berjalan 100% mulus tanpa error koneksi**.
- **File SQL MySQL**: File `simrs.sql` tersedia jika Anda ingin mengimpor database secara manual ke phpMyAdmin.

---

## 📁 Struktur Berkas

- `config.php` : Konfigurasi database cerdas (MySQL & SQLite), session, helper kalkulasi status kalibrasi.
- `index.php` : Dashboard utama, visualisasi Chart.js, filter live, tabel data alat elektromedis.
- `login.php` : Halaman login & registrasi bertema biru estetik dengan fast-demo button.
- `register.php` : Handler registrasi akun pengguna baru.
- `logout.php` : Proses logout dan penghapusan session secara aman.
- `add.php` : Formulir penambahan alat elektromedis baru (lengkap dengan auto +1 tahun kalibrasi).
- `edit.php` : Formulir perbaruan spesifikasi dan tanggal kalibrasi alat.
- `detail.php` : Kartu riwayat inspeksi fisik & teknis alat elektromedis rumah sakit.
- `delete.php` : Handler penghapusan data alat dengan aman.
- `cetak_label.php` : Generator stiker label kalibrasi laik pakai (standar Kemenkes RI/BPFK).
- `cetak_laporan.php` : Format cetak rekapitulasi laporan kalibrasi dengan Kop Surat resmi RS.
- `export.php` : Fitur unduh inventaris ke format CSV / Microsoft Excel.
- `app.html` : Versi web interaktif standalone (langsung jalan di browser).
- `simrs.sql` : Skema tabel dan data awal untuk MySQL.
