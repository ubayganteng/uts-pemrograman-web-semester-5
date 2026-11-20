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
    die("Alat tidak ditemukan.");
}

$calBadge = calculate_calibration_badge($alat['tgl_kalibrasi_berikutnya']);
$isLaik = ($calBadge['status'] !== 'Kadaluarsa (Expired)');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stiker Kalibrasi: <?php echo htmlspecialchars($alat['nama_alat']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-code {
            font-family: 'Share Tech Mono', monospace;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sticker-card {
                box-shadow: none !important;
                border: 2px solid #000 !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-6 flex flex-col items-center justify-center">

    <!-- Print Control Bar -->
    <div class="no-print mb-6 flex items-center gap-3">
        <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md text-sm font-semibold flex items-center gap-2">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Stiker Kalibrasi</span>
        </button>
        <button onclick="window.close()" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-sm font-semibold">
            Tutup
        </button>
    </div>

    <!-- PHYSICAL CALIBRATION STICKER (STANDAR KEMENKES RI / BPFK) -->
    <div class="sticker-card w-[420px] bg-white rounded-2xl shadow-xl overflow-hidden border-2 <?php echo $isLaik ? 'border-emerald-600' : 'border-red-600'; ?>">
        
        <!-- Header Strip -->
        <div class="px-5 py-3 text-white flex items-center justify-between <?php echo $isLaik ? 'bg-emerald-600' : 'bg-red-600'; ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-shield-heart text-2xl"></i>
                <div>
                    <h3 class="text-xs uppercase tracking-widest font-black leading-tight">INSTALASI ELEKTROMEDIS RS</h3>
                    <p class="text-[10px] text-white/90">Permenkes RI No. 54 Tahun 2015</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[11px] font-black uppercase px-2 py-0.5 rounded bg-white <?php echo $isLaik ? 'text-emerald-700' : 'text-red-700'; ?>">
                    <?php echo $isLaik ? 'LAIK PAKAI' : 'TIDAK LAIK'; ?>
                </span>
            </div>
        </div>

        <!-- Body Stiker -->
        <div class="p-5 space-y-3.5 bg-white text-slate-800">
            
            <div class="flex items-start justify-between border-b pb-3 border-slate-100">
                <div class="flex-1 pr-3">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">NAMA ALAT KESEHATAN:</span>
                    <h4 class="text-sm font-extrabold text-slate-900 leading-snug"><?php echo htmlspecialchars($alat['nama_alat']); ?></h4>
                    <p class="text-xs text-slate-600"><?php echo htmlspecialchars($alat['merek'] . ' ' . $alat['tipe_model']); ?></p>
                </div>
                <!-- Simulated QR Code -->
                <div class="w-16 h-16 border border-slate-300 p-1 rounded-lg flex flex-col items-center justify-center bg-slate-50 text-center flex-shrink-0">
                    <i class="fa-solid fa-qrcode text-3xl text-slate-800"></i>
                    <span class="text-[8px] font-mono-code text-slate-500 mt-0.5">VERIFIED</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">KODE ALAT:</span>
                    <span class="font-bold font-mono-code text-slate-800"><?php echo htmlspecialchars($alat['kode_alat'] ?: '-'); ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">NO. SERI (SN):</span>
                    <span class="font-bold font-mono-code text-slate-800"><?php echo htmlspecialchars($alat['nomor_seri'] ?: '-'); ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">RUANGAN / UNIT:</span>
                    <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($alat['lokasi']); ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">NO. SERTIFIKAT:</span>
                    <span class="font-semibold font-mono-code text-slate-800 text-[11px]"><?php echo htmlspecialchars($alat['no_sertifikat'] ?: 'CAL/REG/2026'); ?></span>
                </div>
            </div>

            <!-- Date Highlight Box -->
            <div class="p-3 rounded-xl border flex items-center justify-between <?php echo $isLaik ? 'bg-emerald-50/70 border-emerald-200' : 'bg-red-50/70 border-red-200'; ?>">
                <div>
                    <span class="text-[10px] font-bold uppercase <?php echo $isLaik ? 'text-emerald-700' : 'text-red-700'; ?> block">TGL. KALIBRASI:</span>
                    <span class="text-xs font-bold text-slate-800">
                        <?php echo $alat['tgl_kalibrasi_terakhir'] ? date('d-m-Y', strtotime($alat['tgl_kalibrasi_terakhir'])) : '-'; ?>
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase <?php echo $isLaik ? 'text-emerald-700' : 'text-red-700'; ?> block">BERLAKU S/D:</span>
                    <span class="text-xs font-black <?php echo $isLaik ? 'text-emerald-800' : 'text-red-800'; ?>">
                        <?php echo $alat['tgl_kalibrasi_berikutnya'] ? date('d-m-Y', strtotime($alat['tgl_kalibrasi_berikutnya'])) : '-'; ?>
                    </span>
                </div>
            </div>

            <!-- Footer Sign -->
            <div class="pt-1 flex items-center justify-between text-[10px] text-slate-500 border-t border-slate-100">
                <div>
                    <span>Penguji: <strong><?php echo htmlspecialchars($alat['lembaga_kalibrasi'] ?: 'BPFK'); ?></strong></span>
                </div>
                <div class="text-right italic">
                    Paraf Petugas: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]
                </div>
            </div>

        </div>
    </div>

    <p class="no-print text-xs text-slate-500 mt-4 text-center">
        * Gunakan kertas stiker label vinil/thermal untuk ditempelkan langsung pada bodi fisik instrumen medis.
    </p>

</body>
</html>
