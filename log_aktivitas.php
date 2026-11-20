<?php
require_once __DIR__ . '/config.php';
require_login();

$user = current_user();

$filter_tipe = trim($_GET['tipe'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM log_aktivitas WHERE 1=1";
$params = [];

if (!empty($filter_tipe)) {
    $sql .= " AND tipe_aktivitas = ?";
    $params[] = $filter_tipe;
}

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR nama_pengguna LIKE ? OR deskripsi LIKE ? OR ip_address LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " ORDER BY id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Statistik aktivitas
$statTotal = $pdo->query("SELECT COUNT(*) FROM log_aktivitas")->fetchColumn();
$statLogin = $pdo->query("SELECT COUNT(*) FROM log_aktivitas WHERE tipe_aktivitas = 'LOGIN'")->fetchColumn();
$statUpdate = $pdo->query("SELECT COUNT(*) FROM log_aktivitas WHERE tipe_aktivitas = 'UPDATE_ALAT'")->fetchColumn();
$statReg = $pdo->query("SELECT COUNT(*) FROM log_aktivitas WHERE tipe_aktivitas = 'REGISTRASI'")->fetchColumn();

function getBadgeTipe($tipe) {
    switch ($tipe) {
        case 'LOGIN':
            return ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-right-to-bracket'];
        case 'LOGOUT':
            return ['badge' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-right-from-bracket'];
        case 'REGISTRASI':
            return ['badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'icon' => 'fa-user-plus'];
        case 'UPDATE_ALAT':
            return ['badge' => 'bg-amber-50 text-amber-800 border-amber-200', 'icon' => 'fa-pen-to-square'];
        case 'TAMBAH_ALAT':
            return ['badge' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fa-plus-circle'];
        case 'HAPUS_ALAT':
            return ['badge' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'fa-trash-can'];
        default:
            return ['badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'fa-circle-info'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas & Riwayat Audit - MEDITRONIK RS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased flex flex-col font-sans">

    <!-- TOP NAVBAR -->
    <nav class="bg-gradient-to-r from-medical-950 via-medical-900 to-medical-800 text-white sticky top-0 z-40 shadow-xl border-b border-blue-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3 sm:gap-4">
                    <a href="index.php" class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-500 to-medical-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30 ring-2 ring-white/20">
                        <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-xl font-extrabold tracking-tight text-white">Log Aktivitas & Audit Trail</span>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-cyan-400/20 text-cyan-300 border border-cyan-400/30">Keamanan RS</span>
                        </div>
                        <p class="text-[10px] sm:text-xs text-blue-200/80 hidden sm:block">Pelacakan Siapa yang Masuk, Mendaftar, dan Memperbarui Data Alat</p>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex items-center gap-2">
                    <a href="index.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-all">
                        <i class="fa-solid fa-laptop-medical mr-1"></i> Inventaris Alat
                    </a>
                    <a href="users.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-all">
                        <i class="fa-solid fa-users mr-1"></i> Data Pengguna
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN BODY -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

        <!-- STAT METRICS LOG -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-xs text-slate-500 font-bold uppercase">Total Aktivitas</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?php echo $statTotal; ?></div>
                <div class="text-[11px] text-slate-400 mt-0.5">Seluruh jejak audit tercatat</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100">
                <div class="text-xs text-emerald-700 font-bold uppercase">Aktivitas Masuk (Login)</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1"><?php echo $statLogin; ?></div>
                <div class="text-[11px] text-emerald-600/70 mt-0.5">Sesi login pengguna aktif</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-amber-100">
                <div class="text-xs text-amber-700 font-bold uppercase">Pembaruan Data Alat</div>
                <div class="text-2xl font-extrabold text-amber-600 mt-1"><?php echo $statUpdate; ?></div>
                <div class="text-[11px] text-amber-700/70 mt-0.5">Edit spesifikasi & kalibrasi</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-cyan-100">
                <div class="text-xs text-cyan-700 font-bold uppercase">Registrasi Akun Baru</div>
                <div class="text-2xl font-extrabold text-cyan-600 mt-1"><?php echo $statReg; ?></div>
                <div class="text-[11px] text-cyan-700/70 mt-0.5">Pendaftaran staf & teknisi</div>
            </div>
        </div>

        <!-- FILTER TOOLBAR -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-list-ul text-medical-600"></i>
                            <span>Rekam Jejak Aktivitas Sistem (Audit Trail)</span>
                        </h3>
                        <p class="text-xs text-slate-500">Mencatat riwayat login, registrasi, dan perubahan data alat secara kronologis</p>
                    </div>
                </div>

                <form method="GET" action="log_aktivitas.php" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama pengguna, deskripsi, IP..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 bg-white">
                    </div>

                    <div>
                        <select name="tipe" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 bg-white">
                            <option value="">Semua Jenis Aktivitas</option>
                            <option value="LOGIN" <?php echo $filter_tipe === 'LOGIN' ? 'selected' : ''; ?>>LOGIN (Pengguna Masuk)</option>
                            <option value="REGISTRASI" <?php echo $filter_tipe === 'REGISTRASI' ? 'selected' : ''; ?>>REGISTRASI (Akun Baru)</option>
                            <option value="UPDATE_ALAT" <?php echo $filter_tipe === 'UPDATE_ALAT' ? 'selected' : ''; ?>>UPDATE_ALAT (Perubahan Data)</option>
                            <option value="TAMBAH_ALAT" <?php echo $filter_tipe === 'TAMBAH_ALAT' ? 'selected' : ''; ?>>TAMBAH_ALAT (Alat Baru)</option>
                            <option value="HAPUS_ALAT" <?php echo $filter_tipe === 'HAPUS_ALAT' ? 'selected' : ''; ?>>HAPUS_ALAT (Data Dihapus)</option>
                            <option value="LOGOUT" <?php echo $filter_tipe === 'LOGOUT' ? 'selected' : ''; ?>>LOGOUT (Pengguna Keluar)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 px-3 bg-medical-700 hover:bg-medical-800 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-filter"></i>
                            <span>Terapkan</span>
                        </button>
                        <?php if ($search || $filter_tipe): ?>
                            <a href="log_aktivitas.php" class="p-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- TABEL LOG -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-44">Waktu & Tanggal</th>
                            <th class="py-3 px-4 w-48">Pengguna</th>
                            <th class="py-3 px-4 w-36 text-center">Tindakan</th>
                            <th class="py-3 px-4">Deskripsi Rincian Aktivitas</th>
                            <th class="py-3 px-4 w-28 text-center">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-400">
                                    Belum ada catatan aktivitas yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): 
                                $b = getBadgeTipe($l['tipe_aktivitas']);
                                $time = strtotime($l['created_at']);
                            ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                        <div class="font-bold text-slate-800"><?php echo date('d M Y', $time); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo date('H:i:s', $time); ?> WIB</div>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($l['nama_pengguna'] ?: 'Pengguna'); ?></div>
                                        <div class="text-[10px] text-medical-700 font-mono">@<?php echo htmlspecialchars($l['username'] ?: 'sistem'); ?></div>
                                    </td>

                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo $b['badge']; ?>">
                                            <i class="fa-solid <?php echo $b['icon']; ?>"></i>
                                            <span><?php echo htmlspecialchars($l['tipe_aktivitas']); ?></span>
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-slate-700 leading-relaxed font-medium">
                                        <?php echo htmlspecialchars($l['deskripsi']); ?>
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono text-[11px] text-slate-500">
                                        <span class="px-2 py-0.5 rounded bg-slate-100"><?php echo htmlspecialchars($l['ip_address'] ?: '127.0.0.1'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                <span>Menampilkan <strong><?php echo count($logs); ?></strong> entri aktivitas terbaru</span>
                <span>Audit Trail Keamanan & Kelaikan Medis</span>
            </div>
        </div>

    </main>

</body>
</html>
