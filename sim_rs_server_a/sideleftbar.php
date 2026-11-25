<?php
/**
 * sideleftbar.php - SERVER A
 * Menu Navigasi Utama Berbasis Role-Based Access Control (RBAC)
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$role        = $_SESSION['kode_role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-title">MENU UTAMA</div>
    <a href="dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <span>▦</span> Dashboard
    </a>

    <?php if (in_array($role, ['ADMIN', 'PETUGAS_POLI', 'DOKTER'], true)): ?>
        <div class="sidebar-title">PELAYANAN MEDIS</div>
        <a href="pasien.php" class="sidebar-link <?= $currentPage === 'pasien.php' ? 'active' : '' ?>">
            <span>🧑‍⚕️</span> Data Pasien
        </a>
        <a href="dokter.php" class="sidebar-link <?= $currentPage === 'dokter.php' ? 'active' : '' ?>">
            <span>🩺</span> Data Dokter
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['ADMIN', 'PETUGAS_POLI'], true)): ?>
        <div class="sidebar-title">DATA MASTER RS</div>
        <a href="poliklinik.php" class="sidebar-link <?= $currentPage === 'poliklinik.php' ? 'active' : '' ?>">
            <span>🏥</span> Data Poliklinik
        </a>
        <a href="asuransi.php" class="sidebar-link <?= $currentPage === 'asuransi.php' ? 'active' : '' ?>">
            <span>🛡️</span> Data Asuransi
        </a>
    <?php endif; ?>

    <?php if ($role === 'ADMIN'): ?>
        <div class="sidebar-title">MANAJEMEN AKSES</div>
        <a href="pengguna.php" class="sidebar-link <?= $currentPage === 'pengguna.php' ? 'active' : '' ?>">
            <span>👥</span> Pengguna &amp; RBAC
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['ADMIN', 'DIREKTUR', 'PETUGAS_POLI', 'DOKTER'], true)): ?>
        <div class="sidebar-title">LAPORAN SOA</div>
        <a href="laporan.php" class="sidebar-link <?= $currentPage === 'laporan.php' ? 'active' : '' ?>">
            <span>📊</span> Laporan Rumah Sakit
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['PASIEN', 'DOKTER'], true)): ?>
        <div class="sidebar-title">PORTAL MANDIRI</div>
        <a href="profil_saya.php" class="sidebar-link <?= $currentPage === 'profil_saya.php' ? 'active' : '' ?>">
            <span>👤</span> Profil &amp; Rekam Medis
        </a>
    <?php endif; ?>
</aside>
