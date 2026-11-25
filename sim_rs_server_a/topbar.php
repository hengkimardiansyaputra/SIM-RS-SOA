<?php
/**
 * topbar.php - SERVER A
 * Header navigasi atas yang menampilkan identitas pengguna & koneksi SOA
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

$namaPengguna = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$namaRole     = $_SESSION['nama_role'] ?? 'Pengguna';
?>
<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')" title="Menu Navigasi">☰</button>
        <div class="brand-logo" style="width:36px;height:36px;font-size:13px;">RS</div>
        <div>
            <strong>SIMRS Pasien SOA</strong>
            <span>Server A (UI Client) ⇄ Server B (Web Service)</span>
        </div>
    </div>

    <div class="topbar-right">
        <span class="soa-pill">● REST API Terhubung</span>
        <div class="user-menu">
            <div class="avatar">
                <?= htmlspecialchars(strtoupper(substr($namaPengguna, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="user-info">
                <strong><?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>

        <form action="logout.php" method="post" style="margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
            <?= csrf_field() ?>
            <button type="submit" class="logout-button">Keluar</button>
        </form>
    </div>
</header>
