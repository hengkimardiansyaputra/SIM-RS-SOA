<?php
/**
 * dashboard.php - SERVER A Dashboard Utama Berbasis RBAC & Web Service
 */
require_once __DIR__ . '/auth_check.php';

$role          = $_SESSION['kode_role'] ?? '';
$namaRole      = $_SESSION['nama_role'] ?? '';
$id_poliklinik = $_SESSION['id_poliklinik'] ?? null;
$id_pengguna   = $_SESSION['id_pengguna'] ?? 0;

// Mengambil statistik dashboard dari Web Service Server B
$resDash = call_api('api_dashboard.php', 'GET', [], [
    'kode_role'     => $role,
    'id_poliklinik' => $id_poliklinik,
    'id_pengguna'   => $id_pengguna
]);

$dash = ($resDash['status'] ?? false) ? ($resDash['data'] ?? []) : [];
$labelScope   = $dash['label_scope'] ?? 'Seluruh Rumah Sakit';
$profilPasien = $dash['profil_pasien'] ?? null;
$profilDokter = $dash['profil_dokter'] ?? null;
$pasienBaru   = $dash['pasien_terbaru'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MENU UTAMA / DASHBOARD SOA</span>
            <h1>Selamat datang, <?= e($_SESSION['nama_lengkap'] ?? '') ?></h1>
            <p>Hak Akses RBAC: <strong><?= e($namaRole) ?></strong> · Lingkup Layanan: <strong><?= e($labelScope) ?></strong></p>
        </div>
        <a href="profil_saya.php" class="btn btn-outline" style="<?= in_array($role, ['PASIEN', 'DOKTER'], true) ? '' : 'display:none;' ?>">
            Lihat Profil Lengkap →
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🧑‍⚕️</div>
            <div>
                <span>Total Pasien (Scope)</span>
                <strong><?= number_format((int)($dash['total_pasien'] ?? 0), 0, ',', '.') ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🛡️</div>
            <div>
                <span>Pasien Peserta Asuransi</span>
                <strong><?= number_format((int)($dash['total_pasien_asuransi'] ?? 0), 0, ',', '.') ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💳</div>
            <div>
                <span>Pasien Umum (Non-Asuransi)</span>
                <strong><?= number_format((int)($dash['total_pasien_umum'] ?? 0), 0, ',', '.') ?></strong>
            </div>
        </div>

        <?php if ($role !== 'PASIEN'): ?>
            <div class="stat-card">
                <div class="stat-icon">🩺</div>
                <div>
                    <span>Dokter Aktif</span>
                    <strong><?= number_format((int)($dash['total_dokter'] ?? 0), 0, ',', '.') ?></strong>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏥</div>
                <div>
                    <span>Unit Poliklinik</span>
                    <strong><?= number_format((int)($dash['total_poliklinik'] ?? 0), 0, ',', '.') ?></strong>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏦</div>
                <div>
                    <span>Mitra Asuransi</span>
                    <strong><?= number_format((int)($dash['total_asuransi'] ?? 0), 0, ',', '.') ?></strong>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($role === 'PASIEN' && $profilPasien): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>Ringkasan Rekam Medis &amp; Kepesertaan Asuransi Anda</h2>
                <span class="badge"><?= e($profilPasien['no_rm']) ?></span>
            </div>
            <div class="info-grid">
                <div>
                    <span>Nama Lengkap Pasien</span>
                    <strong><?= e($profilPasien['nama_pasien']) ?> (<?= e($profilPasien['jenis_kelamin_text']) ?>)</strong>
                </div>
                <div>
                    <span>NIK / Tempat, Tanggal Lahir</span>
                    <strong><?= e($profilPasien['nik']) ?> · <?= e($profilPasien['tempat_lahir']) ?>, <?= e($profilPasien['tanggal_lahir']) ?></strong>
                </div>
                <div>
                    <span>Poliklinik &amp; Dokter Penanggung Jawab</span>
                    <strong><?= e($profilPasien['nama_poliklinik']) ?> — <?= e($profilPasien['nama_dokter'] ?? 'Belum ditentukan') ?></strong>
                </div>
                <div>
                    <span>Status Pembiayaan / Asuransi</span>
                    <strong>
                        <?php if (!empty($profilPasien['id_asuransi'])): ?>
                            <?= e($profilPasien['nama_asuransi']) ?> (No. Kartu: <?= e($profilPasien['no_kartu_asuransi']) ?> · Ditanggung <?= e($profilPasien['persen_tanggungan']) ?>%)
                        <?php else: ?>
                            Pasien Umum / Mandiri (Tanpa Asuransi)
                        <?php endif; ?>
                    </strong>
                </div>
                <div>
                    <span>Keluhan / Diagnosa Medis</span>
                    <strong><?= e($profilPasien['keluhan_diagnosa']) ?></strong>
                </div>
                <div>
                    <span>Status Pelayanan Saat Ini</span>
                    <strong><span class="badge"><?= e($profilPasien['status_pasien']) ?></span></strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'DOKTER' && $profilDokter): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>Identitas Dokter Praktik (Pengguna Aplikasi)</h2>
                <span class="badge"><?= e($profilDokter['no_sip']) ?></span>
            </div>
            <div class="info-grid">
                <div>
                    <span>Nama Dokter &amp; Spesialisasi</span>
                    <strong><?= e($profilDokter['nama_dokter']) ?> — <?= e($profilDokter['spesialisasi']) ?></strong>
                </div>
                <div>
                    <span>Poliklinik Penugasan</span>
                    <strong><?= e($profilDokter['nama_poliklinik']) ?> (<?= e($profilDokter['kode_poliklinik']) ?>)</strong>
                </div>
                <div>
                    <span>NIP / Nomor SIP</span>
                    <strong><?= e($profilDokter['nip_dokter']) ?> / <?= e($profilDokter['no_sip']) ?></strong>
                </div>
                <div>
                    <span>Jadwal Praktik</span>
                    <strong><?= e($profilDokter['jadwal_praktik']) ?></strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role !== 'PASIEN'): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>Daftar Pasien Terbaru (Data dari Web Service Server B)</h2>
                <a href="pasien.php" class="btn btn-primary btn-sm">Kelola Data Pasien</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Poliklinik</th>
                            <th>Dokter Pemeriksa</th>
                            <th>Penjamin / Asuransi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pasienBaru)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:24px;">Belum ada data pasien.</td></tr>
                        <?php else: foreach ($pasienBaru as $ps): ?>
                            <tr>
                                <td><strong><?= e($ps['no_rm']) ?></strong></td>
                                <td>
                                    <strong><?= e($ps['nama_pasien']) ?></strong><br>
                                    <small class="muted">NIK: <?= e($ps['nik']) ?></small>
                                </td>
                                <td><?= e($ps['nama_poliklinik']) ?></td>
                                <td><?= e($ps['nama_dokter'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($ps['id_asuransi'])): ?>
                                        <span class="badge badge-blue"><?= e($ps['nama_asuransi']) ?></span><br>
                                        <small class="muted">No: <?= e($ps['no_kartu_asuransi']) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Umum / Mandiri</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?= e($ps['status_pasien']) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Informasi Arsitektur SOA &amp; Sesi Pengguna</h2>
        </div>
        <div class="info-grid">
            <div>
                <span>Server A (Application &amp; UI Server)</span>
                <strong><code>C:\xampp\htdocs\sim_rs_server_a</code> (<code>http://localhost/sim_rs_server_a</code>)</strong>
            </div>
            <div>
                <span>Server B (Database &amp; Web Service Server)</span>
                <strong><code>C:\xampp\htdocs\sim_rs_server_b</code> (<code>http://localhost/sim_rs_server_b</code>)</strong>
            </div>
            <div>
                <span>Akun Login &amp; Role RBAC</span>
                <strong><?= e($_SESSION['username'] ?? '') ?> — <?= e($namaRole) ?> (<code><?= e($role) ?></code>)</strong>
            </div>
            <div>
                <span>Database Terpusat di Server B</span>
                <strong>MySQL / MariaDB: <code>db_rumah_sakit_soa</code></strong>
            </div>
        </div>
    </div>
</main>
</body>
</html>
