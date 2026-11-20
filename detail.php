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
    $_SESSION['flash_error'] = "Data alat tidak ditemukan.";
    header("Location: index.php");
    exit;
}

$calBadge = calculate_calibration_badge($alat['tgl_kalibrasi_berikutnya']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Riwayat Alat: <?php echo htmlspecialchars($alat['nama_alat']); ?> - MEDITRONIK</title>
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
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased print:bg-white">

    <!-- Top Blue Header -->
    <header class="bg-gradient-to-r from-medical-950 via-medical-900 to-medical-800 text-white shadow-xl print:hidden">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-medical text-cyan-400"></i>
                        <span>Kartu Riwayat Alat Elektromedis</span>
                    </h1>
                    <p class="text-xs text-blue-200"><?php echo htmlspecialchars($alat['kode_alat'] ?: 'ID: #' . $alat['id']); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="cetak_label.php?id=<?php echo $alat['id']; ?>" target="_blank" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg shadow-sm transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-tag"></i>
                    <span>Cetak Stiker</span>
                </a>
                <a href="edit.php?id=<?php echo $alat['id']; ?>" class="text-xs bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1.5 rounded-lg shadow-sm transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Data</span>
                </a>
                <button onclick="window.print()" class="text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg border border-white/20 transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Kartu</span>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        
        <!-- Kartu Resmi Rumah Sakit -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            <!-- Hospital Header Strip -->
            <div class="p-6 bg-gradient-to-r from-medical-900 to-medical-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-cyan-300 text-2xl shadow-inner">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-cyan-300 font-semibold">Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)</div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($alat['nama_alat']); ?></h2>
                        <div class="flex items-center gap-2 text-xs text-blue-200 mt-1">
                            <span>Merek: <strong><?php echo htmlspecialchars($alat['merek']); ?></strong></span>
                            <span>•</span>
                            <span>Model: <strong><?php echo htmlspecialchars($alat['tipe_model'] ?: '-'); ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:items-end gap-1">
                    <span class="text-xs text-blue-200 font-medium">Status Kelaikan Operasional:</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?php echo $calBadge['badge']; ?>">
                        <span class="w-2 h-2 rounded-full bg-current"></span>
                        <?php echo $calBadge['status']; ?>
                    </span>
                    <span class="text-[11px] text-blue-200 font-medium mt-0.5"><?php echo $calBadge['text']; ?></span>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="p-6 sm:p-8 space-y-6">
                
                <!-- Identifikasi Utama -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <span class="text-xs text-slate-500 uppercase font-semibold block">Kode Inventaris / ASPAK</span>
                        <span class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($alat['kode_alat'] ?: '-'); ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase font-semibold block">Nomor Seri (SN)</span>
                        <span class="text-sm font-bold font-mono text-medical-700"><?php echo htmlspecialchars($alat['nomor_seri'] ?: '-'); ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase font-semibold block">Tahun Pengadaan</span>
                        <span class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($alat['tahun'] ?: '-'); ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase font-semibold block">Lokasi Penempatan</span>
                        <span class="text-sm font-bold text-medical-800"><?php echo htmlspecialchars($alat['lokasi']); ?></span>
                    </div>
                </div>

                <!-- Detail Teknis & Kalibrasi -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Box Spesifikasi -->
                    <div class="border border-slate-200 rounded-xl p-5 bg-white">
                        <h3 class="text-xs font-bold text-medical-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-medical-600"></i>
                            <span>Parameter Teknis & Keselamatan</span>
                        </h3>
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium w-44">Jenis / Klasifikasi</td>
                                    <td class="py-2.5 text-slate-800 font-bold"><?php echo htmlspecialchars($alat['jenis_alat']); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Tingkat Risiko (ECRI)</td>
                                    <td class="py-2.5">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <?php echo htmlspecialchars($alat['tingkat_risiko'] ?: 'Sedang'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Kondisi Fisik & Fungsi</td>
                                    <td class="py-2.5 text-slate-800 font-semibold"><?php echo htmlspecialchars($alat['kondisi']); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Konsumsi Daya Listrik</td>
                                    <td class="py-2.5 text-slate-800 font-semibold"><?php echo htmlspecialchars($alat['daya_listrik'] ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Safety Standard</td>
                                    <td class="py-2.5 text-slate-800 font-semibold">IEC 62353 / IEC 60601-1 Medical Safe</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Box Kalibrasi BPFK -->
                    <div class="border border-slate-200 rounded-xl p-5 bg-white">
                        <h3 class="text-xs font-bold text-medical-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <i class="fa-solid fa-certificate text-medical-600"></i>
                            <span>Sertifikasi Kalibrasi Kemenkes RI</span>
                        </h3>
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium w-44">Tanggal Kalibrasi Terakhir</td>
                                    <td class="py-2.5 text-slate-800 font-bold">
                                        <?php echo $alat['tgl_kalibrasi_terakhir'] ? date('d F Y', strtotime($alat['tgl_kalibrasi_terakhir'])) : '-'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Jatuh Tempo Kalibrasi</td>
                                    <td class="py-2.5 text-medical-700 font-bold">
                                        <?php echo $alat['tgl_kalibrasi_berikutnya'] ? date('d F Y', strtotime($alat['tgl_kalibrasi_berikutnya'])) : '-'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Lembaga / Lab Penguji</td>
                                    <td class="py-2.5 text-slate-800 font-semibold"><?php echo htmlspecialchars($alat['lembaga_kalibrasi'] ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Nomor Sertifikat Kalibrasi</td>
                                    <td class="py-2.5 text-slate-800 font-mono font-semibold"><?php echo htmlspecialchars($alat['no_sertifikat'] ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Regulasi Pengujian</td>
                                    <td class="py-2.5 text-slate-800 font-semibold">Permenkes RI No. 54 Tahun 2015</td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 text-slate-500 font-medium">Audit Pembaruan Data</td>
                                    <td class="py-2.5 text-slate-800 font-semibold">
                                        <?php echo !empty($alat['updated_at']) ? date('d/m/Y H:i', strtotime($alat['updated_at'])) . ' WIB' : 'Data awal tersimpan'; ?>
                                        <?php if (!empty($alat['updated_by'])): ?>
                                            <span class="text-medical-700 font-medium block text-[11px]">Diedit oleh: <?php echo htmlspecialchars($alat['updated_by']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Catatan Pemeliharaan -->
                <div class="border border-slate-200 rounded-xl p-5 bg-slate-50/50">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-notes-medical text-medical-600"></i>
                        <span>Catatan Teknisi & Pemeliharaan Berkala</span>
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($alat['keterangan'] ?: 'Tidak ada catatan khusus untuk alat ini. Seluruh parameter pengujian berada dalam toleransi batas laik operasional.')); ?>
                    </p>
                </div>

                <!-- Tanda Tangan Teknisi & Penanggung Jawab Elektromedis (Untuk Cetak Formal) -->
                <div class="pt-8 grid grid-cols-2 gap-8 text-center text-xs">
                    <div>
                        <p class="text-slate-500 mb-16">Mengetahui,<br><strong>Kepala Ruangan / Unit RS</strong></p>
                        <p class="font-bold text-slate-800 underline"><?php echo htmlspecialchars($alat['lokasi']); ?></p>
                        <p class="text-slate-500">NIP / ID Petugas</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-16">Diverifikasi Oleh,<br><strong>Teknisi Elektromedis (IPSRS)</strong></p>
                        <p class="font-bold text-slate-800 underline">Divisi Elektromedis RS</p>
                        <p class="text-slate-500">SIP-TEM / STR Terdaftar</p>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
