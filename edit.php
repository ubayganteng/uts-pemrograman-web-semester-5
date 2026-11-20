<?php
require_once __DIR__ . '/config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM alat WHERE id = ?");
$stmt->execute([$id]);
$alat = $stmt->fetch();

if (!$alat) {
    $_SESSION['flash_error'] = "Data alat dengan ID $id tidak ditemukan.";
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_alat = trim($_POST['kode_alat'] ?? '');
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $jenis_alat = trim($_POST['jenis_alat'] ?? '');
    $merek = trim($_POST['merek'] ?? '');
    $tipe_model = trim($_POST['tipe_model'] ?? '');
    $nomor_seri = trim($_POST['nomor_seri'] ?? '');
    $tahun = trim($_POST['tahun'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tgl_kalibrasi_terakhir = trim($_POST['tgl_kalibrasi_terakhir'] ?? '');
    $tgl_kalibrasi_berikutnya = trim($_POST['tgl_kalibrasi_berikutnya'] ?? '');
    $lembaga_kalibrasi = trim($_POST['lembaga_kalibrasi'] ?? '');
    $no_sertifikat = trim($_POST['no_sertifikat'] ?? '');
    $kondisi = trim($_POST['kondisi'] ?? '');
    $daya_listrik = trim($_POST['daya_listrik'] ?? '');
    $tingkat_risiko = trim($_POST['tingkat_risiko'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Status Kalibrasi Otomatis
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
        $user = current_user();
        $now = date('Y-m-d H:i:s');

        $stmtUpdate = $pdo->prepare("UPDATE alat SET
            kode_alat = ?, nama_alat = ?, jenis_alat = ?, merek = ?, tipe_model = ?,
            nomor_seri = ?, tahun = ?, lokasi = ?, tgl_kalibrasi_terakhir = ?,
            tgl_kalibrasi_berikutnya = ?, status_kalibrasi = ?, lembaga_kalibrasi = ?,
            no_sertifikat = ?, kondisi = ?, daya_listrik = ?, tingkat_risiko = ?, keterangan = ?,
            updated_by = ?, updated_at = ?
            WHERE id = ?");

        $stmtUpdate->execute([
            $kode_alat, $nama_alat, $jenis_alat, $merek, $tipe_model,
            $nomor_seri, $tahun, $lokasi, $tgl_kalibrasi_terakhir ?: null,
            $tgl_kalibrasi_berikutnya ?: null, $status_kalibrasi, $lembaga_kalibrasi,
            $no_sertifikat, $kondisi, $daya_listrik, $tingkat_risiko, $keterangan,
            $user['nama_lengkap'], $now, $id
        ]);

        // Catat log aktivitas pembaruan data
        catat_log($pdo, 'UPDATE_ALAT', "Memperbarui data alat: '$nama_alat' ($kode_alat). Jadwal kalibrasi: " . ($tgl_kalibrasi_berikutnya ?: '-') . ", Kondisi: $kondisi.");

        $_SESSION['flash_success'] = "Data alat '$nama_alat' ($kode_alat) berhasil diperbarui!";
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
    <title>Edit Alat Elektromedis - MEDITRONIK</title>
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
                        <i class="fa-solid fa-pen-to-square text-cyan-400"></i>
                        <span>Edit Data Alat Elektromedis</span>
                    </h1>
                    <p class="text-xs text-blue-200"><?php echo htmlspecialchars($alat['nama_alat'] . ' (' . ($alat['kode_alat'] ?: 'ID: ' . $alat['id']) . ')'); ?></p>
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
                    <h2 class="text-base font-bold text-slate-800">Perbarui Parameter & Jadwal Kalibrasi</h2>
                    <p class="text-xs text-slate-500">Sesuaikan tanggal kalibrasi, lokasi penempatan atau riwayat perbaikan</p>
                </div>
                <span class="px-3 py-1 bg-medical-600 text-white text-xs font-semibold rounded-full shadow-sm">
                    ID: #<?php echo $alat['id']; ?>
                </span>
            </div>

            <form method="POST" action="edit.php?id=<?php echo $id; ?>" class="p-6 sm:p-8 space-y-8">
                
                <!-- BAGIAN 1: IDENTITAS ALAT -->
                <div>
                    <h3 class="text-sm font-bold text-medical-800 uppercase tracking-wider flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
                        <i class="fa-solid fa-microchip text-medical-600"></i>
                        <span>1. Identitas & Spesifikasi Teknis Alat</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Alat Elektromedis <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_alat" required value="<?php echo htmlspecialchars($alat['nama_alat']); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis / Klasifikasi Alat <span class="text-red-500">*</span></label>
                            <select name="jenis_alat" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <?php
                                $categories = [
                                    'Life Support (Penyokong Hidup)',
                                    'Monitoring Pasien',
                                    'Diagnostik & Pencitraan',
                                    'Terapi & Bedah',
                                    'Terapi & Pengobatan',
                                    'Laboratorium Klinis',
                                    'Sterilisasi & CSSD'
                                ];
                                foreach ($categories as $cat) {
                                    $selected = ($alat['jenis_alat'] === $cat) ? 'selected' : '';
                                    echo "<option value=\"$cat\" $selected>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Merek Pabrikan <span class="text-red-500">*</span></label>
                            <input type="text" name="merek" required value="<?php echo htmlspecialchars($alat['merek']); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe / Model Alat</label>
                            <input type="text" name="tipe_model" value="<?php echo htmlspecialchars($alat['tipe_model'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Seri (Serial Number / SN)</label>
                            <input type="text" name="nomor_seri" value="<?php echo htmlspecialchars($alat['nomor_seri'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Inventaris Alat (ASPAK)</label>
                            <input type="text" name="kode_alat" value="<?php echo htmlspecialchars($alat['kode_alat'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Pengadaan / Perolehan</label>
                            <input type="text" name="tahun" value="<?php echo htmlspecialchars($alat['tahun'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Konsumsi Daya / Kelistrikan</label>
                            <input type="text" name="daya_listrik" value="<?php echo htmlspecialchars($alat['daya_listrik'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
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
                                <?php
                                $rooms = [
                                    'ICU (Intensive Care Unit)',
                                    'IGD (Instalasi Gawat Darurat)',
                                    'Kamar Bedah Sentral (OK 1)',
                                    'Kamar Bedah Sentral (OK 2)',
                                    'NICU / PICU',
                                    'Ruang Rawat Inap Melati',
                                    'Poliklinik Jantung & Pembuluh Darah',
                                    'Poliklinik Kebidanan & Kandungan (Obgyn)',
                                    'Instalasi Radiologi',
                                    'Laboratorium Patologi Klinik',
                                    'Unit Hemodialisa (Cuci Darah)',
                                    'Instalasi CSSD (Pusat Sterilisasi)'
                                ];
                                foreach ($rooms as $r) {
                                    $selected = ($alat['lokasi'] === $r) ? 'selected' : '';
                                    echo "<option value=\"$r\" $selected>$r</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Fisik & Fungsi</label>
                            <select name="kondisi" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <?php
                                $conditions = ['Baik (Laik Operasi)', 'Rusak Ringan (Perlu Maintenance)', 'Rusak Berat (Afkir)'];
                                foreach ($conditions as $c) {
                                    $selected = ($alat['kondisi'] === $c) ? 'selected' : '';
                                    echo "<option value=\"$c\" $selected>$c</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Risiko (ECRI)</label>
                            <select name="tingkat_risiko" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                                <?php
                                $risks = [
                                    'Tinggi (High Risk / Life Support)',
                                    'Sedang (Medium Risk)',
                                    'Rendah (Low Risk)'
                                ];
                                foreach ($risks as $rk) {
                                    $selected = ($alat['tingkat_risiko'] === $rk) ? 'selected' : '';
                                    echo "<option value=\"$rk\" $selected>$rk</option>";
                                }
                                ?>
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
                            <input type="date" id="tglTerakhir" name="tgl_kalibrasi_terakhir" value="<?php echo htmlspecialchars($alat['tgl_kalibrasi_terakhir'] ?? ''); ?>" onchange="autoCalculateDueDate()" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jadwal Kalibrasi Berikutnya (Jatuh Tempo)</label>
                            <input type="date" id="tglBerikutnya" name="tgl_kalibrasi_berikutnya" value="<?php echo htmlspecialchars($alat['tgl_kalibrasi_berikutnya'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-blue-50/50">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Lembaga Penguji / Balai Kalibrasi</label>
                            <input type="text" name="lembaga_kalibrasi" value="<?php echo htmlspecialchars($alat['lembaga_kalibrasi'] ?? 'Balai Pengamanan Fasilitas Kesehatan (BPFK)'); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Sertifikat Kalibrasi</label>
                            <input type="text" name="no_sertifikat" value="<?php echo htmlspecialchars($alat['no_sertifikat'] ?? ''); ?>" placeholder="Contoh: CAL/BPFK-SBY/IV/2026-112" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pemeliharaan / Kelengkapan Aksesori</label>
                            <textarea name="keterangan" rows="3" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500"><?php echo htmlspecialchars($alat['keterangan'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="index.php" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-semibold transition-all">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-medical-600 to-medical-700 hover:from-medical-700 hover:to-medical-800 text-white text-sm font-semibold shadow-lg shadow-medical-600/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
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
