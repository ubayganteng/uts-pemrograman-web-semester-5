<?php
require_once __DIR__ . '/config.php';
require_login();

$user = current_user();

$search = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nama_lengkap LIKE ? OR username LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

if (!empty($filter_role)) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$userList = $stmt->fetchAll();

$statTotalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$statAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role LIKE '%Admin%'")->fetchColumn();
$statTeknisi = $pdo->query("SELECT COUNT(*) FROM users WHERE role LIKE '%Teknisi%'")->fetchColumn();
$statRuangan = $pdo->query("SELECT COUNT(*) FROM users WHERE role LIKE '%Ruangan%' OR role LIKE '%Kepala%'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna Terdaftar - MEDITRONIK RS</title>
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
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-xl font-extrabold tracking-tight text-white">Manajemen Akun Pengguna</span>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-cyan-400/20 text-cyan-300 border border-cyan-400/30">SIM-RS</span>
                        </div>
                        <p class="text-[10px] sm:text-xs text-blue-200/80 hidden sm:block">Daftar Akun yang Telah Registrasi & Memiliki Hak Akses</p>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex items-center gap-2">
                    <a href="index.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-all">
                        <i class="fa-solid fa-laptop-medical mr-1"></i> Inventaris Alat
                    </a>
                    <a href="log_aktivitas.php" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-all">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Log Aktivitas
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN BODY -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

        <!-- STAT METRICS USERS -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-xs text-slate-500 font-bold uppercase">Total Akun Terdaftar</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?php echo $statTotalUsers; ?></div>
                <div class="text-[11px] text-slate-400 mt-0.5">Memiliki akses ke sistem</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-blue-100">
                <div class="text-xs text-medical-700 font-bold uppercase">Admin IPSRS</div>
                <div class="text-2xl font-extrabold text-medical-600 mt-1"><?php echo $statAdmin; ?></div>
                <div class="text-[11px] text-medical-600/70 mt-0.5">Hak akses administrator</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-cyan-100">
                <div class="text-xs text-cyan-700 font-bold uppercase">Teknisi Elektromedis</div>
                <div class="text-2xl font-extrabold text-cyan-600 mt-1"><?php echo $statTeknisi; ?></div>
                <div class="text-[11px] text-cyan-700/70 mt-0.5">Pelaksana pemeliharaan</div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100">
                <div class="text-xs text-emerald-700 font-bold uppercase">Kepala Ruangan / Unit</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1"><?php echo $statRuangan; ?></div>
                <div class="text-[11px] text-emerald-600/70 mt-0.5">Pengawas unit pelayanan</div>
            </div>
        </div>

        <!-- TABLE USERS -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-address-book text-medical-600"></i>
                            <span>Daftar Akun Pengguna Terdaftar</span>
                        </h3>
                        <p class="text-xs text-slate-500">Mencakup nama lengkap, username, email, tanggal registrasi dan peran</p>
                    </div>

                    <a href="login.php?tab=register" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-medical-700 hover:bg-medical-800 text-white text-xs font-semibold shadow-sm transition-all">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Daftarkan Pengguna Baru</span>
                    </a>
                </div>

                <form method="GET" action="users.php" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, username, email..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 bg-white">
                    </div>

                    <div>
                        <select name="role" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 bg-white">
                            <option value="">Semua Peran / Jabatan</option>
                            <option value="Admin IPSRS / Elektromedis" <?php echo $filter_role === 'Admin IPSRS / Elektromedis' ? 'selected' : ''; ?>>Admin IPSRS / Elektromedis</option>
                            <option value="Teknisi Elektromedis" <?php echo $filter_role === 'Teknisi Elektromedis' ? 'selected' : ''; ?>>Teknisi Elektromedis</option>
                            <option value="Kepala Ruangan" <?php echo $filter_role === 'Kepala Ruangan' ? 'selected' : ''; ?>>Kepala Ruangan</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 px-3 bg-medical-700 hover:bg-medical-800 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-filter"></i>
                            <span>Terapkan</span>
                        </button>
                        <?php if ($search || $filter_role): ?>
                            <a href="users.php" class="p-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- TABEL DATA PENGGUNA -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Lengkap & Profil</th>
                            <th class="py-3 px-4">Username & Email</th>
                            <th class="py-3 px-4">Peran / Jabatan</th>
                            <th class="py-3 px-4">Waktu Registrasi</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (empty($userList)): ?>
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    Tidak ada pengguna yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($userList as $u): 
                                $isAdmin = strpos($u['role'], 'Admin') !== false;
                            ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400"><?php echo $no++; ?></td>
                                    
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-medical-700 text-white flex items-center justify-center font-bold text-xs uppercase shadow-inner">
                                                <?php echo strtoupper(substr($u['username'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($u['nama_lengkap']); ?></div>
                                                <div class="text-[10px] text-slate-400">User ID: #<?php echo $u['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-semibold text-medical-800">@<?php echo htmlspecialchars($u['username']); ?></div>
                                        <div class="text-slate-500 text-[11px]"><?php echo htmlspecialchars($u['email']); ?></div>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo $isAdmin ? 'bg-blue-50 text-medical-800 border-medical-200' : 'bg-cyan-50 text-cyan-800 border-cyan-200'; ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                        <div class="font-bold text-slate-800"><?php echo date('d M Y', strtotime($u['created_at'])); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo date('H:i', strtotime($u['created_at'])); ?> WIB</div>
                                    </td>

                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                <span>Total <strong><?php echo count($userList); ?></strong> akun pengguna terdata</span>
                <span>Manajemen Akses Terotorisasi</span>
            </div>
        </div>

    </main>

</body>
</html>
