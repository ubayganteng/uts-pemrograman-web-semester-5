<?php
/**
 * Konfigurasi Database & Sistem SIM-Elektromedis RS
 * Mendukung MySQL (XAMPP) & Otomatis Fallback SQLite agar selalu berjalan tanpa kendala.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database MySQL
$databaseHost = 'localhost';
$databaseName = 'simrs';
$databaseUsername = 'root';
$databasePassword = '';
$databasePort = 3306;

$pdo = null;
$dbType = 'mysql';

// Coba koneksi ke MySQL terlebih dahulu
try {
    $dsn = "mysql:host=$databaseHost;port=$databasePort;charset=utf8mb4";
    $pdo = new PDO($dsn, $databaseUsername, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 2
    ]);

    // Pastikan database simrs ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$databaseName`");
    $dbType = 'mysql';
} catch (Exception $e) {
    // Jika MySQL tidak aktif atau error autentikasi, fallback ke SQLite
    $sqlitePath = __DIR__ . '/simrs.db';
    try {
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $dbType = 'sqlite';
    } catch (Exception $sqliteErr) {
        die("Gagal menghubungkan ke database: " . $sqliteErr->getMessage());
    }
}

// Kompatibilitas variabel mysqli lama
$mysqli = null;
if ($dbType === 'mysql') {
    $mysqli = @mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName, $databasePort);
}

// Inisialisasi Tabel dan Seed Data Otomatis jika belum ada
function initDatabase($pdo, $dbType) {
    if ($dbType === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            nama_lengkap TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'Teknisi Elektromedis',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS alat (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode_alat TEXT,
            nama_alat TEXT NOT NULL,
            jenis_alat TEXT NOT NULL,
            merek TEXT NOT NULL,
            tipe_model TEXT,
            nomor_seri TEXT,
            tahun TEXT,
            lokasi TEXT NOT NULL,
            tgl_kalibrasi_terakhir DATE,
            tgl_kalibrasi_berikutnya DATE,
            status_kalibrasi TEXT,
            lembaga_kalibrasi TEXT,
            no_sertifikat TEXT,
            kondisi TEXT DEFAULT 'Baik (Laik Operasi)',
            daya_listrik TEXT,
            tingkat_risiko TEXT DEFAULT 'Sedang (Medium Risk)',
            keterangan TEXT,
            updated_by TEXT,
            updated_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS log_aktivitas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            nama_pengguna TEXT,
            tipe_aktivitas TEXT NOT NULL,
            deskripsi TEXT NOT NULL,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        try { $pdo->exec("ALTER TABLE alat ADD COLUMN updated_by TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE alat ADD COLUMN updated_at DATETIME"); } catch (Exception $e) {}
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            nama_lengkap VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'Teknisi Elektromedis',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS alat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kode_alat VARCHAR(50),
            nama_alat VARCHAR(150) NOT NULL,
            jenis_alat VARCHAR(100) NOT NULL,
            merek VARCHAR(100) NOT NULL,
            tipe_model VARCHAR(100),
            nomor_seri VARCHAR(100),
            tahun VARCHAR(20),
            lokasi VARCHAR(150) NOT NULL,
            tgl_kalibrasi_terakhir DATE,
            tgl_kalibrasi_berikutnya DATE,
            status_kalibrasi VARCHAR(80),
            lembaga_kalibrasi VARCHAR(150),
            no_sertifikat VARCHAR(120),
            kondisi VARCHAR(80) DEFAULT 'Baik (Laik Operasi)',
            daya_listrik VARCHAR(80),
            tingkat_risiko VARCHAR(80) DEFAULT 'Sedang (Medium Risk)',
            keterangan TEXT,
            updated_by VARCHAR(100) NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS log_aktivitas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            nama_pengguna VARCHAR(100),
            tipe_aktivitas VARCHAR(50) NOT NULL,
            deskripsi TEXT NOT NULL,
            ip_address VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        try { $pdo->exec("ALTER TABLE alat ADD COLUMN updated_by VARCHAR(100) NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE alat ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP"); } catch (Exception $e) {}
    }

    // Periksa apakah tabel users kosong, jika ya buat user default
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmtUsers->fetchColumn() == 0) {
        $users = [
            [
                'username' => 'admin',
                'nama' => 'Raden Pratama, S.Tr.TEM',
                'email' => 'admin.iprs@rumahsakit.co.id',
                'pass' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => 'Admin IPSRS / Elektromedis'
            ],
            [
                'username' => 'teknisi',
                'nama' => 'Siti Khadijah, A.Md.TEM',
                'email' => 'teknisi.medis@rumahsakit.co.id',
                'pass' => password_hash('teknisi123', PASSWORD_BCRYPT),
                'role' => 'Teknisi Elektromedis'
            ],
            [
                'username' => 'perawat',
                'nama' => 'Ns. Hendra Wijaya, S.Kep',
                'email' => 'hendra.icu@rumahsakit.co.id',
                'pass' => password_hash('perawat123', PASSWORD_BCRYPT),
                'role' => 'Kepala Ruangan ICU'
            ]
        ];

        $insUser = $pdo->prepare("INSERT INTO users (username, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $insUser->execute([$u['username'], $u['nama'], $u['email'], $u['pass'], $u['role']]);
        }
    }

    // Periksa apakah tabel alat kosong, jika ya isi data elektromedis realistis
    $stmtAlat = $pdo->query("SELECT COUNT(*) FROM alat");
    if ($stmtAlat->fetchColumn() == 0) {
        $today = new DateTime();
        $datePlus10 = (clone $today)->modify('+10 days')->format('Y-m-d');
        $datePlus20 = (clone $today)->modify('+20 days')->format('Y-m-d');
        $dateMinus15 = (clone $today)->modify('-15 days')->format('Y-m-d');
        $dateMinus40 = (clone $today)->modify('-40 days')->format('Y-m-d');
        $datePlus180 = (clone $today)->modify('+180 days')->format('Y-m-d');
        $datePlus240 = (clone $today)->modify('+240 days')->format('Y-m-d');
        $datePlus300 = (clone $today)->modify('+300 days')->format('Y-m-d');

        $alatList = [
            [
                'kode_alat' => 'EM-ICU-001',
                'nama_alat' => 'Defibrillator / DC Shock Biphasic',
                'jenis_alat' => 'Life Support (Penyokong Hidup)',
                'merek' => 'Mindray',
                'tipe_model' => 'BeneHeart D3',
                'nomor_seri' => 'SN-BH2023-8841',
                'tahun' => '2023',
                'lokasi' => 'ICU (Intensive Care Unit)',
                'tgl_kalibrasi_terakhir' => '2025-11-20',
                'tgl_kalibrasi_berikutnya' => $datePlus300,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'Balai Pengamanan Fasilitas Kesehatan (BPFK) Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/XI/2025-091',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '250 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Kondisi baterai internal 100%, paddles dewasa dan anak lengkap.'
            ],
            [
                'kode_alat' => 'EM-ICU-002',
                'nama_alat' => 'Ventilator Mekanik ICU Adult & Pediatric',
                'jenis_alat' => 'Life Support (Penyokong Hidup)',
                'merek' => 'Dräger',
                'tipe_model' => 'Savina 300 Select',
                'nomor_seri' => 'SN-DR2022-4910',
                'tahun' => '2022',
                'lokasi' => 'ICU (Intensive Care Unit)',
                'tgl_kalibrasi_terakhir' => '2025-11-05',
                'tgl_kalibrasi_berikutnya' => $datePlus10,
                'status_kalibrasi' => 'Mendekati Jatuh Tempo',
                'lembaga_kalibrasi' => 'BPFK Jakarta',
                'no_sertifikat' => 'CAL/BPFK-JKT/XI/2025-782',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '320 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Segera hubungi BPFK untuk perpanjangan jadwal kalibrasi tahunan.'
            ],
            [
                'kode_alat' => 'EM-IGD-001',
                'nama_alat' => 'Patient Monitor 7 Parameter Touchscreen',
                'jenis_alat' => 'Monitoring Pasien',
                'merek' => 'Mindray',
                'tipe_model' => 'BeneVision N15',
                'nomor_seri' => 'SN-BV2024-1102',
                'tahun' => '2024',
                'lokasi' => 'IGD (Instalasi Gawat Darurat)',
                'tgl_kalibrasi_terakhir' => '2026-02-15',
                'tgl_kalibrasi_berikutnya' => $datePlus180,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'PT Kalibrasi Medika Presisi (KAN LK-089-IDN)',
                'no_sertifikat' => 'CAL/KMP/II/2026-104',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '110 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Modul SpO2, NIBP, IBP, Temp, ECG 5-lead berfungsi sangat presisi.'
            ],
            [
                'kode_alat' => 'EM-MEL-003',
                'nama_alat' => 'Infusion Pump Digital Smart',
                'jenis_alat' => 'Terapi & Pengobatan',
                'merek' => 'B. Braun',
                'tipe_model' => 'Infusomat Space',
                'nomor_seri' => 'SN-BB2021-9981',
                'tahun' => '2021',
                'lokasi' => 'Ruang Rawat Inap Melati',
                'tgl_kalibrasi_terakhir' => '2025-10-01',
                'tgl_kalibrasi_berikutnya' => $dateMinus15,
                'status_kalibrasi' => 'Kadaluarsa (Expired)',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/X/2025-455',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '45 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Masa berlaku kalibrasi telah lewat 15 hari. Wajib kalibrasi ulang sebelum digunakan ke pasien.'
            ],
            [
                'kode_alat' => 'EM-NIC-001',
                'nama_alat' => 'Syringe Pump Micro & Macro Precision',
                'jenis_alat' => 'Terapi & Pengobatan',
                'merek' => 'Terumo',
                'tipe_model' => 'TE-SS700',
                'nomor_seri' => 'SN-TR2023-3392',
                'tahun' => '2023',
                'lokasi' => 'NICU / PICU',
                'tgl_kalibrasi_terakhir' => '2026-01-10',
                'tgl_kalibrasi_berikutnya' => $datePlus240,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/I/2026-018',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '35 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Kalibrasi laju alir (flow rate) dan oklusi lulus uji standar Permenkes 54/2015.'
            ],
            [
                'kode_alat' => 'EM-POL-002',
                'nama_alat' => 'Electrocardiograph (ECG/EKG 12 Channel)',
                'jenis_alat' => 'Diagnostik & Pencitraan',
                'merek' => 'Philips Healthcare',
                'tipe_model' => 'PageWriter TC20',
                'nomor_seri' => 'SN-PH2022-7714',
                'tahun' => '2022',
                'lokasi' => 'Poliklinik Jantung & Pembuluh Darah',
                'tgl_kalibrasi_terakhir' => '2025-11-15',
                'tgl_kalibrasi_berikutnya' => $datePlus20,
                'status_kalibrasi' => 'Mendekati Jatuh Tempo',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/XI/2025-331',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '80 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Uji sensitivitas amplitudo dan kertas printer berjalan normal.'
            ],
            [
                'kode_alat' => 'EM-OK-001',
                'nama_alat' => 'Electrosurgical Unit (ESU / Couter Bedah)',
                'jenis_alat' => 'Terapi & Bedah',
                'merek' => 'Medtronic Covidien',
                'tipe_model' => 'ForceTriad Energy Platform',
                'nomor_seri' => 'SN-MD2021-6542',
                'tahun' => '2021',
                'lokasi' => 'Kamar Bedah Sentral (OK 1)',
                'tgl_kalibrasi_terakhir' => '2026-03-01',
                'tgl_kalibrasi_berikutnya' => $datePlus300,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/III/2026-088',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '850 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Output monopolar, bipolar, dan LigaSure terkalibrasi presisi.'
            ],
            [
                'kode_alat' => 'EM-OK-002',
                'nama_alat' => 'Mesin Anestesi Digital dengan Ventilator',
                'jenis_alat' => 'Life Support (Penyokong Hidup)',
                'merek' => 'GE Healthcare',
                'tipe_model' => 'Aisys CS2',
                'nomor_seri' => 'SN-GE2023-1190',
                'tahun' => '2023',
                'lokasi' => 'Kamar Bedah Sentral (OK 2)',
                'tgl_kalibrasi_terakhir' => '2026-02-10',
                'tgl_kalibrasi_berikutnya' => $datePlus240,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Jakarta',
                'no_sertifikat' => 'CAL/BPFK-JKT/II/2026-512',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '650 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Vaporizer Sevoflurane & Isoflurane lulus uji kebocoran dan akurasi konsentrasi.'
            ],
            [
                'kode_alat' => 'EM-RAD-001',
                'nama_alat' => 'Mobile Digital X-Ray System',
                'jenis_alat' => 'Diagnostik & Pencitraan',
                'merek' => 'Shimadzu',
                'tipe_model' => 'MobileDaRt Evolution MX8',
                'nomor_seri' => 'SN-SH2020-0089',
                'tahun' => '2020',
                'lokasi' => 'Instalasi Radiologi',
                'tgl_kalibrasi_terakhir' => '2025-09-12',
                'tgl_kalibrasi_berikutnya' => $dateMinus40,
                'status_kalibrasi' => 'Kadaluarsa (Expired)',
                'lembaga_kalibrasi' => 'BPFK Jakarta & BAPETEN',
                'no_sertifikat' => 'CAL/BAPETEN-BPFK/IX/2025-119',
                'kondisi' => 'Rusak Ringan (Perlu Maintenance)',
                'daya_listrik' => '3200 Watt / 220 VAC 16A',
                'tingkat_risiko' => 'Tinggi (High Risk / Radiasi)',
                'keterangan' => 'Perlu kalibrasi dosis radiasi paparan tabung X-Ray dan collimator alignment.'
            ],
            [
                'kode_alat' => 'EM-POL-003',
                'nama_alat' => 'Ultrasound Scanner 4D Color Doppler (USG)',
                'jenis_alat' => 'Diagnostik & Pencitraan',
                'merek' => 'GE Healthcare',
                'tipe_model' => 'Vivid T8 Cardiovascular',
                'nomor_seri' => 'SN-VT2024-5511',
                'tahun' => '2024',
                'lokasi' => 'Poliklinik Kebidanan & Kandungan (Obgyn)',
                'tgl_kalibrasi_terakhir' => '2026-01-20',
                'tgl_kalibrasi_berikutnya' => $datePlus240,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'PT Kalibrasi Medika Presisi',
                'no_sertifikat' => 'CAL/KMP/I/2026-441',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '400 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Probe Convex 3D/4D, Linear, dan Cardiac Phased Array dalam performa prima.'
            ],
            [
                'kode_alat' => 'EM-NIC-002',
                'nama_alat' => 'Infant Incubator / Inkubator Bayi Perawatan',
                'jenis_alat' => 'Life Support (Penyokong Hidup)',
                'merek' => 'Dräger',
                'tipe_model' => 'Isolette 8000 Plus',
                'nomor_seri' => 'SN-DR2023-7729',
                'tahun' => '2023',
                'lokasi' => 'NICU / Perinatologi',
                'tgl_kalibrasi_terakhir' => '2025-12-05',
                'tgl_kalibrasi_berikutnya' => $datePlus180,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/XII/2025-667',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '500 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Sensor suhu udara, sensor kelembaban, dan alarm batas suhu lolos uji kelayakan.'
            ],
            [
                'kode_alat' => 'EM-LAB-001',
                'nama_alat' => 'Centrifuge Klinis Kecepatan Tinggi',
                'jenis_alat' => 'Laboratorium Klinis',
                'merek' => 'Hettich',
                'tipe_model' => 'Rotofix 32A',
                'nomor_seri' => 'SN-HT2022-3844',
                'tahun' => '2022',
                'lokasi' => 'Laboratorium Patologi Klinik',
                'tgl_kalibrasi_terakhir' => '2026-03-10',
                'tgl_kalibrasi_berikutnya' => $datePlus300,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'Lab Kalibrasi Medis KAN LK-112',
                'no_sertifikat' => 'CAL/LK-112/III/2026-902',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '300 Watt / 220 VAC',
                'tingkat_risiko' => 'Rendah (Low Risk)',
                'keterangan' => 'Akurasi tachometer RPM dan timer digital sesuai toleransi ISO.'
            ],
            [
                'kode_alat' => 'EM-HD-001',
                'nama_alat' => 'Mesin Cuci Darah (Hemodialysis Machine)',
                'jenis_alat' => 'Life Support (Penyokong Hidup)',
                'merek' => 'Fresenius Medical Care',
                'tipe_model' => '4008S Classix',
                'nomor_seri' => 'SN-FM2022-9011',
                'tahun' => '2022',
                'lokasi' => 'Unit Hemodialisa (Cuci Darah)',
                'tgl_kalibrasi_terakhir' => '2026-02-01',
                'tgl_kalibrasi_berikutnya' => $datePlus180,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Jakarta',
                'no_sertifikat' => 'CAL/BPFK-JKT/II/2026-309',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '1500 Watt / 220 VAC',
                'tingkat_risiko' => 'Tinggi (High Risk / Life Support)',
                'keterangan' => 'Conductivity cell, blood pump speed, dan alarm transmembrane pressure terverifikasi.'
            ],
            [
                'kode_alat' => 'EM-CSSD-001',
                'nama_alat' => 'Autoclave Sterilisasi Uap Tekanan Tinggi',
                'jenis_alat' => 'Sterilisasi & CSSD',
                'merek' => 'Tuttnauer',
                'tipe_model' => '3870E Electronic Tabletop',
                'nomor_seri' => 'SN-TT2021-4431',
                'tahun' => '2021',
                'lokasi' => 'Instalasi CSSD (Pusat Sterilisasi)',
                'tgl_kalibrasi_terakhir' => '2026-01-15',
                'tgl_kalibrasi_berikutnya' => $datePlus240,
                'status_kalibrasi' => 'Terkalibrasi (Laik Pakai)',
                'lembaga_kalibrasi' => 'BPFK Surabaya',
                'no_sertifikat' => 'CAL/BPFK-SBY/I/2026-781',
                'kondisi' => 'Baik (Laik Operasi)',
                'daya_listrik' => '2400 Watt / 220 VAC',
                'tingkat_risiko' => 'Sedang (Medium Risk)',
                'keterangan' => 'Pressure gauge, sensor suhu 134°C, dan safety relief valve berfungsi aman.'
            ]
        ];

        $insAlat = $pdo->prepare("INSERT INTO alat (
            kode_alat, nama_alat, jenis_alat, merek, tipe_model, nomor_seri, tahun,
            lokasi, tgl_kalibrasi_terakhir, tgl_kalibrasi_berikutnya, status_kalibrasi,
            lembaga_kalibrasi, no_sertifikat, kondisi, daya_listrik, tingkat_risiko, keterangan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($alatList as $a) {
            $insAlat->execute([
                $a['kode_alat'], $a['nama_alat'], $a['jenis_alat'], $a['merek'], $a['tipe_model'],
                $a['nomor_seri'], $a['tahun'], $a['lokasi'], $a['tgl_kalibrasi_terakhir'],
                $a['tgl_kalibrasi_berikutnya'], $a['status_kalibrasi'], $a['lembaga_kalibrasi'],
                $a['no_sertifikat'], $a['kondisi'], $a['daya_listrik'], $a['tingkat_risiko'], $a['keterangan']
            ]);
        }
    }

    // Periksa apakah tabel log_aktivitas kosong, jika ya buat log awal realistis
    $stmtLogs = $pdo->query("SELECT COUNT(*) FROM log_aktivitas");
    if ($stmtLogs->fetchColumn() == 0) {
        $initialLogs = [
            ['admin', 'Raden Pratama, S.Tr.TEM', 'LOGIN', 'Pengguna admin berhasil masuk ke dashboard sistem.', '127.0.0.1'],
            ['admin', 'Raden Pratama, S.Tr.TEM', 'UPDATE_ALAT', 'Memperbarui data dan jadwal kalibrasi alat Defibrillator Biphasic (EM-ICU-001).', '127.0.0.1'],
            ['teknisi', 'Siti Khadijah, A.Md.TEM', 'TAMBAH_ALAT', 'Menambahkan instrumen baru Patient Monitor 7-Para (EM-IGD-001) di IGD.', '127.0.0.1'],
            ['perawat', 'Ns. Hendra Wijaya, S.Kep', 'REGISTRASI', 'Pengguna baru berhasil registrasi akun dengan peran Kepala Ruangan ICU.', '127.0.0.1']
        ];
        $insLog = $pdo->prepare("INSERT INTO log_aktivitas (username, nama_pengguna, tipe_aktivitas, deskripsi, ip_address) VALUES (?, ?, ?, ?, ?)");
        foreach ($initialLogs as $l) {
            $insLog->execute($l);
        }
    }
}

// Jalankan inisialisasi database
initDatabase($pdo, $dbType);

// Helper Functions
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'Teknisi Elektromedis'
    ];
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function calculate_calibration_badge($tgl_berikutnya) {
    if (empty($tgl_berikutnya)) {
        return [
            'status' => 'Belum Terjadwal',
            'badge' => 'bg-gray-100 text-gray-700 border-gray-300',
            'days' => 0,
            'text' => 'Belum ada jadwal'
        ];
    }

    $today = new DateTime();
    $target = new DateTime($tgl_berikutnya);
    $diff = $today->diff($target);
    $isPast = $today > $target;
    $days = $diff->days;

    if ($isPast) {
        return [
            'status' => 'Kadaluarsa (Expired)',
            'badge' => 'bg-red-50 text-red-700 border-red-200 ring-1 ring-red-400/30',
            'days' => -$days,
            'text' => 'Terlambat ' . $days . ' hari'
        ];
    } elseif ($days <= 30) {
        return [
            'status' => 'Mendekati Jatuh Tempo',
            'badge' => 'bg-amber-50 text-amber-800 border-amber-300 ring-1 ring-amber-400/40',
            'days' => $days,
            'text' => 'Sisa ' . $days . ' hari lagi'
        ];
    } else {
        return [
            'status' => 'Terkalibrasi (Laik Pakai)',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-400/30',
            'days' => $days,
            'text' => 'Aktif (' . $days . ' hari lagi)'
        ];
    }
}

/**
 * Catat aktivitas pengguna (Audit Trail / Activity Log)
 */
function catat_log($pdo, $tipe_aktivitas, $deskripsi, $username = null, $nama_pengguna = null) {
    if (!$username && isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }
    if (!$nama_pengguna && isset($_SESSION['nama_lengkap'])) {
        $nama_pengguna = $_SESSION['nama_lengkap'];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip === '::1') $ip = '127.0.0.1';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (username, nama_pengguna, tipe_aktivitas, deskripsi, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $username ?: 'Sistem',
            $nama_pengguna ?: 'Sistem',
            $tipe_aktivitas,
            $deskripsi,
            $ip
        ]);
    } catch (Exception $e) {
        // Log gagal dicatat tidak boleh menghentikan proses utama
    }
}
