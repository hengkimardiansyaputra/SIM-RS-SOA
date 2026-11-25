<?php
/**
 * login.php - SERVER A Halaman Login Pengguna
 */
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/client_api.php';

if (!empty($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-info">
            <div class="brand">
                <div class="brand-logo">RS</div>
                <div>
                    <strong>SIMRS Pasien SOA</strong>
                    <small>Server A (Client UI) ⇄ Server B (Web Service)</small>
                </div>
            </div>

            <h1>Autentikasi Berbasis Web Service</h1>
            <p>
                Setiap permintaan login dari <strong>Server A</strong> diverifikasi oleh
                endpoint <code>api_auth.php</code> pada <strong>Server B</strong> untuk menentukan
                hak akses <em>Role-Based Access Control (RBAC)</em>.
            </p>

            <div class="demo-box">
                <strong>Akun Uji Coba RBAC (Password semua akun: <code>password</code>)</strong>
                <table>
                    <thead>
                        <tr><th>Role RBAC</th><th>Username</th><th>Keterangan Scope</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>ADMIN</td><td><code>admin</code></td><td>Akses Penuh Seluruh RS</td></tr>
                        <tr><td>PETUGAS_POLI</td><td><code>petugas.umum</code></td><td>Scope Poliklinik Umum</td></tr>
                        <tr><td>DOKTER</td><td><code>dr.andi</code></td><td>Dokter sebagai Pengguna</td></tr>
                        <tr><td>DIREKTUR</td><td><code>direktur</code></td><td>Eksekutif &amp; Laporan RS</td></tr>
                        <tr><td>PASIEN</td><td><code>RM-2026-0001</code></td><td>Pasien sebagai Pengguna</td></tr>
                    </tbody>
                </table>
            </div>

            <a href="index.php" class="back-link">← Kembali ke Landing Page</a>
        </div>

        <div class="login-card">
            <h2>Login SIMRS</h2>
            <p class="muted">Masukkan username dan password akun Anda.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form action="auth.php" method="post" autocomplete="off" style="margin-top:20px;">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username / No. Rekam Medis</label>
                    <input type="text" id="username" name="username" placeholder="Contoh: admin, dr.andi, RM-2026-0001" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:10px;">
                    Masuk ke Sistem
                </button>
            </form>
        </div>
    </div>
</body>
</html>
