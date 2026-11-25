<?php
/**
 * api_laporan.php - SERVER B Web Service Laporan Rumah Sakit & Audit Log
 * Menyediakan layanan laporan terintegrasi untuk Server A:
 * 1. Rekapitulasi Pasien & Dokter per Poliklinik
 * 2. Rekapitulasi Pasien Berdasarkan Asuransi vs Non-Asuransi (Umum)
 * 3. Laporan Detail Data Pasien dengan Filter (Poliklinik, Asuransi, Pembiayaan, Status)
 * 4. Riwayat Audit Log
 */
require_once __DIR__ . '/koneksi.php';

$jenis         = $_GET['jenis'] ?? 'semua';
$id_poliklinik = !empty($_GET['id_poliklinik']) ? (int)$_GET['id_poliklinik'] : 0;
$id_asuransi   = !empty($_GET['id_asuransi']) ? (int)$_GET['id_asuransi'] : 0;
$pembiayaan    = trim($_GET['pembiayaan'] ?? ''); // 'asuransi' atau 'umum'
$status_pasien = trim($_GET['status_pasien'] ?? '');

// 1. Rekapitulasi Per Poliklinik
$rekap_poliklinik = [];
$wherePoli = $id_poliklinik > 0 ? "WHERE pl.id_poliklinik = $id_poliklinik" : "";
$sqlPoli = "SELECT
                pl.id_poliklinik,
                pl.kode_poliklinik,
                pl.nama_poliklinik,
                pl.gedung_lantai,
                (SELECT COUNT(*) FROM dokter d WHERE d.id_poliklinik = pl.id_poliklinik AND d.status_dokter = 'Aktif') AS jumlah_dokter,
                COUNT(ps.id_pasien) AS total_pasien,
                SUM(CASE WHEN ps.id_asuransi IS NOT NULL THEN 1 ELSE 0 END) AS pasien_asuransi,
                SUM(CASE WHEN ps.id_asuransi IS NULL AND ps.id_pasien IS NOT NULL THEN 1 ELSE 0 END) AS pasien_umum,
                SUM(CASE WHEN ps.status_pasien = 'Rawat Jalan' THEN 1 ELSE 0 END) AS rawat_jalan,
                SUM(CASE WHEN ps.status_pasien = 'Rawat Inap' THEN 1 ELSE 0 END) AS rawat_inap,
                SUM(CASE WHEN ps.status_pasien = 'Selesai' THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN ps.status_pasien = 'Rujuk' THEN 1 ELSE 0 END) AS rujuk
            FROM poliklinik pl
            LEFT JOIN pasien ps ON ps.id_poliklinik = pl.id_poliklinik
            $wherePoli
            GROUP BY pl.id_poliklinik, pl.kode_poliklinik, pl.nama_poliklinik, pl.gedung_lantai
            ORDER BY pl.id_poliklinik ASC";
$resPoli = mysqli_query($koneksi, $sqlPoli);
if ($resPoli) {
    while ($r = mysqli_fetch_assoc($resPoli)) {
        $rekap_poliklinik[] = $r;
    }
}

// 2. Rekapitulasi Per Asuransi
$rekap_asuransi = [];
$sqlAsr = "SELECT
                a.id_asuransi,
                a.kode_asuransi,
                a.nama_asuransi,
                a.jenis_asuransi,
                a.persen_tanggungan,
                a.plafon_maksimal,
                COUNT(ps.id_pasien) AS jumlah_pasien
           FROM asuransi a
           LEFT JOIN pasien ps ON ps.id_asuransi = a.id_asuransi
           " . ($id_poliklinik > 0 ? "AND ps.id_poliklinik = $id_poliklinik" : "") . "
           GROUP BY a.id_asuransi, a.kode_asuransi, a.nama_asuransi, a.jenis_asuransi, a.persen_tanggungan, a.plafon_maksimal
           ORDER BY jumlah_pasien DESC, a.id_asuransi ASC";
$resAsr = mysqli_query($koneksi, $sqlAsr);
if ($resAsr) {
    while ($r = mysqli_fetch_assoc($resAsr)) {
        $rekap_asuransi[] = $r;
    }
}

// 3. Daftar Pasien Terfilter
$where = [];
$params = [];
$types = '';

if ($id_poliklinik > 0) {
    $where[]  = "id_poliklinik = ?";
    $params[] = $id_poliklinik;
    $types   .= "i";
}
if ($id_asuransi > 0) {
    $where[]  = "id_asuransi = ?";
    $params[] = $id_asuransi;
    $types   .= "i";
}
if ($pembiayaan === 'asuransi') {
    $where[] = "id_asuransi IS NOT NULL";
} elseif ($pembiayaan === 'umum') {
    $where[] = "id_asuransi IS NULL";
}
if ($status_pasien !== '') {
    $where[]  = "status_pasien = ?";
    $params[] = $status_pasien;
    $types   .= "s";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sqlPasien = "SELECT * FROM v_pasien_lengkap $whereSql ORDER BY tanggal_daftar DESC, id_pasien DESC";
$stmtP = mysqli_prepare($koneksi, $sqlPasien);
if ($types !== '') {
    mysqli_stmt_bind_param($stmtP, $types, ...$params);
}
mysqli_stmt_execute($stmtP);
$resP = mysqli_stmt_get_result($stmtP);
$laporan_pasien = [];
while ($r = mysqli_fetch_assoc($resP)) {
    $laporan_pasien[] = $r;
}
mysqli_stmt_close($stmtP);

// 4. Audit Log terbaru
$audit_logs = [];
$resAudit = mysqli_query(
    $koneksi,
    "SELECT a.*, p.username, p.nama_lengkap
     FROM audit_log a
     LEFT JOIN pengguna p ON a.id_pengguna = p.id_pengguna
     ORDER BY a.id_audit DESC
     LIMIT 50"
);
if ($resAudit) {
    while ($r = mysqli_fetch_assoc($resAudit)) {
        $audit_logs[] = $r;
    }
}

json_response([
    'status' => true,
    'data'   => [
        'rekap_poliklinik' => $rekap_poliklinik,
        'rekap_asuransi'   => $rekap_asuransi,
        'laporan_pasien'   => $laporan_pasien,
        'audit_logs'       => $audit_logs
    ]
]);
?>
