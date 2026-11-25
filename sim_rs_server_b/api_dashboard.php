<?php
/**
 * api_dashboard.php - SERVER B Web Service Statistik Dashboard & Profil RBAC
 */
require_once __DIR__ . '/koneksi.php';

$role          = strtoupper(trim($_GET['kode_role'] ?? 'ADMIN'));
$id_poliklinik = isset($_GET['id_poliklinik']) ? (int)$_GET['id_poliklinik'] : 0;
$id_pengguna   = isset($_GET['id_pengguna']) ? (int)$_GET['id_pengguna'] : 0;

$stats = [
    'total_pasien'            => 0,
    'total_pasien_asuransi'   => 0,
    'total_pasien_umum'       => 0,
    'total_dokter'            => 0,
    'total_poliklinik'        => 0,
    'total_asuransi'          => 0,
    'total_pengguna'          => 0,
    'label_scope'             => 'Seluruh Unit Rumah Sakit',
    'profil_pasien'           => null,
    'profil_dokter'           => null,
    'pasien_terbaru'          => []
];

// Hitung Poliklinik & Asuransi Aktif
$rPoli = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM poliklinik WHERE status_aktif = 'Aktif'");
if ($rPoli && ($row = mysqli_fetch_assoc($rPoli))) {
    $stats['total_poliklinik'] = (int)$row['total'];
}

$rAsr = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM asuransi WHERE status_aktif = 'Aktif'");
if ($rAsr && ($row = mysqli_fetch_assoc($rAsr))) {
    $stats['total_asuransi'] = (int)$row['total'];
}

$rUsr = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengguna WHERE status_aktif = 'Aktif'");
if ($rUsr && ($row = mysqli_fetch_assoc($rUsr))) {
    $stats['total_pengguna'] = (int)$row['total'];
}

if ($role === 'PASIEN' && $id_pengguna > 0) {
    $stats['label_scope'] = 'Portal Mandiri Pasien';
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM v_pasien_lengkap WHERE id_pengguna = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_pengguna);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $profil = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if ($profil) {
            $stats['profil_pasien']         = $profil;
            $stats['total_pasien']          = 1;
            $stats['total_pasien_asuransi'] = !empty($profil['id_asuransi']) ? 1 : 0;
            $stats['total_pasien_umum']     = empty($profil['id_asuransi']) ? 1 : 0;
        }
    }
} elseif (($role === 'PETUGAS_POLI' || $role === 'DOKTER') && $id_poliklinik > 0) {
    // Scope terbatas pada Poliklinik terkait
    $stmtPoli = mysqli_prepare($koneksi, "SELECT nama_poliklinik FROM poliklinik WHERE id_poliklinik = ?");
    if ($stmtPoli) {
        mysqli_stmt_bind_param($stmtPoli, "i", $id_poliklinik);
        mysqli_stmt_execute($stmtPoli);
        $resP = mysqli_stmt_get_result($stmtPoli);
        if ($rowP = mysqli_fetch_assoc($resP)) {
            $stats['label_scope'] = $rowP['nama_poliklinik'];
        }
        mysqli_stmt_close($stmtPoli);
    }

    $stmtStat = mysqli_prepare(
        $koneksi,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN id_asuransi IS NOT NULL THEN 1 ELSE 0 END) AS berasuransi,
            SUM(CASE WHEN id_asuransi IS NULL THEN 1 ELSE 0 END) AS umum
         FROM pasien
         WHERE id_poliklinik = ?"
    );
    if ($stmtStat) {
        mysqli_stmt_bind_param($stmtStat, "i", $id_poliklinik);
        mysqli_stmt_execute($stmtStat);
        $resS = mysqli_stmt_get_result($stmtStat);
        if ($rowS = mysqli_fetch_assoc($resS)) {
            $stats['total_pasien']          = (int)($rowS['total'] ?? 0);
            $stats['total_pasien_asuransi'] = (int)($rowS['berasuransi'] ?? 0);
            $stats['total_pasien_umum']     = (int)($rowS['umum'] ?? 0);
        }
        mysqli_stmt_close($stmtStat);
    }

    $stmtDok = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total FROM dokter WHERE id_poliklinik = ?");
    if ($stmtDok) {
        mysqli_stmt_bind_param($stmtDok, "i", $id_poliklinik);
        mysqli_stmt_execute($stmtDok);
        $resD = mysqli_stmt_get_result($stmtDok);
        if ($rowD = mysqli_fetch_assoc($resD)) {
            $stats['total_dokter'] = (int)$rowD['total'];
        }
        mysqli_stmt_close($stmtDok);
    }

    if ($role === 'DOKTER' && $id_pengguna > 0) {
        $stmtPrDok = mysqli_prepare($koneksi, "SELECT * FROM v_dokter_lengkap WHERE id_pengguna = ? LIMIT 1");
        if ($stmtPrDok) {
            mysqli_stmt_bind_param($stmtPrDok, "i", $id_pengguna);
            mysqli_stmt_execute($stmtPrDok);
            $resPrD = mysqli_stmt_get_result($stmtPrDok);
            $stats['profil_dokter'] = mysqli_fetch_assoc($resPrD) ?: null;
            mysqli_stmt_close($stmtPrDok);
        }
    }

    $stmtList = mysqli_prepare($koneksi, "SELECT * FROM v_pasien_lengkap WHERE id_poliklinik = ? ORDER BY id_pasien DESC LIMIT 5");
    if ($stmtList) {
        mysqli_stmt_bind_param($stmtList, "i", $id_poliklinik);
        mysqli_stmt_execute($stmtList);
        $resL = mysqli_stmt_get_result($stmtList);
        while ($r = mysqli_fetch_assoc($resL)) {
            $stats['pasien_terbaru'][] = $r;
        }
        mysqli_stmt_close($stmtList);
    }
} else {
    // ADMIN / DIREKTUR: Seluruh Rumah Sakit
    $resS = mysqli_query(
        $koneksi,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN id_asuransi IS NOT NULL THEN 1 ELSE 0 END) AS berasuransi,
            SUM(CASE WHEN id_asuransi IS NULL THEN 1 ELSE 0 END) AS umum
         FROM pasien"
    );
    if ($resS && ($rowS = mysqli_fetch_assoc($resS))) {
        $stats['total_pasien']          = (int)($rowS['total'] ?? 0);
        $stats['total_pasien_asuransi'] = (int)($rowS['berasuransi'] ?? 0);
        $stats['total_pasien_umum']     = (int)($rowS['umum'] ?? 0);
    }

    $resD = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM dokter");
    if ($resD && ($rowD = mysqli_fetch_assoc($resD))) {
        $stats['total_dokter'] = (int)$rowD['total'];
    }

    $resL = mysqli_query($koneksi, "SELECT * FROM v_pasien_lengkap ORDER BY id_pasien DESC LIMIT 5");
    if ($resL) {
        while ($r = mysqli_fetch_assoc($resL)) {
            $stats['pasien_terbaru'][] = $r;
        }
    }
}

json_response([
    'status' => true,
    'data'   => $stats
]);
?>
