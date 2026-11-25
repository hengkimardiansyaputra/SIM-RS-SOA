<?php
/**
 * index.php - SERVER A Landing Page
 * Sistem Informasi Manajemen Pasien Rumah Sakit Berbasis SOA & RBAC
 */
session_start();
require_once __DIR__ . '/client_api.php';

if (!empty($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Ambil daftar poliklinik langsung dari Web Service Server B
$resPoli = call_api('api_poliklinik.php', 'GET');
$poliklinikList = ($resPoli['status'] ?? false) ? ($resPoli['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMRS SOA - Sistem Informasi Manajemen Pasien Rumah Sakit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing">
    <header class="landing-header">
        <div class="brand">
            <div class="brand-logo">RS</div>
            <div>
                <strong>SIMRS Pasien SOA</strong>
                <small>Rumah Sakit Pendidikan Universitas Muhammadiyah Bengkulu</small>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a class="btn btn-outline" href="#poliklinik">Poliklinik</a>
            <a class="btn btn-primary" href="login.php">Login Aplikasi</a>
        </div>
    </header>

    <main class="hero">
        <div class="hero-content">
            <span class="eyebrow">SERVICE-ORIENTED ARCHITECTURE (SOA)</span>
            <h1>Layanan Terpadu<br>Manajemen Pasien Rumah Sakit</h1>
            <p>
                Sistem informasi rumah sakit berbasis arsitektur terdistribusi:
                <strong>Server A</strong> menyajikan antarmuka pengguna (UI) berbasis
                <em>Role-Based Access Control (RBAC)</em>, sementara <strong>Server B</strong>
                melayani pengolahan basis data dan RESTful Web Service secara real-time.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="login.php">Masuk ke Portal SIMRS</a>
                <a class="btn btn-outline btn-lg" href="http://localhost/sim_rs_server_b" target="_blank">Status Web Service (Server B)</a>
            </div>
        </div>

        <div class="hero-card">
            <div class="hero-card-icon">🏥</div>
            <h3>Hak Akses Multi-Peran (RBAC)</h3>
            <p class="muted" style="font-size:14px;line-height:1.6;">
                Mendukung pengelolaan Poliklinik, Dokter (dapat menjadi pengguna aplikasi),
                Pasien (dapat menjadi pengguna aplikasi), serta penjaminan Asuransi kesehatan.
            </p>
            <div class="role-list">
                <span>Administrator RS</span>
                <span>Petugas Poliklinik</span>
                <span>Dokter</span>
                <span>Manajemen / Direktur</span>
                <span>Pasien</span>
            </div>
        </div>
    </main>

    <section id="poliklinik" class="landing-section">
        <div style="text-align:center;max-width:720px;margin:0 auto;">
            <span class="eyebrow">DATA LANGSUNG DARI WEB SERVICE SERVER B</span>
            <h2 style="margin:8px 0;">Unit Layanan Poliklinik Aktif</h2>
            <p class="muted">Daftar unit poliklinik di bawah ini diambil secara dinamis oleh Server A melalui endpoint <code>api_poliklinik.php</code> pada Server B.</p>
        </div>

        <div class="landing-grid">
            <?php foreach ($poliklinikList as $poli): ?>
                <div class="poli-card">
                    <span class="badge"><?= e($poli['kode_poliklinik']) ?></span>
                    <h4><?= e($poli['nama_poliklinik']) ?></h4>
                    <p class="muted" style="font-size:13px;margin:6px 0;"><?= e($poli['gedung_lantai']) ?> · <?= e($poli['jam_operasional']) ?></p>
                    <small style="color:#0f766e;font-weight:700;">
                        🩺 <?= (int)$poli['jumlah_dokter'] ?> Dokter &nbsp;|&nbsp; 🧑‍⚕️ <?= (int)$poli['jumlah_pasien'] ?> Pasien
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="landing-footer">
        &copy; <?= date('Y') ?> SIMRS Pasien Berbasis SOA &amp; RBAC — Praktikum Service-Oriented Architecture
    </footer>
</body>
</html>
