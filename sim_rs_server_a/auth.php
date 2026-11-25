<?php
/**
 * auth.php - SERVER A Pemroses Login
 * Mengirim kredensial pengguna ke Web Service Server B (api_auth.php)
 */
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/client_api.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header("Location: login.php");
    exit;
}

csrf_verify();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header("Location: login.php");
    exit;
}

// Panggil Web Service Autentikasi pada Server B
$response = call_api('api_auth.php', 'POST', [
    'action'   => 'login',
    'username' => $username,
    'password' => $password
]);

if (empty($response['status']) || empty($response['data'])) {
    $_SESSION['login_error'] = $response['message'] ?? 'Gagal melakukan autentikasi ke Server B.';
    header("Location: login.php");
    exit;
}

$user = $response['data'];
session_regenerate_id(true);

$_SESSION['login']           = true;
$_SESSION['id_pengguna']     = (int)$user['id_pengguna'];
$_SESSION['id_role']         = (int)$user['id_role'];
$_SESSION['kode_role']       = $user['kode_role'];
$_SESSION['nama_role']       = $user['nama_role'];
$_SESSION['id_poliklinik']   = !empty($user['id_poliklinik']) ? (int)$user['id_poliklinik'] : null;
$_SESSION['nama_poliklinik'] = $user['nama_poliklinik'] ?? null;
$_SESSION['username']        = $user['username'];
$_SESSION['nama_lengkap']    = $user['nama_lengkap'];
$_SESSION['email']           = $user['email'] ?? '';
$_SESSION['id_dokter']       = !empty($user['id_dokter']) ? (int)$user['id_dokter'] : null;
$_SESSION['id_pasien']       = !empty($user['id_pasien']) ? (int)$user['id_pasien'] : null;

header("Location: dashboard.php");
exit;
?>
