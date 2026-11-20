<?php
require_once __DIR__ . '/config.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error_login = '';
$success_msg = '';
$error_reg = '';
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

if (isset($_GET['registered'])) {
    $success_msg = 'Akun Anda berhasil didaftarkan! Silakan masuk menggunakan username dan kata sandi Anda.';
    $active_tab = 'login';
}

if (isset($_GET['logged_out'])) {
    $success_msg = 'Anda telah berhasil keluar dari sistem.';
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_login = 'Silakan isi username dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Catat log aktivitas masuk (login)
            catat_log($pdo, 'LOGIN', "Pengguna '" . $user['username'] . "' (" . $user['nama_lengkap'] . " - " . $user['role'] . ") berhasil masuk ke sistem.", $user['username'], $user['nama_lengkap']);

            header("Location: index.php");
            exit;
        } else {
            $error_login = 'Username atau kata sandi yang Anda masukkan salah!';
        }
    }
}

// Proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'Teknisi Elektromedis');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $active_tab = 'register';

    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
        $error_reg = 'Semua kolom formulir pendaftaran wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_reg = 'Format alamat email tidak valid.';
    } elseif ($password !== $password_confirm) {
        $error_reg = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error_reg = 'Kata sandi minimal harus 6 karakter.';
    } else {
        // Cek duplikasi
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([$username, $email]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error_reg = 'Username atau email sudah terdaftar sebelumnya.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO users (nama_lengkap, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$nama_lengkap, $username, $email, $hashed, $role]);

            // Catat log aktivitas pendaftaran (registrasi)
            catat_log($pdo, 'REGISTRASI', "Pengguna baru '$username' ($nama_lengkap) berhasil mendaftar akun sebagai $role.", $username, $nama_lengkap);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Registrasi - SIM-Elektromedis Rumah Sakit</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
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
        .medical-mesh-bg {
            background-color: #061933;
            background-image: 
                radial-gradient(at 0% 0%, rgba(14, 116, 144, 0.45) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(37, 99, 235, 0.5) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(2, 132, 199, 0.35) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(30, 58, 138, 0.6) 0px, transparent 50%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(3, 33, 74, 0.35);
        }
        .pulse-wave {
            stroke-dasharray: 600;
            stroke-dashoffset: 600;
            animation: dash 3.5s linear infinite;
        }
        @keyframes dash {
            to {
                stroke-dashoffset: 0;
            }
        }
    </style>
</head>
<body class="medical-mesh-bg min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-800 antialiased relative overflow-x-hidden">

    <!-- Background Animated Grid Decor -->
    <div class="absolute inset-0 opacity-15 pointer-events-none" style="background-size: 32px 32px; background-image: linear-gradient(to right, rgba(255, 255, 255, 0.2) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.2) 1px, transparent 1px);"></div>

    <!-- Glowing Orbs -->
    <div class="absolute top-1/4 left-1/10 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/10 w-96 h-96 bg-blue-600/25 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-4xl relative z-10 my-6">

        <!-- Top Hospital Brand Badge -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-white/10 border border-white/20 text-white backdrop-blur-md shadow-lg mb-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-400"></span>
                </span>
                <span class="text-xs font-semibold tracking-wider uppercase text-cyan-200">Sistem Informasi Elektromedis & Kalibrasi RS</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight flex items-center justify-center gap-3">
                <i class="fa-solid fa-heart-pulse text-cyan-400"></i>
                <span>MEDITRONIK</span>
                <span class="text-xs bg-cyan-500/30 text-cyan-200 border border-cyan-400/30 font-semibold px-2.5 py-1 rounded-md uppercase tracking-wider">v2.5</span>
            </h1>
            <p class="text-blue-200 text-sm mt-1 max-w-md mx-auto">Pengawasan Kelaikan, Jadwal Kalibrasi & Manajemen Pemeliharaan Alat Kesehatan (Permenkes RI No. 54 / 2015)</p>
        </div>

        <!-- Main Card Container -->
        <div class="glass-card rounded-3xl overflow-hidden shadow-2xl transition-all duration-300">
            <div class="grid grid-cols-1 lg:grid-cols-12">
                
                <!-- Left Info Banner (Deep Blue Accent) -->
                <div class="lg:col-span-5 bg-gradient-to-br from-medical-900 via-medical-800 to-medical-950 p-8 text-white flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-cyan-400/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div>
                        <div class="flex items-center gap-2 mb-6 text-cyan-400">
                            <i class="fa-solid fa-shield-halved text-2xl"></i>
                            <span class="font-bold tracking-wide text-sm text-cyan-300 uppercase">Instalasi IPSRS Medis</span>
                        </div>
                        <h2 class="text-2xl font-bold leading-snug mb-3">Manajemen Akreditasi & Kelaikan Alat Elektromedis</h2>
                        <p class="text-blue-200/80 text-xs sm:text-sm leading-relaxed mb-6">
                            Pastikan seluruh instrumen medis ICU, IGD, Kamar Operasi, dan Radiologi memiliki sertifikat kalibrasi aktif guna menjamin keselamatan pasien (Patient Safety).
                        </p>

                        <!-- Key Pillars -->
                        <div class="space-y-3.5 mb-6">
                            <div class="flex items-center gap-3 text-xs bg-white/5 p-2.5 rounded-xl border border-white/10">
                                <i class="fa-solid fa-calendar-check text-cyan-400 text-base"></i>
                                <span>Jadwal Otomatis Jatuh Tempo Kalibrasi</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs bg-white/5 p-2.5 rounded-xl border border-white/10">
                                <i class="fa-solid fa-print text-cyan-400 text-base"></i>
                                <span>Cetak Stiker Laik Pakai Standar BPFK</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs bg-white/5 p-2.5 rounded-xl border border-white/10">
                                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-base"></i>
                                <span>Peringatan Dini Kadaluarsa Kalibrasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Fast Login Buttons -->
                    <div class="mt-6 pt-5 border-t border-white/15">
                        <p class="text-[11px] font-semibold text-cyan-300 uppercase tracking-wider mb-2.5">
                            <i class="fa-solid fa-bolt text-amber-300 mr-1"></i> Klik untuk Akun Demo:
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="quickFill('admin', 'admin123')" class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-cyan-500 hover:text-white transition-all text-left border border-white/15 flex items-center justify-between">
                                <span><strong>Admin</strong> IPSRS</span>
                                <i class="fa-solid fa-arrow-right text-[10px] opacity-70"></i>
                            </button>
                            <button type="button" onclick="quickFill('teknisi', 'teknisi123')" class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-cyan-500 hover:text-white transition-all text-left border border-white/15 flex items-center justify-between">
                                <span><strong>Teknisi</strong> Medis</span>
                                <i class="fa-solid fa-arrow-right text-[10px] opacity-70"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Form Area (Login & Register Tabs) -->
                <div class="lg:col-span-7 p-7 sm:p-9 bg-white">
                    
                    <!-- Tabs Navigation -->
                    <div class="flex border-b border-slate-200 mb-6">
                        <button id="tabBtnLogin" onclick="switchTab('login')" class="flex-1 pb-3 text-sm font-semibold text-center border-b-2 transition-all flex items-center justify-center gap-2 <?php echo $active_tab === 'login' ? 'border-medical-600 text-medical-700' : 'border-transparent text-slate-400 hover:text-slate-600'; ?>">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Masuk ke Akun</span>
                        </button>
                        <button id="tabBtnRegister" onclick="switchTab('register')" class="flex-1 pb-3 text-sm font-semibold text-center border-b-2 transition-all flex items-center justify-center gap-2 <?php echo $active_tab === 'register' ? 'border-medical-600 text-medical-700' : 'border-transparent text-slate-400 hover:text-slate-600'; ?>">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Registrasi Akun</span>
                        </button>
                    </div>

                    <!-- Alerts Notification -->
                    <?php if (!empty($success_msg)): ?>
                        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-base"></i>
                            <span><?php echo htmlspecialchars($success_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_login)): ?>
                        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5 text-base"></i>
                            <span><?php echo htmlspecialchars($error_login); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_reg)): ?>
                        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5 text-base"></i>
                            <span><?php echo htmlspecialchars($error_reg); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- TAB 1: FORM LOGIN -->
                    <div id="panelLogin" class="<?php echo $active_tab === 'login' ? 'block' : 'hidden'; ?>">
                        <form method="POST" action="login.php" class="space-y-4">
                            <input type="hidden" name="action" value="login">

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Username atau Email</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <i class="fa-regular fa-user"></i>
                                    </span>
                                    <input type="text" id="loginUsername" name="username" required placeholder="Masukkan username atau email" value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>" class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                                    <span class="text-[11px] text-medical-600 font-medium cursor-pointer hover:underline" onclick="quickFill('admin', 'admin123')">Lupa Sandi? (Pakai Demo)</span>
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                    <input type="password" id="loginPassword" name="password" required placeholder="Masukkan kata sandi" value="admin123" class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                    <button type="button" onclick="togglePass('loginPassword', 'eyeIconLogin')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                                        <i id="eyeIconLogin" class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center text-xs text-slate-600 select-none cursor-pointer">
                                    <input type="checkbox" name="remember" class="rounded text-medical-600 focus:ring-medical-500 border-slate-300 h-4 w-4 mr-2">
                                    Ingat saya di perangkat ini
                                </label>
                            </div>

                            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-medical-600 to-medical-700 hover:from-medical-700 hover:to-medical-800 text-white font-semibold rounded-xl shadow-lg shadow-medical-600/30 hover:shadow-medical-600/50 transition-all duration-200 flex items-center justify-center gap-2 group text-sm">
                                <span>Masuk ke Dashboard</span>
                                <i class="fa-solid fa-arrow-right-to-bracket transition-transform group-hover:translate-x-1"></i>
                            </button>
                        </form>

                        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                            <p class="text-xs text-slate-500">
                                Belum memiliki hak akses akun?
                                <button type="button" onclick="switchTab('register')" class="text-medical-600 font-semibold hover:underline ml-1">Daftar Akun Baru Sekarang</button>
                            </p>
                        </div>
                    </div>

                    <!-- TAB 2: FORM REGISTRASI -->
                    <div id="panelRegister" class="<?php echo $active_tab === 'register' ? 'block' : 'hidden'; ?>">
                        <form method="POST" action="login.php" class="space-y-3.5">
                            <input type="hidden" name="action" value="register">

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap & Gelar</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <i class="fa-regular fa-id-badge"></i>
                                    </span>
                                    <input type="text" name="nama_lengkap" required placeholder="Contoh: Danu Wiranto, S.Tr.TEM" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <i class="fa-solid fa-at"></i>
                                        </span>
                                        <input type="text" name="username" required placeholder="Username unik" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Peran / Jabatan</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <i class="fa-solid fa-user-tag"></i>
                                        </span>
                                        <select name="role" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                            <option value="Teknisi Elektromedis">Teknisi Elektromedis</option>
                                            <option value="Admin IPSRS / Elektromedis">Admin IPSRS / Elektromedis</option>
                                            <option value="Kepala Ruangan">Kepala Ruangan / Unit RS</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Email Rumah Sakit</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <i class="fa-regular fa-envelope"></i>
                                    </span>
                                    <input type="email" name="email" required placeholder="nama@rumahsakit.co.id" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kata Sandi</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <i class="fa-solid fa-key"></i>
                                        </span>
                                        <input type="password" id="regPass" name="password" required minlength="6" placeholder="Min. 6 karakter" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Ulangi Sandi</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <i class="fa-solid fa-check-double"></i>
                                        </span>
                                        <input type="password" id="regPassConfirm" name="password_confirm" required minlength="6" placeholder="Konfirmasi kata sandi" class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-medical-500 focus:border-medical-500 transition-all bg-slate-50/50">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-gradient-to-r from-cyan-600 to-medical-600 hover:from-cyan-700 hover:to-medical-700 text-white font-semibold rounded-xl shadow-lg shadow-cyan-600/30 hover:shadow-cyan-600/50 transition-all duration-200 flex items-center justify-center gap-2 group text-sm mt-2">
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Daftarkan Akun Baru</span>
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                            <p class="text-xs text-slate-500">
                                Sudah punya akun terdaftar?
                                <button type="button" onclick="switchTab('login')" class="text-medical-600 font-semibold hover:underline ml-1">Masuk Sekarang</button>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="text-center mt-6 text-xs text-blue-200/60 flex items-center justify-center gap-2">
            <i class="fa-solid fa-hospital"></i>
            <span>Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS) & Divisi Elektromedis &copy; 2026</span>
        </div>
    </div>

    <!-- JavaScript Interactive Handlers -->
    <script>
        function switchTab(tab) {
            const panelLogin = document.getElementById('panelLogin');
            const panelRegister = document.getElementById('panelRegister');
            const tabBtnLogin = document.getElementById('tabBtnLogin');
            const tabBtnRegister = document.getElementById('tabBtnRegister');

            if (tab === 'login') {
                panelLogin.classList.remove('hidden');
                panelRegister.classList.add('hidden');
                tabBtnLogin.className = 'flex-1 pb-3 text-sm font-semibold text-center border-b-2 border-medical-600 text-medical-700 transition-all flex items-center justify-center gap-2';
                tabBtnRegister.className = 'flex-1 pb-3 text-sm font-semibold text-center border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center gap-2';
            } else {
                panelLogin.classList.add('hidden');
                panelRegister.classList.remove('hidden');
                tabBtnRegister.className = 'flex-1 pb-3 text-sm font-semibold text-center border-b-2 border-medical-600 text-medical-700 transition-all flex items-center justify-center gap-2';
                tabBtnLogin.className = 'flex-1 pb-3 text-sm font-semibold text-center border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center gap-2';
            }
        }

        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        }

        function quickFill(user, pass) {
            switchTab('login');
            document.getElementById('loginUsername').value = user;
            document.getElementById('loginPassword').value = pass;
        }
    </script>
</body>
</html>
