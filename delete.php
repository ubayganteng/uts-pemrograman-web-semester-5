<?php
require_once __DIR__ . '/config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Ambil nama alat untuk pesan notifikasi
    $stmtFind = $pdo->prepare("SELECT nama_alat FROM alat WHERE id = ?");
    $stmtFind->execute([$id]);
    $alat = $stmtFind->fetch();

    if ($alat) {
        $stmtDel = $pdo->prepare("DELETE FROM alat WHERE id = ?");
        $stmtDel->execute([$id]);

        // Catat log aktivitas penghapusan
        catat_log($pdo, 'HAPUS_ALAT', "Menghapus data alat: '" . $alat['nama_alat'] . "' (ID: #$id).");

        $_SESSION['flash_success'] = "Data alat '" . htmlspecialchars($alat['nama_alat']) . "' berhasil dihapus dari sistem.";
    } else {
        $_SESSION['flash_error'] = "Data alat tidak ditemukan.";
    }
} else {
    $_SESSION['flash_error'] = "ID alat tidak valid.";
}

header("Location: index.php");
exit;
