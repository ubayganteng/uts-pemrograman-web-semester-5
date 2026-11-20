<?php
require_once __DIR__ . '/config.php';
require_login();

$user = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_alat = trim($_POST['kode_alat'] ?? '');
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $jenis_alat = trim($_POST['jenis_alat'] ?? 'Life Support (Penyokong Hidup)');
    $merek = trim($_POST['merek'] ?? '');
    $tipe_model = trim($_POST['tipe_model'] ?? '');
    $nomor_seri = trim($_POST['nomor_seri'] ?? '');
    $tahun = trim($_POST['tahun'] ?? date('Y'));
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tgl_kalibrasi_terakhir = trim($_POST['tgl_kalibrasi_terakhir'] ?? '');
    $tgl_kalibrasi_berikutnya = trim($_POST['tgl_kalibrasi_berikutnya'] ?? '');
    $lembaga_kalibrasi = trim($_POST['lembaga_kalibrasi'] ?? 'Balai Pengamanan Fasilitas Kesehatan (BPFK)');
    $no_sertifikat = trim($_POST['no_sertifikat'] ?? '');
    $kondisi = trim($_POST['kondisi'] ?? 'Baik (Laik Operasi)');
    $daya_listrik = trim($_POST['daya_listrik'] ?? '');
    $tingkat_risiko = trim($_POST['tingkat_risiko'] ?? 'Sedang (Medium Risk)');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Jika tgl kalibrasi berikutnya kosong namun tgl terakhir terisi, otomatis +1 tahun
    if (!empty($tgl_kalibrasi_terakhir) && empty($tgl_kalibrasi_berikutnya)) {
        $tgl_kalibrasi_berikutnya = date('Y-m-d', strtotime('+1 year', strtotime($tgl_kalibrasi_terakhir)));
    }

    // Tentukan status kalibrasi otomatis
    $status_kalibrasi = 'Terkalibrasi (Laik Pakai)';
    if (!empty($tgl_kalibrasi_berikutnya)) {
        $today = new DateTime();
        $target = new DateTime($tgl_kalibrasi_berikutnya);
        if ($today > $target) {
            $status_kalibrasi = 'Kadaluarsa (Expired)';
        } elseif ($today->diff($target)->days <= 30) {
            $status_kalibrasi = 'Mendekati Jatuh Tempo';
        }
    }

    if (empty($nama_alat) || empty($merek) || empty($lokasi)) {
        $error = 'Nama alat, merek, dan lokasi ruangan wajib diisi.';
    } else {
        // Jika kode alat kosong, otomatis buatkan
        if (empty($kode_alat)) {
            $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $lokasi), 0, 3));
            $kode_alat = 'EM-' . ($prefix ? $prefix : 'RS') . '-' . rand(100, 999);
        }

        $stmt = $pdo->prepare("INSERT INTO alat (
            kode_alat, nama_alat, jenis_alat, merek, tipe_model, nomor_seri, tahun,
            lokasi, tgl_kalibrasi_terakhir, tgl_kalibrasi_berikutnya, status_kalibrasi,
            lembaga_kalibrasi, no_sertifikat, kondisi, daya_listrik, tingkat_risiko, keterangan,
            updated_by, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            $kode_alat, $nama_alat, $jenis_alat, $merek, $tipe_model, $nomor_seri, $tahun,
            $lokasi, $tgl_kalibrasi_terakhir ?: null, $tgl_kalibrasi_berikutnya ?: null, $status_kalibrasi,
            $lembaga_kalibrasi, $no_sertifikat, $kondisi, $daya_listrik, $tingkat_risiko, $keterangan,
            $user['nama_lengkap'], $now
        ]);

        // Catat log aktivitas penambahan data
        catat_log($pdo, 'TAMBAH_ALAT', "Menambahkan alat baru: '$nama_alat' ($kode_alat), Merek: $merek, Ruangan: $lokasi.");

        $_SESSION['flash_success'] = "Alat elektromedis '$nama_alat' ($kode_alat) berhasil ditambahkan ke inventaris!";
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Alat Elektromedis - MEDITRONIK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        medical: {
                            50: '#f0f7ff', 100: '#e0effe', 200: '#b9ddfd', 300: '#7cc2fb',
                            400: '#36a2f7', 500: '#0c85eb', 600: '#0067c9', 700: '#0152a3',
                            800: '#064687', 900: '#0a3c70', 950: '#07264a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased">
    
    <!-- Top Blue Header -->
    <header class="bg-gradient-to-r from-medical-950 via-medical-900 to-medical-800 text-white shadow-xl">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-square-plus text-cyan-400"></i>
                        <span>Tambah Data Alat Elektromedis</span>
                    </h1>
                    <p class="text-xs text-blue-200">Inventarisasi Alat Kesehatan Sesuai Standar Kemenkes RI</p>
                </div>
            </div>
            <a href="index.php" class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg border border-white/20 transition-all">
                Kembali ke Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Formulir Pendaftaran Alat Baru</h2>
                    <p class="text-xs text-slate-500">Lengkapi data teknis, penempatan ruangan, dan sertifikasi kalibrasi alat</p>
                </div>
                <span class="px-3 py-1 bg-medical-600 text-white text-xs font-semibold rounded-full shadow-sm">
                    SIM-RS Elektromedis
                </span>
            </div>

            <form method="POST" action="add.php" class="p-6 sm:p-8 space-y-8">
                
                <!-- BAGIAN 1: IDENTITAS ALAT -->
                <div>
                    <h3 class="text-sm font-bold text-medical-800 uppercase tracking-wider flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
                        <i class="fa-solid fa-microchip text-medical-600"></i>
                        <span>1. Identitas & Spesifikasi Teknis Alat</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Alat Elektromedis <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_alat" required placeholder="Contoh: Defibrillator / DC Shock Biphasic" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis / Klasifikasi Alat <span class="text-red-500">*</span></label>
                            <select name="jenis_alat" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <option value="Life Support (Penyokong Hidup)">Life Support (Penyokong Hidup)</option>
                                <option value="Monitoring Pasien">Monitoring Pasien</option>
                                <option value="Diagnostik & Pencitraan">Diagnostik & Pencitraan</option>
                                <option value="Terapi & Bedah">Terapi & Bedah</option>
                                <option value="Terapi & Pengobatan">Terapi & Pengobatan</option>
                                <option value="Laboratorium Klinis">Laboratorium Klinis</option>
                                <option value="Sterilisasi & CSSD">Sterilisasi & CSSD</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Merek Pabrikan <span class="text-red-500">*</span></label>
                            <input type="text" name="merek" required placeholder="Contoh: Mindray, GE, Dräger, Philips" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe / Model Alat</label>
                            <input type="text" name="tipe_model" placeholder="Contoh: BeneHeart D3 / Savina 300" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Seri (Serial Number / SN)</label>
                            <input type="text" name="nomor_seri" placeholder="Contoh: SN-2023-DF-8821" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Inventaris Alat (ASPAK)</label>
                            <input type="text" name="kode_alat" placeholder="Dikosongkan untuk auto-generate (contoh: EM-ICU-001)" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-slate-50">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Pengadaan / Perolehan</label>
                            <input type="text" name="tahun" value="<?php echo date('Y'); ?>" placeholder="Contoh: 2024" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Konsumsi Daya / Kelistrikan</label>
                            <input type="text" name="daya_listrik" placeholder="Contoh: 250 Watt / 220 VAC 50Hz" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: PENEMPATAN & KONDISI -->
                <div>
                    <h3 class="text-sm font-bold text-medical-800 uppercase tracking-wider flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
                        <i class="fa-solid fa-hospital-user text-medical-600"></i>
                        <span>2. Lokasi Penempatan & Tingkat Risiko</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Ruangan / Unit RS <span class="text-red-500">*</span></label>
                            <select name="lokasi" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <option value="ICU (Intensive Care Unit)">ICU (Intensive Care Unit)</option>
                                <option value="IGD (Instalasi Gawat Darurat)">IGD (Instalasi Gawat Darurat)</option>
                                <option value="Kamar Bedah Sentral (OK 1)">Kamar Bedah Sentral (OK 1)</option>
                                <option value="Kamar Bedah Sentral (OK 2)">Kamar Bedah Sentral (OK 2)</option>
                                <option value="NICU / PICU">NICU / PICU</option>
                                <option value="Ruang Rawat Inap Melati">Ruang Rawat Inap Melati</option>
                                <option value="Poliklinik Jantung & Pembuluh Darah">Poliklinik Jantung</option>
                                <option value="Poliklinik Kebidanan & Kandungan (Obgyn)">Poli Kandungan (Obgyn)</option>
                                <option value="Instalasi Radiologi">Instalasi Radiologi</option>
                                <option value="Laboratorium Patologi Klinik">Laboratorium Patologi</option>
                                <option value="Unit Hemodialisa (Cuci Darah)">Unit Hemodialisa</option>
                                <option value="Instalasi CSSD (Pusat Sterilisasi)">Instalasi CSSD</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Fisik & Fungsi</label>
                            <select name="kondisi" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <option value="Baik (Laik Operasi)">Baik (Laik Operasi)</option>
                                <option value="Rusak Ringan (Perlu Maintenance)">Rusak Ringan (Maintenance)</option>
                                <option value="Rusak Berat (Afkir)">Rusak Berat (Afkir)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Risiko (ECRI Standard)</label>
                            <select name="tingkat_risiko" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <option value="Tinggi (High Risk / Life Support)">Tinggi (High Risk / Life Support)</option>
                                <option value="Sedang (Medium Risk)" selected>Sedang (Medium Risk)</option>
                                <option value="Rendah (Low Risk)">Rendah (Low Risk)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: KALIBRASI KEMENKES -->
                <div>
                    <h3 class="text-sm font-bold text-medical-800 uppercase tracking-wider flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
                        <i class="fa-solid fa-certificate text-medical-600"></i>
                        <span>3. Data Kalibrasi & Sertifikasi Pengujian (Permenkes 54/2015)</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Kalibrasi Terakhir</label>
                            <input type="date" id="tglTerakhir" name="tgl_kalibrasi_terakhir" onchange="autoCalculateDueDate()" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jadwal Kalibrasi Berikutnya (Jatuh Tempo)</label>
                            <input type="date" id="tglBerikutnya" name="tgl_kalibrasi_berikutnya" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-blue-50/50">
                            <span class="text-[11px] text-slate-500 italic">* Otomatis dihitung +1 tahun sesuai regulasi Kemenkes</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Lembaga Penguji / Balai Kalibrasi</label>
                            <input type="text" name="lembaga_kalibrasi" value="Balai Pengamanan Fasilitas Kesehatan (BPFK) Surabaya" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Sertifikat Kalibrasi</label>
                            <input type="text" name="no_sertifikat" placeholder="Contoh: CAL/BPFK-SBY/IV/2026-112" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pemeliharaan / Kelengkapan Aksesori</label>
                            <textarea name="keterangan" rows="3" placeholder="Catatan teknis elektromedis, pergantian part, kondisi probe/lead, dll..." class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="index.php" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-semibold transition-all">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-medical-600 to-medical-700 hover:from-medical-700 hover:to-medical-800 text-white text-sm font-semibold shadow-lg shadow-medical-600/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Data Alat</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function autoCalculateDueDate() {
            const lastDateInput = document.getElementById('tglTerakhir');
            const nextDateInput = document.getElementById('tglBerikutnya');
            if (lastDateInput.value) {
                const date = new Date(lastDateInput.value);
                date.setFullYear(date.getFullYear() + 1);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                nextDateInput.value = `${year}-${month}-${day}`;
            }
        }
    </script>
</body>
</html>
