<?php
/**
 * logout.php - SERVER A Pemroses Logout
 * Memberitahu Server B melalui Web Service lalu menghapus session di Server A
 */
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/client_api.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
}

if (!empty($_SESSION['id_pengguna'])) {
    call_api('api_auth.php', 'POST', [
        'action'      => 'logout',
        'id_pengguna' => (int)$_SESSION['id_pengguna']
    ]);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header("Location: login.php");
exit;
?>
