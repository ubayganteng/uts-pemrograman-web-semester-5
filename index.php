<?php
require_once __DIR__ . '/config.php';
require_login();

$user = current_user();

// Notifikasi Flash
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filter & Pencarian
$search = trim($_GET['search'] ?? '');
$filter_lokasi = trim($_GET['lokasi'] ?? '');
$filter_jenis = trim($_GET['jenis'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT * FROM alat WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (nama_alat LIKE ? OR merek LIKE ? OR tipe_model LIKE ? OR nomor_seri LIKE ? OR kode_alat LIKE ? OR lokasi LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

if (!empty($filter_lokasi)) {
    $query .= " AND lokasi = ?";
    $params[] = $filter_lokasi;
}

if (!empty($filter_jenis)) {
    $query .= " AND jenis_alat = ?";
    $params[] = $filter_jenis;
}

if (!empty($filter_status)) {
    $query .= " AND status_kalibrasi = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$alatList = $stmt->fetchAll();

// Hitung Statistik Keseluruhan
$statTotal = $pdo->query("SELECT COUNT(*) FROM alat")->fetchColumn();
$statRusak = $pdo->query("SELECT COUNT(*) FROM alat WHERE kondisi LIKE '%Rusak%'")->fetchColumn();

// Evaluasi status dinamis berdasarkan tanggal kalibrasi
$allAlat = $pdo->query("SELECT * FROM alat")->fetchAll();
$statLaik = 0;
$statMendekati = 0;
$statKadaluarsa = 0;
$urgentList = [];

// Distribusi Kategori untuk Chart
$catCounts = [];

foreach ($allAlat as $item) {
    $badge = calculate_calibration_badge($item['tgl_kalibrasi_berikutnya']);
    if ($badge['status'] === 'Terkalibrasi (Laik Pakai)') {
        $statLaik++;
    } elseif ($badge['status'] === 'Mendekati Jatuh Tempo') {
        $statMendekati++;
        $urgentList[] = array_merge($item, ['calBadge' => $badge]);
    } else {
        $statKadaluarsa++;
        $urgentList[] = array_merge($item, ['calBadge' => $badge]);
    }

    $c = $item['jenis_alat'] ?: 'Lainnya';
    $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}

// Ambil daftar unik lokasi dan jenis untuk dropdown filter
$daftarLokasi = $pdo->query("SELECT DISTINCT lokasi FROM alat WHERE lokasi IS NOT NULL AND lokasi != '' ORDER BY lokasi ASC")->fetchAll(PDO::FETCH_COLUMN);
$daftarJenis = $pdo->query("SELECT DISTINCT jenis_alat FROM alat WHERE jenis_alat IS NOT NULL AND jenis_alat != '' ORDER BY jenis_alat ASC")->fetchAll(PDO::FETCH_COLUMN);

// Ambil Log Aktivitas Terbaru untuk Widget Live Feed
$recentLogs = $pdo->query("SELECT * FROM log_aktivitas ORDER BY id DESC LIMIT 5")->fetchAll();
$totalLogCount = $pdo->query("SELECT COUNT(*) FROM log_aktivitas")->fetchColumn();
$totalUserCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Elektromedis & Kalibrasi - MEDITRONIK RS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        medical: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#b9ddfd',
                            300: '#7cc2fb',
                            400: '#36a2f7',
                            500: '#0c85eb',
                            600: '#0067c9',
                            700: '#0152a3',
                            800: '#064687',
                            900: '#0a3c70',
                            950: '#07264a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased flex flex-col">

    <!-- TOP HOSPITAL BLUE NAVBAR -->
    <nav class="bg-gradient-to-r from-medical-950 via-medical-900 to-medical-800 text-white sticky top-0 z-40 shadow-xl border-b border-blue-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Logo & Brand Title -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-tr from-cyan-500 to-medical-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30 ring-2 ring-white/20">
                        <i class="fa-solid fa-heart-pulse text-lg sm:text-2xl animate-pulse"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-xl font-extrabold tracking-tight text-white">MEDITRONIK</span>
                            <span class="text-[10px] sm:text-xs font-bold uppercase px-2 py-0.5 rounded-full bg-cyan-400/20 text-cyan-300 border border-cyan-400/30">IPSRS RS</span>
                        </div>
                        <p class="text-[10px] sm:text-xs text-blue-200/80 hidden sm:block">Sistem Pengawasan Kelaikan & Jadwal Kalibrasi Alat Elektromedis</p>
                    </div>
                </div>

                <!-- Right Action Bar & User Profile -->
                <div class="flex items-center gap-2 sm:gap-4">
                    
                    <!-- Menu Navigasi Utama -->
                    <div class="hidden lg:flex items-center gap-1.5 mr-2">
                        <a href="index.php" class="px-3 py-1.5 rounded-xl bg-white/20 text-white text-xs font-semibold shadow-inner">
                            <i class="fa-solid fa-laptop-medical mr-1.5 text-cyan-300"></i> Inventaris
                        </a>
                        <a href="log_aktivitas.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-blue-100 hover:text-white text-xs font-semibold transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-clock-rotate-left text-cyan-300"></i>
                            <span>Log Aktivitas</span>
                            <span class="px-1.5 py-0.2 rounded-full bg-cyan-400/30 text-cyan-200 text-[10px]"><?php echo $totalLogCount; ?></span>
                        </a>
                        <a href="users.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-blue-100 hover:text-white text-xs font-semibold transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-users text-cyan-300"></i>
                            <span>Pengguna</span>
                            <span class="px-1.5 py-0.2 rounded-full bg-emerald-400/30 text-emerald-200 text-[10px]"><?php echo $totalUserCount; ?></span>
                        </a>
                    </div>

                    <!-- Tombol Cepat Tambah Alat -->
                    <a href="add.php" class="hidden md:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-medical-500 hover:from-cyan-600 hover:to-medical-600 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-cyan-500/25 transition-all">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Alat</span>
                    </a>

                    <!-- User Profile Dropdown Pill -->
                    <div class="flex items-center gap-3 pl-2 sm:pl-3 border-l border-white/10">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs sm:text-sm font-bold text-white"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                            <div class="text-[10px] sm:text-[11px] text-cyan-300 font-medium"><?php echo htmlspecialchars($user['role']); ?></div>
                        </div>
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-medical-700 border-2 border-cyan-400/40 flex items-center justify-center text-white text-sm font-bold uppercase shadow-inner">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" class="p-2 text-blue-200 hover:text-red-400 hover:bg-white/10 rounded-xl transition-all" title="Keluar / Logout">
                            <i class="fa-solid fa-right-from-bracket text-base"></i>
                        </a>
                    </div>

                </div>

            </div>
        </div>
    </nav>

    <!-- MAIN BODY CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

        <!-- Flash Messages -->
        <?php if (!empty($flash_success)): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($flash_success); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_error)): ?>
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($flash_error); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- WELCOME BANNER & STANDARD NOTICE -->
        <div class="bg-gradient-to-r from-medical-900 via-medical-800 to-indigo-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-blue-800/40">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-cyan-400/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-cyan-300 text-xs font-semibold backdrop-blur-md mb-2">
                        <i class="fa-solid fa-circle-dot text-[9px] text-emerald-400 animate-ping"></i>
                        <span>Sistem Aktif & Terintegrasi Regulasi Kemenkes</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, <?php echo htmlspecialchars($user['nama_lengkap']); ?>!</h2>
                    <p class="text-blue-100/80 text-xs sm:text-sm mt-1 max-w-2xl leading-relaxed">
                        Pantau kelayakan fisik, parameter kelistrikan medis (IEC 62353), dan kepatuhan masa kalibrasi berkala (Permenkes 54/2015) untuk seluruh alat elektromedis rumah sakit.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="cetak_laporan.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-semibold border border-white/20 backdrop-blur-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-print text-cyan-300"></i>
                        <span>Cetak Rekap Kalibrasi</span>
                    </a>
                    <a href="export.php" class="px-4 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-600 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-cyan-500/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Ekspor Excel / CSV</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- STATISTIC METRIC CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
            
            <!-- Card 1: Total Alat -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200/80 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Alat Medis</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-medical-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-laptop-medical"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?php echo $statTotal; ?></div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-check text-emerald-500"></i> Terdata di inventaris
                </div>
            </div>

            <!-- Card 2: Terkalibrasi (Laik) -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-emerald-100 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Laik Pakai</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600"><?php echo $statLaik; ?></div>
                <div class="text-[11px] text-emerald-600/80 mt-1">Sertifikat kalibrasi aktif</div>
            </div>

            <!-- Card 3: Mendekati Jatuh Tempo -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-amber-100 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Segera Kalibrasi</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-amber-600"><?php echo $statMendekati; ?></div>
                <div class="text-[11px] text-amber-700/80 mt-1">Jatuh tempo &le; 30 hari</div>
            </div>

            <!-- Card 4: Kadaluarsa / Expired -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-red-100 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-700">Kadaluarsa</span>
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-red-600"><?php echo $statKadaluarsa; ?></div>
                <div class="text-[11px] text-red-600/80 mt-1">Perlu uji kalibrasi ulang</div>
            </div>

            <!-- Card 5: Kondisi Rusak -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-indigo-100 hover:shadow-md transition-all col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Maintenance</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-indigo-600"><?php echo $statRusak; ?></div>
                <div class="text-[11px] text-indigo-600/80 mt-1">Perbaikan / Perawatan</div>
            </div>

        </div>

        <!-- URGENT CALIBRATION ALERT BANNER (Jika ada yang mendekati / kadaluarsa) -->
        <?php if (!empty($urgentList)): ?>
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <h3 class="text-sm font-bold text-amber-900">Perhatian: <?php echo count($urgentList); ?> Alat Elektromedis Membutuhkan Tindakan Kalibrasi</h3>
                    </div>
                    <span class="text-xs text-amber-800 font-semibold hidden sm:inline">Hubungi Lab BPFK / Kalibrasi</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach (array_slice($urgentList, 0, 3) as $urg): ?>
                        <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-sm flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800"><?php echo htmlspecialchars($urg['nama_alat']); ?></h4>
                                <p class="text-[11px] text-slate-500"><?php echo htmlspecialchars($urg['lokasi']); ?></p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold <?php echo $urg['calBadge']['badge']; ?>">
                                    <?php echo $urg['calBadge']['text']; ?>
                                </span>
                            </div>
                            <a href="detail.php?id=<?php echo $urg['id']; ?>" class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-bold transition-all">
                                Periksa <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- CHARTS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Chart 1: Distribusi Kategori Alat -->
            <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Distribusi Kategori Alat Elektromedis</h3>
                        <p class="text-xs text-slate-500">Klasifikasi instrumen berdasarkan fungsi pelayanan medis RS</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-medical-700">
                        <i class="fa-solid fa-chart-pie mr-1"></i> Analisis
                    </span>
                </div>
                <div class="h-64 sm:h-72">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Status Kalibrasi Kelaikan -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Status Kelaikan Kalibrasi</h3>
                            <p class="text-xs text-slate-500">Berdasarkan tanggal jatuh tempo</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">
                            Permenkes 54/15
                        </span>
                    </div>
                    <div class="h-52 flex items-center justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Laik Pakai
                        </span>
                        <strong class="text-slate-800"><?php echo $statLaik; ?> unit</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span> Mendekati Jatuh Tempo
                        </span>
                        <strong class="text-slate-800"><?php echo $statMendekati; ?> unit</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span> Kadaluarsa (Expired)
                        </span>
                        <strong class="text-slate-800"><?php echo $statKadaluarsa; ?> unit</strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- LIVE AUDIT & ACTIVITY FEED WIDGET -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-bolt text-cyan-600 animate-pulse"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Aktivitas Terkini (Siapa Masuk, Registrasi & Perubahan Data)</h3>
                        <p class="text-xs text-slate-500">Pemantauan langsung secara real-time setiap kali ada pengguna yang beraktivitas</p>
                    </div>
                </div>
                <a href="log_aktivitas.php" class="text-xs font-bold text-medical-700 hover:text-medical-800 flex items-center gap-1 group">
                    <span>Lihat Semua Log Audit (<?php echo $totalLogCount; ?>)</span>
                    <i class="fa-solid fa-arrow-right text-[10px] transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($recentLogs as $rl): 
                    $time = strtotime($rl['created_at']);
                    $tipe = $rl['tipe_aktivitas'];
                    $color = 'bg-blue-50 text-blue-700 border-blue-200';
                    $icon = 'fa-circle-info';
                    if ($tipe === 'LOGIN') { $color = 'bg-emerald-50 text-emerald-700 border-emerald-200'; $icon = 'fa-right-to-bracket'; }
                    elseif ($tipe === 'REGISTRASI') { $color = 'bg-cyan-50 text-cyan-700 border-cyan-200'; $icon = 'fa-user-plus'; }
                    elseif ($tipe === 'UPDATE_ALAT') { $color = 'bg-amber-50 text-amber-800 border-amber-200'; $icon = 'fa-pen-to-square'; }
                    elseif ($tipe === 'TAMBAH_ALAT') { $color = 'bg-indigo-50 text-indigo-700 border-indigo-200'; $icon = 'fa-plus-circle'; }
                    elseif ($tipe === 'HAPUS_ALAT') { $color = 'bg-red-50 text-red-700 border-red-200'; $icon = 'fa-trash-can'; }
                ?>
                    <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:bg-white hover:shadow-sm transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo $color; ?>">
                                    <i class="fa-solid <?php echo $icon; ?> text-[9px]"></i>
                                    <span><?php echo htmlspecialchars($tipe); ?></span>
                                </span>
                                <span class="text-[10px] font-mono text-slate-400"><?php echo date('d/m/Y H:i', $time); ?></span>
                            </div>
                            <p class="text-xs text-slate-800 font-semibold line-clamp-2"><?php echo htmlspecialchars($rl['deskripsi']); ?></p>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
                            <span class="flex items-center gap-1 font-medium text-slate-700">
                                <i class="fa-regular fa-user text-[10px] text-slate-400"></i>
                                <span><?php echo htmlspecialchars($rl['nama_pengguna'] ?: $rl['username']); ?></span>
                            </span>
                            <span class="font-mono text-[10px] text-slate-400"><?php echo htmlspecialchars($rl['ip_address'] ?: '127.0.0.1'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- FILTER & DATA TABLE SECTION -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            
            <!-- Table Action & Filter Toolbar -->
            <div class="p-5 sm:p-6 border-b border-slate-200/80 bg-slate-50/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-medical-600"></i>
                            <span>Daftar Inventaris Alat Elektromedis & Kalibrasi</span>
                        </h3>
                        <p class="text-xs text-slate-500">Menampilkan <?php echo count($alatList); ?> instrumen medis aktif terdata</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="add.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-medical-600 to-medical-700 hover:from-medical-700 hover:to-medical-800 text-white text-xs sm:text-sm font-semibold shadow-md shadow-medical-600/30 transition-all">
                            <i class="fa-solid fa-plus"></i>
                            <span>Tambah Alat Baru</span>
                        </a>
                    </div>
                </div>

                <!-- Form Filter Bar -->
                <form method="GET" action="index.php" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, merek, SN..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-white">
                    </div>

                    <!-- Dropdown Lokasi Ruangan -->
                    <div>
                        <select name="lokasi" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-white">
                            <option value="">Semua Lokasi Ruangan</option>
                            <?php foreach ($daftarLokasi as $lok): ?>
                                <option value="<?php echo htmlspecialchars($lok); ?>" <?php echo $filter_lokasi === $lok ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lok); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dropdown Jenis Alat -->
                    <div>
                        <select name="jenis" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 bg-white">
                            <option value="">Semua Kategori Alat</option>
                            <?php foreach ($daftarJenis as $jen): ?>
                                <option value="<?php echo htmlspecialchars($jen); ?>" <?php echo $filter_jenis === $jen ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jen); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Action Buttons Filter -->
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 px-3 bg-medical-700 hover:bg-medical-800 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-filter"></i>
                            <span>Terapkan</span>
                        </button>
                        <?php if ($search || $filter_lokasi || $filter_jenis || $filter_status): ?>
                            <a href="index.php" class="p-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold transition-all" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>

            <!-- TABEL DATA ALAT ELEKTROMEDIS -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Nama & Model Alat</th>
                            <th class="py-3.5 px-4">Kategori & Risiko</th>
                            <th class="py-3.5 px-4">Lokasi Ruangan</th>
                            <th class="py-3.5 px-4">Tahun & Kondisi</th>
                            <th class="py-3.5 px-4">Jadwal Kalibrasi</th>
                            <th class="py-3.5 px-4 text-center">Status Kelaikan</th>
                            <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (empty($alatList)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                                        <p class="text-sm font-semibold text-slate-600">Tidak ada data alat elektromedis yang sesuai dengan kriteria filter.</p>
                                        <a href="index.php" class="text-xs text-medical-600 font-semibold hover:underline">Reset Kriteria Pencarian</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($alatList as $item): 
                                $cal = calculate_calibration_badge($item['tgl_kalibrasi_berikutnya']);
                                $isHighRisk = strpos($item['tingkat_risiko'] ?? '', 'Tinggi') !== false;
                            ?>
                                <tr class="hover:bg-blue-50/40 transition-colors">
                                    <td class="py-4 px-4 text-center font-bold text-slate-400">
                                        <?php echo $no++; ?>
                                    </td>

                                    <!-- Nama & Model -->
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-900 text-sm leading-snug">
                                            <?php echo htmlspecialchars($item['nama_alat']); ?>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 mt-1">
                                            <span class="font-semibold text-medical-800"><?php echo htmlspecialchars($item['merek']); ?></span>
                                            <span>•</span>
                                            <span><?php echo htmlspecialchars($item['tipe_model'] ?: '-'); ?></span>
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px]">
                                                SN: <?php echo htmlspecialchars($item['nomor_seri'] ?: '-'); ?>
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                            Kode: <?php echo htmlspecialchars($item['kode_alat'] ?: 'EM-' . $item['id']); ?>
                                        </div>
                                    </td>

                                    <!-- Kategori & Risiko -->
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-700">
                                            <?php echo htmlspecialchars($item['jenis_alat']); ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $isHighRisk ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-slate-100 text-slate-700'; ?>">
                                                <?php echo htmlspecialchars($item['tingkat_risiko'] ?: 'Sedang'); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Lokasi Ruangan -->
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                            <i class="fa-solid fa-door-open text-medical-600 text-xs"></i>
                                            <span><?php echo htmlspecialchars($item['lokasi']); ?></span>
                                        </div>
                                    </td>

                                    <!-- Tahun & Kondisi -->
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-700">
                                            Thn: <?php echo htmlspecialchars($item['tahun'] ?: '-'); ?>
                                        </div>
                                        <div class="text-[11px] mt-0.5 <?php echo strpos($item['kondisi'], 'Rusak') !== false ? 'text-red-600 font-bold' : 'text-slate-500'; ?>">
                                            <?php echo htmlspecialchars($item['kondisi']); ?>
                                        </div>
                                    </td>

                                    <!-- Jadwal Kalibrasi -->
                                    <td class="py-4 px-4">
                                        <div class="text-[11px] text-slate-500">
                                            Terakhir: <strong><?php echo $item['tgl_kalibrasi_terakhir'] ? date('d/m/Y', strtotime($item['tgl_kalibrasi_terakhir'])) : '-'; ?></strong>
                                        </div>
                                        <div class="text-xs font-bold text-slate-800 mt-0.5">
                                            Tempo: <?php echo $item['tgl_kalibrasi_berikutnya'] ? date('d/m/Y', strtotime($item['tgl_kalibrasi_berikutnya'])) : '-'; ?>
                                        </div>
                                        <div class="text-[10px] font-semibold mt-1">
                                            <span class="px-2 py-0.5 rounded-md <?php echo $cal['badge']; ?>">
                                                <?php echo $cal['text']; ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status Kalibrasi -->
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border <?php echo $cal['badge']; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            <?php echo $cal['status']; ?>
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Detail Button -->
                                            <a href="detail.php?id=<?php echo $item['id']; ?>" class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-medical-600 hover:text-white text-medical-700 flex items-center justify-center transition-all" title="Lihat Kartu Riwayat Alat">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </a>
                                            <!-- Cetak Stiker Button -->
                                            <a href="cetak_label.php?id=<?php echo $item['id']; ?>" target="_blank" class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 flex items-center justify-center transition-all" title="Cetak Stiker Kalibrasi Laik Pakai">
                                                <i class="fa-solid fa-tag text-xs"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 flex items-center justify-center transition-all" title="Edit Data Alat">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <a href="delete.php?id=<?php echo $item['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus alat: <?php echo addslashes($item['nama_alat']); ?>?')" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-600 hover:text-white text-red-700 flex items-center justify-center transition-all" title="Hapus Data">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>
                    Menampilkan <strong><?php echo count($alatList); ?></strong> dari <strong><?php echo $statTotal; ?></strong> alat kesehatan terdaftar
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Laik</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Waspada</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Expired</span>
                </div>
            </div>

        </div>

    </main>

    <!-- FOOTER RS -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500 mt-12">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-medical-800 font-bold">
                <i class="fa-solid fa-shield-heart text-base text-medical-600"></i>
                <span>MEDITRONIK - Sistem Informasi Elektromedis RS</span>
            </div>
            <div>
                Pemeliharaan Sarana, Prasarana, dan Peralatan Kesehatan Sesuai Standar Kemenkes RI
            </div>
            <div>
                &copy; 2026 Instalasi IPSRS Medis
            </div>
        </div>
    </footer>

    <!-- CHART INITIALIZATION SCRIPTS -->
    <script>
        // Data Kategori dari PHP
        const catLabels = <?php echo json_encode(array_keys($catCounts)); ?>;
        const catData = <?php echo json_encode(array_values($catCounts)); ?>;

        // Chart Distribusi Kategori
        const ctxCat = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Jumlah Unit Alat',
                    data: catData,
                    backgroundColor: [
                        'rgba(14, 116, 144, 0.85)',
                        'rgba(37, 99, 235, 0.85)',
                        'rgba(99, 102, 241, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                        'rgba(107, 114, 128, 0.85)'
                    ],
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#64748b' },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { 
                            color: '#64748b',
                            font: { size: 10 }
                        },
                        grid: { display: false }
                    }
                }
            }
        });

        // Chart Status Kelaikan (Doughnut)
        const ctxStat = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStat, {
            type: 'doughnut',
            data: {
                labels: ['Laik Pakai', 'Mendekati Jatuh Tempo', 'Kadaluarsa'],
                datasets: [{
                    data: [<?php echo $statLaik; ?>, <?php echo $statMendekati; ?>, <?php echo $statKadaluarsa; ?>],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    hoverOffset: 4,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    </script>

</body>
</html>
