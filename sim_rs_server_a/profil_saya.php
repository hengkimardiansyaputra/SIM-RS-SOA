<?php
/**
 * profil_saya.php - SERVER A
 * Portal Mandiri untuk Role PASIEN (Melihat Rekam Medis & Kartu Asuransi)
 * dan Role DOKTER (Melihat Profil Praktik & Daftar Pasien yang Ditangani)
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['PASIEN', 'DOKTER', 'ADMIN']);

$role        = $_SESSION['kode_role'] ?? '';
$id_pengguna = (int)($_SESSION['id_pengguna'] ?? 0);

$resDash = call_api('api_dashboard.php', 'GET', [], [
    'kode_role'     => $role,
    'id_poliklinik' => $_SESSION['id_poliklinik'] ?? null,
    'id_pengguna'   => $id_pengguna
]);
$dash         = ($resDash['status'] ?? false) ? ($resDash['data'] ?? []) : [];
$profilPasien = $dash['profil_pasien'] ?? null;
$profilDokter = $dash['profil_dokter'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">PORTAL MANDIRI PENGGUNA</span>
            <h1>Profil &amp; Data Medis Saya</h1>
            <p>Data diambil secara real-time melalui Web Service Server B sesuai akun pengguna Anda.</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-outline no-print">🖨️ Cetak Kartu / Profil</button>
    </div>

    <?php if ($profilPasien): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>Kartu Rekam Medis Elektronik Pasien</h2>
                <span class="badge"><?= e($profilPasien['no_rm']) ?></span>
            </div>
            <div class="info-grid">
                <div><span>Nomor Rekam Medis (No. RM)</span><strong><?= e($profilPasien['no_rm']) ?></strong></div>
                <div><span>NIK KTP</span><strong><?= e($profilPasien['nik']) ?></strong></div>
                <div><span>Nama Lengkap Pasien</span><strong><?= e($profilPasien['nama_pasien']) ?></strong></div>
                <div><span>Jenis Kelamin &amp; Golongan Darah</span><strong><?= e($profilPasien['jenis_kelamin_text']) ?> / Gol. Darah <?= e($profilPasien['golongan_darah']) ?></strong></div>
                <div><span>Tempat, Tanggal Lahir</span><strong><?= e($profilPasien['tempat_lahir']) ?>, <?= e($profilPasien['tanggal_lahir']) ?></strong></div>
                <div><span>Nomor Telepon &amp; Alamat</span><strong><?= e($profilPasien['no_hp']) ?> — <?= e($profilPasien['alamat']) ?></strong></div>
                <div><span>Poliklinik Tujuan</span><strong><?= e($profilPasien['nama_poliklinik']) ?> (<?= e($profilPasien['kode_poliklinik']) ?>)</strong></div>
                <div><span>Dokter Pemeriksa</span><strong><?= e($profilPasien['nama_dokter'] ?? '-') ?> (<?= e($profilPasien['spesialisasi'] ?? '-') ?>)</strong></div>
                <div>
                    <span>Kepesertaan Asuransi</span>
                    <strong>
                        <?php if (!empty($profilPasien['id_asuransi'])): ?>
                            <span class="badge badge-blue"><?= e($profilPasien['nama_asuransi']) ?></span>
                            — No. Kartu: <?= e($profilPasien['no_kartu_asuransi']) ?> (Tanggungan <?= e($profilPasien['persen_tanggungan']) ?>%)
                        <?php else: ?>
                            Pasien Umum / Mandiri (Tidak Menggunakan Asuransi)
                        <?php endif; ?>
                    </strong>
                </div>
                <div><span>Tanggal Registrasi &amp; Status</span><strong><?= e($profilPasien['tanggal_daftar']) ?> · <span class="badge"><?= e($profilPasien['status_pasien']) ?></span></strong></div>
                <div style="grid-column: 1 / -1;"><span>Keluhan &amp; Diagnosa Medis</span><strong><?= e($profilPasien['keluhan_diagnosa']) ?></strong></div>
            </div>
        </div>
    <?php elseif ($profilDokter): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>Profil Dokter Spesialis / Umum</h2>
                <span class="badge"><?= e($profilDokter['kode_poliklinik']) ?></span>
            </div>
            <div class="info-grid">
                <div><span>Nama Lengkap Dokter</span><strong><?= e($profilDokter['nama_dokter']) ?></strong></div>
                <div><span>Spesialisasi</span><strong><?= e($profilDokter['spesialisasi']) ?></strong></div>
                <div><span>NIP Dokter</span><strong><?= e($profilDokter['nip_dokter']) ?></strong></div>
                <div><span>Nomor SIP</span><strong><?= e($profilDokter['no_sip']) ?></strong></div>
                <div><span>Unit Poliklinik</span><strong><?= e($profilDokter['nama_poliklinik']) ?></strong></div>
                <div><span>Jadwal Praktik</span><strong><?= e($profilDokter['jadwal_praktik']) ?></strong></div>
                <div><span>Kontak Email &amp; No. HP</span><strong><?= e($profilDokter['email']) ?> / <?= e($profilDokter['no_hp']) ?></strong></div>
                <div><span>Akun Pengguna Terhubung</span><strong><code><?= e($profilDokter['username_akun']) ?></code> (<?= e($profilDokter['status_dokter']) ?>)</strong></div>
            </div>
        </div>
    <?php else: ?>
        <div class="dashboard-panel" style="padding:30px;text-align:center;">
            <p class="muted">Akun ini adalah akun manajemen/administrator dan tidak terikat ke satu rekam medis pasien atau dokter tertentu.</p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
