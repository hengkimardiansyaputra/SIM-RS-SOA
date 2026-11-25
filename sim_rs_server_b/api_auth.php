<?php
/**
 * api_auth.php - SERVER B Web Service Autentikasi (Login & Logout)
 * Melayani permintaan autentikasi dari Server A berbasis RBAC
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? 'login');

if ($method === 'GET') {
    json_response([
        'status'   => true,
        'endpoint' => 'api_auth.php',
        'message'  => 'Web Service Autentikasi SIMRS SOA siap menerima request POST (action: login / logout).'
    ]);
}

if ($action === 'logout') {
    $id_pengguna = isset($body['id_pengguna']) ? (int)$body['id_pengguna'] : null;
    if ($id_pengguna) {
        catat_audit_log($koneksi, $id_pengguna, 'pengguna', (string)$id_pengguna, 'LOGOUT', 'Logout pengguna melalui Web Service');
    }
    json_response([
        'status'  => true,
        'message' => 'Logout berhasil dicatat pada Server B.'
    ]);
}

$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_response([
        'status'  => false,
        'message' => 'Username dan password wajib diisi.'
    ], 400);
}

$sql = "SELECT
            p.id_pengguna,
            p.id_role,
            p.id_poliklinik,
            p.username,
            p.password_hash,
            p.nama_lengkap,
            p.email,
            p.no_hp,
            p.status_aktif,
            r.kode_role,
            r.nama_role,
            pl.kode_poliklinik,
            pl.nama_poliklinik,
            d.id_dokter,
            d.nip_dokter,
            d.spesialisasi,
            ps.id_pasien,
            ps.no_rm
        FROM pengguna p
        INNER JOIN roles r ON p.id_role = r.id_role
        LEFT JOIN poliklinik pl ON p.id_poliklinik = pl.id_poliklinik
        LEFT JOIN dokter d ON d.id_pengguna = p.id_pengguna
        LEFT JOIN pasien ps ON ps.id_pengguna = p.id_pengguna
        WHERE p.username = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    json_response(['status' => false, 'message' => 'Kesalahan query pada Server B.'], 500);
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || $user['status_aktif'] !== 'Aktif' || !password_verify($password, $user['password_hash'])) {
    json_response([
        'status'  => false,
        'message' => 'Username atau password salah, atau akun tidak aktif.'
    ], 401);
}

// Bila dokter/pasien memiliki poliklinik di tabel dokter/pasien tetapi kolom id_poliklinik di pengguna kosong, sinkronkan
if (empty($user['id_poliklinik']) && !empty($user['id_dokter'])) {
    $resPoli = mysqli_query($koneksi, "SELECT d.id_poliklinik, pl.nama_poliklinik FROM dokter d INNER JOIN poliklinik pl ON d.id_poliklinik = pl.id_poliklinik WHERE d.id_dokter = " . (int)$user['id_dokter']);
    if ($resPoli && ($rowPoli = mysqli_fetch_assoc($resPoli))) {
        $user['id_poliklinik']   = (int)$rowPoli['id_poliklinik'];
        $user['nama_poliklinik'] = $rowPoli['nama_poliklinik'];
    }
}

// Update waktu login terakhir
$idPengguna = (int)$user['id_pengguna'];
$upd = mysqli_prepare($koneksi, "UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = ?");
if ($upd) {
    mysqli_stmt_bind_param($upd, "i", $idPengguna);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);
}

catat_audit_log(
    $koneksi,
    $idPengguna,
    'pengguna',
    (string)$idPengguna,
    'LOGIN',
    'Login berhasil via Web Service (' . $user['kode_role'] . ')'
);

unset($user['password_hash']);

json_response([
    'status'  => true,
    'message' => 'Autentikasi berhasil.',
    'data'    => $user
]);
?>
