<?php
require_once __DIR__ . '/config.php';
require_login();

$lokasiFilter = trim($_GET['lokasi'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM alat WHERE 1=1";
$params = [];

if (!empty($lokasiFilter)) {
    $sql .= " AND lokasi = ?";
    $params[] = $lokasiFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND status_kalibrasi = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY tgl_kalibrasi_berikutnya ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alatList = $stmt->fetchAll();

// Hitung rekap statistik
$total = count($alatList);
$laik = 0;
$mendekati = 0;
$kadaluarsa = 0;

foreach ($alatList as $a) {
    $b = calculate_calibration_badge($a['tgl_kalibrasi_berikutnya']);
    if ($b['status'] === 'Terkalibrasi (Laik Pakai)') $laik++;
    elseif ($b['status'] === 'Mendekati Jatuh Tempo') $mendekati++;
    else $kadaluarsa++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Kalibrasi Alat Elektromedis RS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            body { background: white !important; font-size: 11px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-800">

    <!-- Top Action Toolbar -->
    <div class="no-print max-w-6xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-2">
            <a href="index.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
            <span class="text-xs text-slate-500">Pratinjau Cetak Format Resmi Kemenkes RI</span>
        </div>
        <button onclick="window.print()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Dokumen (PDF)</span>
        </button>
    </div>

    <!-- Dokumen Utama Laporan -->
    <div class="max-w-6xl mx-auto bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0">
        
        <!-- KOP SURAT RUMAH SAKIT -->
        <div class="border-b-2 border-slate-900 pb-4 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-blue-900 text-white flex items-center justify-center text-3xl font-bold shadow-md">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold uppercase tracking-tight text-slate-900 leading-tight">RUMAH SAKIT UMUM PUSAT MEDIKA UTAMA</h1>
                    <p class="text-xs text-slate-600 font-medium">INSTALASI PEMELIHARAAN SARANA RUMAH SAKIT (IPSRS) & DIVISI ELEKTROMEDIS</p>
                    <p class="text-[11px] text-slate-500">Jl. Kesehatan Medika No. 88, Telp: (021) 7788-9900 | Email: elektromedis@rumahsakit.co.id</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block border border-slate-300 px-3 py-1 text-[10px] uppercase font-bold text-slate-700 rounded-md">
                    FORM: IPSRS-KAL-2026
                </span>
                <p class="text-[11px] text-slate-500 mt-1">Dicetak: <?php echo date('d F Y H:i'); ?></p>
            </div>
        </div>

        <!-- Judul Laporan -->
        <div class="text-center mb-6">
            <h2 class="text-base font-extrabold uppercase text-slate-900 tracking-wide">
                LAPORAN REKAPITULASI JADWAL KALIBRASI & KELAIKAN ALAT ELEKTROMEDIS
            </h2>
            <p class="text-xs text-slate-600 mt-0.5">
                Standar Pengawasan & Pengujian Sesuai Permenkes RI Nomor 54 Tahun 2015
                <?php if ($lokasiFilter): ?>
                    <span class="font-bold text-blue-700"> | Unit: <?php echo htmlspecialchars($lokasiFilter); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Rekap Statistik Singkat -->
        <div class="grid grid-cols-4 gap-3 mb-6 p-3 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs">
            <div>
                <span class="text-slate-500 block">Total Alat Terdata</span>
                <strong class="text-base text-slate-900"><?php echo $total; ?> Unit</strong>
            </div>
            <div>
                <span class="text-emerald-700 block">Terkalibrasi / Laik Pakai</span>
                <strong class="text-base text-emerald-700"><?php echo $laik; ?> Unit</strong>
            </div>
            <div>
                <span class="text-amber-700 block">Mendekati Jatuh Tempo</span>
                <strong class="text-base text-amber-700"><?php echo $mendekati; ?> Unit</strong>
            </div>
            <div>
                <span class="text-red-700 block">Kadaluarsa / Perlu Kalibrasi</span>
                <strong class="text-base text-red-700"><?php echo $kadaluarsa; ?> Unit</strong>
            </div>
        </div>

        <!-- Tabel Data Alat -->
        <div class="overflow-x-auto mb-8">
            <table class="w-full text-left text-xs border border-slate-300 divide-y divide-slate-300">
                <thead class="bg-blue-900 text-white font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="p-2 border border-blue-950 text-center w-8">No</th>
                        <th class="p-2 border border-blue-950">Kode & Nama Alat</th>
                        <th class="p-2 border border-blue-950">Merek / Seri</th>
                        <th class="p-2 border border-blue-950">Ruangan RS</th>
                        <th class="p-2 border border-blue-950">Tgl Kalibrasi</th>
                        <th class="p-2 border border-blue-950">Jatuh Tempo</th>
                        <th class="p-2 border border-blue-950 text-center">Status Kalibrasi</th>
                        <th class="p-2 border border-blue-950">Lembaga Penguji</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php 
                    $no = 1;
                    foreach ($alatList as $row): 
                        $b = calculate_calibration_badge($row['tgl_kalibrasi_berikutnya']);
                    ?>
                        <tr class="<?php echo $no % 2 === 0 ? 'bg-slate-50/70' : 'bg-white'; ?>">
                            <td class="p-2 border border-slate-300 text-center font-semibold"><?php echo $no++; ?></td>
                            <td class="p-2 border border-slate-300">
                                <strong class="text-slate-900"><?php echo htmlspecialchars($row['nama_alat']); ?></strong>
                                <div class="text-[10px] text-slate-500 font-mono"><?php echo htmlspecialchars($row['kode_alat'] ?: '-'); ?></div>
                            </td>
                            <td class="p-2 border border-slate-300">
                                <?php echo htmlspecialchars($row['merek'] . ' ' . $row['tipe_model']); ?>
                                <div class="text-[10px] text-slate-500 font-mono">SN: <?php echo htmlspecialchars($row['nomor_seri'] ?: '-'); ?></div>
                            </td>
                            <td class="p-2 border border-slate-300 font-medium"><?php echo htmlspecialchars($row['lokasi']); ?></td>
                            <td class="p-2 border border-slate-300 text-slate-700">
                                <?php echo $row['tgl_kalibrasi_terakhir'] ? date('d/m/Y', strtotime($row['tgl_kalibrasi_terakhir'])) : '-'; ?>
                            </td>
                            <td class="p-2 border border-slate-300 font-bold text-slate-800">
                                <?php echo $row['tgl_kalibrasi_berikutnya'] ? date('d/m/Y', strtotime($row['tgl_kalibrasi_berikutnya'])) : '-'; ?>
                            </td>
                            <td class="p-2 border border-slate-300 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?php echo $b['badge']; ?>">
                                    <?php echo $b['status']; ?>
                                </span>
                            </td>
                            <td class="p-2 border border-slate-300 text-[11px] text-slate-600">
                                <?php echo htmlspecialchars($row['lembaga_kalibrasi'] ?: 'BPFK'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan Resmi Pengesahan -->
        <div class="grid grid-cols-3 gap-6 text-center text-xs pt-4 border-t border-slate-200">
            <div>
                <p class="text-slate-500 mb-16">Mengetahui,<br><strong>Kepala Instalasi IPSRS</strong></p>
                <p class="font-bold text-slate-900 underline">Ir. Bambang Sugiarto, M.T.</p>
                <p class="text-slate-500">NIP. 19780512 200501 1 004</p>
            </div>
            <div>
                <p class="text-slate-500 mb-16">Penanggung Jawab Kalibrasi,<br><strong>Koordinator Elektromedis</strong></p>
                <p class="font-bold text-slate-900 underline">Raden Pratama, S.Tr.TEM</p>
                <p class="text-slate-500">STR. 120952120-2027</p>
            </div>
            <div>
                <p class="text-slate-500 mb-16">Menyetujui,<br><strong>Direktur Medik & Keperawatan</strong></p>
                <p class="font-bold text-slate-900 underline">dr. Sarah Amalia, Sp.A(K)</p>
                <p class="text-slate-500">NIP. 19820318 200812 2 001</p>
            </div>
        </div>

    </div>

</body>
</html>
