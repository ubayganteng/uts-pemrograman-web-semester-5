<?php
require_once __DIR__ . '/config.php';
require_login();

$stmt = $pdo->query("SELECT * FROM alat ORDER BY id ASC");
$rows = $stmt->fetchAll();

$filename = "Rekap_Alat_Elektromedis_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Tambahkan BOM agar karakter UTF-8 terbaca rapi di Microsoft Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header Kolom Excel
fputcsv($output, [
    'No',
    'Kode Alat / ASPAK',
    'Nama Alat Elektromedis',
    'Jenis / Kategori',
    'Merek Pabrikan',
    'Tipe / Model',
    'Nomor Seri (SN)',
    'Tahun Perolehan',
    'Lokasi Ruangan',
    'Kondisi Fisik',
    'Tingkat Risiko',
    'Daya Listrik',
    'Tgl Kalibrasi Terakhir',
    'Jadwal Kalibrasi Berikutnya',
    'Status Kalibrasi',
    'Lembaga Kalibrasi',
    'No Sertifikat',
    'Catatan Pemeliharaan'
]);

$no = 1;
foreach ($rows as $r) {
    fputcsv($output, [
        $no++,
        $r['kode_alat'] ?? '',
        $r['nama_alat'] ?? '',
        $r['jenis_alat'] ?? '',
        $r['merek'] ?? '',
        $r['tipe_model'] ?? '',
        $r['nomor_seri'] ?? '',
        $r['tahun'] ?? '',
        $r['lokasi'] ?? '',
        $r['kondisi'] ?? '',
        $r['tingkat_risiko'] ?? '',
        $r['daya_listrik'] ?? '',
        $r['tgl_kalibrasi_terakhir'] ?? '',
        $r['tgl_kalibrasi_berikutnya'] ?? '',
        $r['status_kalibrasi'] ?? '',
        $r['lembaga_kalibrasi'] ?? '',
        $r['no_sertifikat'] ?? '',
        $r['keterangan'] ?? ''
    ]);
}

fclose($output);
exit;
