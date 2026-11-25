<?php
/**
 * auth_check.php - SERVER A
 * Middleware pemeriksaan autentikasi & Role-Based Access Control (RBAC)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/client_api.php';

// Dukungan auto-login cepat pada localhost untuk pengujian / demo role RBAC
if (!empty($_GET['demo_user']) && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    $respDemo = call_api('api_auth.php', 'POST', [
        'action'   => 'login',
        'username' => trim((string)$_GET['demo_user']),
        'password' => 'password'
    ]);
    if (!empty($respDemo['status']) && !empty($respDemo['data'])) {
        $u = $respDemo['data'];
        $_SESSION['login']           = true;
        $_SESSION['id_pengguna']     = (int)$u['id_pengguna'];
        $_SESSION['id_role']         = (int)$u['id_role'];
        $_SESSION['kode_role']       = $u['kode_role'];
        $_SESSION['nama_role']       = $u['nama_role'];
        $_SESSION['id_poliklinik']   = !empty($u['id_poliklinik']) ? (int)$u['id_poliklinik'] : null;
        $_SESSION['nama_poliklinik'] = $u['nama_poliklinik'] ?? null;
        $_SESSION['username']        = $u['username'];
        $_SESSION['nama_lengkap']    = $u['nama_lengkap'];
        $_SESSION['email']           = $u['email'] ?? '';
    }
}

if (empty($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

/**
 * Memastikan pengguna yang sedang login memiliki salah satu kode_role yang diizinkan
 */
function wajib_role(array $allowed_roles): void
{
    $current_role = $_SESSION['kode_role'] ?? '';
    if (!in_array($current_role, $allowed_roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 - Akses Ditolak (RBAC)</title>';
        echo '<link rel="stylesheet" href="style.css"></head><body style="padding:50px;text-align:center;">';
        echo '<div class="dashboard-panel" style="max-width:520px;margin:40px auto;padding:35px;">';
        echo '<h1 style="color:#b91c1c;margin-top:0;">403 — Akses Ditolak</h1>';
        echo '<p class="muted">Role Anda (<strong>' . e($_SESSION['nama_role'] ?? $current_role) . '</strong>) tidak memiliki hak akses (RBAC) untuk membuka modul ini.</p>';
        echo '<a href="dashboard.php" class="btn btn-primary" style="margin-top:15px;">Kembali ke Dashboard</a>';
        echo '</div></body></html>';
        exit;
    }
}
?>
