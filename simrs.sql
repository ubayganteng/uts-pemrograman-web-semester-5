-- ========================================================
-- SISTEM INFORMASI ELEKTROMEDIS & KALIBRASI RUMAH SAKIT
-- Database: simrs
-- Standar Permenkes RI No. 54 Tahun 2015
-- ========================================================

CREATE DATABASE IF NOT EXISTS `simrs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `simrs`;

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Teknisi Elektromedis',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Default Users (Password: admin123, teknisi123, perawat123)
INSERT INTO `users` (`id`, `username`, `nama_lengkap`, `email`, `password`, `role`) VALUES
(1, 'admin', 'Raden Pratama, S.Tr.TEM', 'admin.iprs@rumahsakit.co.id', '$2y$10$wE8f82T4aAiqx7rRkGjE/OCG0B9JbZzJvHnB3h5EwZ2vKzB4tJjSm', 'Admin IPSRS / Elektromedis'),
(2, 'teknisi', 'Siti Khadijah, A.Md.TEM', 'teknisi.medis@rumahsakit.co.id', '$2y$10$tZz09zV7MkqY1C1Z0P2E.euV4S4F01mGfR8YtKz2pTq6H8Xw3b1yC', 'Teknisi Elektromedis'),
(3, 'perawat', 'Ns. Hendra Wijaya, S.Kep', 'hendra.icu@rumahsakit.co.id', '$2y$10$J5hK4yR3M0N9v8T2Q1P0.eW7E5R6T7Y8U9I0O1P2A3S4D5F6G7H8J', 'Kepala Ruangan ICU');

-- --------------------------------------------------------
-- Struktur Tabel `alat`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `alat`;
CREATE TABLE `alat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_alat` VARCHAR(50) DEFAULT NULL,
  `nama_alat` VARCHAR(150) NOT NULL,
  `jenis_alat` VARCHAR(100) NOT NULL,
  `merek` VARCHAR(100) NOT NULL,
  `tipe_model` VARCHAR(100) DEFAULT NULL,
  `nomor_seri` VARCHAR(100) DEFAULT NULL,
  `tahun` VARCHAR(20) DEFAULT NULL,
  `lokasi` VARCHAR(150) NOT NULL,
  `tgl_kalibrasi_terakhir` DATE DEFAULT NULL,
  `tgl_kalibrasi_berikutnya` DATE DEFAULT NULL,
  `status_kalibrasi` VARCHAR(80) DEFAULT NULL,
  `lembaga_kalibrasi` VARCHAR(150) DEFAULT NULL,
  `no_sertifikat` VARCHAR(120) DEFAULT NULL,
  `kondisi` VARCHAR(80) DEFAULT 'Baik (Laik Operasi)',
  `daya_listrik` VARCHAR(80) DEFAULT NULL,
  `tingkat_risiko` VARCHAR(80) DEFAULT 'Sedang (Medium Risk)',
  `keterangan` TEXT DEFAULT NULL,
  `updated_by` VARCHAR(100) DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Awal Alat Elektromedis
INSERT INTO `alat` (`id`, `kode_alat`, `nama_alat`, `jenis_alat`, `merek`, `tipe_model`, `nomor_seri`, `tahun`, `lokasi`, `tgl_kalibrasi_terakhir`, `tgl_kalibrasi_berikutnya`, `status_kalibrasi`, `lembaga_kalibrasi`, `no_sertifikat`, `kondisi`, `daya_listrik`, `tingkat_risiko`, `keterangan`) VALUES
(1, 'EM-ICU-001', 'Defibrillator / DC Shock Biphasic', 'Life Support (Penyokong Hidup)', 'Mindray', 'BeneHeart D3', 'SN-BH2023-8841', '2023', 'ICU (Intensive Care Unit)', '2025-11-20', '2026-11-20', 'Terkalibrasi (Laik Pakai)', 'Balai Pengamanan Fasilitas Kesehatan (BPFK) Surabaya', 'CAL/BPFK-SBY/XI/2025-091', 'Baik (Laik Operasi)', '250 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Kondisi baterai internal 100%, paddles dewasa dan anak lengkap.'),
(2, 'EM-ICU-002', 'Ventilator Mekanik ICU Adult & Pediatric', 'Life Support (Penyokong Hidup)', 'Dräger', 'Savina 300 Select', 'SN-DR2022-4910', '2022', 'ICU (Intensive Care Unit)', '2025-11-05', '2026-11-05', 'Mendekati Jatuh Tempo', 'BPFK Jakarta', 'CAL/BPFK-JKT/XI/2025-782', 'Baik (Laik Operasi)', '320 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Segera hubungi BPFK untuk perpanjangan jadwal kalibrasi tahunan.'),
(3, 'EM-IGD-001', 'Patient Monitor 7 Parameter Touchscreen', 'Monitoring Pasien', 'Mindray', 'BeneVision N15', 'SN-BV2024-1102', '2024', 'IGD (Instalasi Gawat Darurat)', '2026-02-15', '2027-02-15', 'Terkalibrasi (Laik Pakai)', 'PT Kalibrasi Medika Presisi (KAN LK-089-IDN)', 'CAL/KMP/II/2026-104', 'Baik (Laik Operasi)', '110 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Modul SpO2, NIBP, IBP, Temp, ECG 5-lead berfungsi sangat presisi.'),
(4, 'EM-MEL-003', 'Infusion Pump Digital Smart', 'Terapi & Pengobatan', 'B. Braun', 'Infusomat Space', 'SN-BB2021-9981', '2021', 'Ruang Rawat Inap Melati', '2025-10-01', '2026-10-01', 'Kadaluarsa (Expired)', 'BPFK Surabaya', 'CAL/BPFK-SBY/X/2025-455', 'Baik (Laik Operasi)', '45 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Masa berlaku kalibrasi telah lewat. Wajib kalibrasi ulang sebelum digunakan ke pasien.'),
(5, 'EM-NIC-001', 'Syringe Pump Micro & Macro Precision', 'Terapi & Pengobatan', 'Terumo', 'TE-SS700', 'SN-TR2023-3392', '2023', 'NICU / PICU', '2026-01-10', '2027-01-10', 'Terkalibrasi (Laik Pakai)', 'BPFK Surabaya', 'CAL/BPFK-SBY/I/2026-018', 'Baik (Laik Operasi)', '35 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Kalibrasi laju alir (flow rate) dan oklusi lulus uji standar Permenkes 54/2015.'),
(6, 'EM-POL-002', 'Electrocardiograph (ECG/EKG 12 Channel)', 'Diagnostik & Pencitraan', 'Philips Healthcare', 'PageWriter TC20', 'SN-PH2022-7714', '2022', 'Poliklinik Jantung & Pembuluh Darah', '2025-11-15', '2026-11-15', 'Mendekati Jatuh Tempo', 'BPFK Surabaya', 'CAL/BPFK-SBY/XI/2025-331', 'Baik (Laik Operasi)', '80 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Uji sensitivitas amplitudo dan kertas printer berjalan normal.'),
(7, 'EM-OK-001', 'Electrosurgical Unit (ESU / Couter Bedah)', 'Terapi & Bedah', 'Medtronic Covidien', 'ForceTriad Energy Platform', 'SN-MD2021-6542', '2021', 'Kamar Bedah Sentral (OK 1)', '2026-03-01', '2027-03-01', 'Terkalibrasi (Laik Pakai)', 'BPFK Surabaya', 'CAL/BPFK-SBY/III/2026-088', 'Baik (Laik Operasi)', '850 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Output monopolar, bipolar, dan LigaSure terkalibrasi presisi.'),
(8, 'EM-OK-002', 'Mesin Anestesi Digital dengan Ventilator', 'Life Support (Penyokong Hidup)', 'GE Healthcare', 'Aisys CS2', 'SN-GE2023-1190', '2023', 'Kamar Bedah Sentral (OK 2)', '2026-02-10', '2027-02-10', 'Terkalibrasi (Laik Pakai)', 'BPFK Jakarta', 'CAL/BPFK-JKT/II/2026-512', 'Baik (Laik Operasi)', '650 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Vaporizer Sevoflurane & Isoflurane lulus uji kebocoran dan akurasi konsentrasi.'),
(9, 'EM-RAD-001', 'Mobile Digital X-Ray System', 'Diagnostik & Pencitraan', 'Shimadzu', 'MobileDaRt Evolution MX8', 'SN-SH2020-0089', '2020', 'Instalasi Radiologi', '2025-09-12', '2026-09-12', 'Kadaluarsa (Expired)', 'BPFK Jakarta & BAPETEN', 'CAL/BAPETEN-BPFK/IX/2025-119', 'Rusak Ringan (Perlu Maintenance)', '3200 Watt / 220 VAC 16A', 'Tinggi (High Risk / Radiasi)', 'Perlu kalibrasi dosis radiasi paparan tabung X-Ray dan collimator alignment.'),
(10, 'EM-POL-003', 'Ultrasound Scanner 4D Color Doppler (USG)', 'Diagnostik & Pencitraan', 'GE Healthcare', 'Vivid T8 Cardiovascular', 'SN-VT2024-5511', '2024', 'Poliklinik Kebidanan & Kandungan (Obgyn)', '2026-01-20', '2027-01-20', 'Terkalibrasi (Laik Pakai)', 'PT Kalibrasi Medika Presisi', 'CAL/KMP/I/2026-441', 'Baik (Laik Operasi)', '400 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Probe Convex 3D/4D, Linear, dan Cardiac Phased Array dalam performa prima.'),
(11, 'EM-NIC-002', 'Infant Incubator / Inkubator Bayi Perawatan', 'Life Support (Penyokong Hidup)', 'Dräger', 'Isolette 8000 Plus', 'SN-DR2023-7729', '2023', 'NICU / Perinatologi', '2025-12-05', '2026-12-05', 'Terkalibrasi (Laik Pakai)', 'BPFK Surabaya', 'CAL/BPFK-SBY/XII/2025-667', 'Baik (Laik Operasi)', '500 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Sensor suhu udara, sensor kelembaban, dan alarm batas suhu lolos uji kelayakan.'),
(12, 'EM-LAB-001', 'Centrifuge Klinis Kecepatan Tinggi', 'Laboratorium Klinis', 'Hettich', 'Rotofix 32A', 'SN-HT2022-3844', '2022', 'Laboratorium Patologi Klinik', '2026-03-10', '2027-03-10', 'Terkalibrasi (Laik Pakai)', 'Lab Kalibrasi Medis KAN LK-112', 'CAL/LK-112/III/2026-902', 'Baik (Laik Operasi)', '300 Watt / 220 VAC', 'Rendah (Low Risk)', 'Akurasi tachometer RPM dan timer digital sesuai toleransi ISO.'),
(13, 'EM-HD-001', 'Mesin Cuci Darah (Hemodialysis Machine)', 'Life Support (Penyokong Hidup)', 'Fresenius Medical Care', '4008S Classix', 'SN-FM2022-9011', '2022', 'Unit Hemodialisa (Cuci Darah)', '2026-02-01', '2027-02-01', 'Terkalibrasi (Laik Pakai)', 'BPFK Jakarta', 'CAL/BPFK-JKT/II/2026-309', 'Baik (Laik Operasi)', '1500 Watt / 220 VAC', 'Tinggi (High Risk / Life Support)', 'Conductivity cell, blood pump speed, dan alarm transmembrane pressure terverifikasi.'),
(14, 'EM-CSSD-001', 'Autoclave Sterilisasi Uap Tekanan Tinggi', 'Sterilisasi & CSSD', 'Tuttnauer', '3870E Electronic Tabletop', 'SN-TT2021-4431', '2021', 'Instalasi CSSD (Pusat Sterilisasi)', '2026-01-15', '2027-01-15', 'Terkalibrasi (Laik Pakai)', 'BPFK Surabaya', 'CAL/BPFK-SBY/I/2026-781', 'Baik (Laik Operasi)', '2400 Watt / 220 VAC', 'Sedang (Medium Risk)', 'Pressure gauge, sensor suhu 134°C, dan safety relief valve berfungsi aman.');

-- --------------------------------------------------------
-- Struktur Tabel `log_aktivitas` (Audit Trail)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) DEFAULT NULL,
  `nama_pengguna` VARCHAR(100) DEFAULT NULL,
  `tipe_aktivitas` VARCHAR(50) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `ip_address` VARCHAR(50) DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Log Awal
INSERT INTO `log_aktivitas` (`username`, `nama_pengguna`, `tipe_aktivitas`, `deskripsi`, `ip_address`) VALUES
('admin', 'Raden Pratama, S.Tr.TEM', 'LOGIN', 'Pengguna admin berhasil masuk ke dashboard sistem.', '127.0.0.1'),
('admin', 'Raden Pratama, S.Tr.TEM', 'UPDATE_ALAT', 'Memperbarui data dan jadwal kalibrasi alat Defibrillator Biphasic (EM-ICU-001).', '127.0.0.1'),
('teknisi', 'Siti Khadijah, A.Md.TEM', 'TAMBAH_ALAT', 'Menambahkan instrumen baru Patient Monitor 7-Para (EM-IGD-001) di IGD.', '127.0.0.1'),
('perawat', 'Ns. Hendra Wijaya, S.Kep', 'REGISTRASI', 'Pengguna baru berhasil registrasi akun dengan peran Kepala Ruangan ICU.', '127.0.0.1');
