<?php
/**
 * pasien.php - SERVER A Modul CRUD Data Pasien
 * Mengonsumsi Web Service Server B: api_pasien.php
 * Mendukung:
 * - Pasien (Bisa menjadi Pengguna Aplikasi)
 * - Asuransi (Bila ada pasien yang menggunakan Asuransi)
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN', 'PETUGAS_POLI', 'DOKTER']);

$role          = $_SESSION['kode_role'] ?? '';
$id_poliklinik = $_SESSION['id_poliklinik'] ?? null;
$canModify     = in_array($role, ['ADMIN', 'PETUGAS_POLI', 'DOKTER'], true);
$canDelete     = in_array($role, ['ADMIN', 'PETUGAS_POLI'], true);

$msg = $_SESSION['flash_msg'] ?? '';
$err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $canModify) {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'create';

    if ($formAction === 'delete' && $canDelete) {
        $resp = call_api('api_pasien.php?action=delete', 'POST', [
            'action'    => 'delete',
            'id_pasien' => (int)($_POST['id_pasien'] ?? 0)
        ]);
    } else {
        $poliTarget = ($role !== 'ADMIN' && $id_poliklinik) ? (int)$id_poliklinik : (int)($_POST['id_poliklinik'] ?? 0);
        $payload = [
            'action'            => $formAction,
            'id_pasien'         => (int)($_POST['id_pasien'] ?? 0),
            'id_poliklinik'     => $poliTarget,
            'id_dokter'         => !empty($_POST['id_dokter']) ? (int)$_POST['id_dokter'] : null,
            'id_asuransi'       => !empty($_POST['id_asuransi']) ? (int)$_POST['id_asuransi'] : null,
            'no_rm'             => $_POST['no_rm'] ?? '',
            'nik'               => $_POST['nik'] ?? '',
            'no_kartu_asuransi' => $_POST['no_kartu_asuransi'] ?? '',
            'nama_pasien'       => $_POST['nama_pasien'] ?? '',
            'jenis_kelamin'     => $_POST['jenis_kelamin'] ?? 'L',
            'tempat_lahir'      => $_POST['tempat_lahir'] ?? '',
            'tanggal_lahir'     => $_POST['tanggal_lahir'] ?? '',
            'golongan_darah'    => $_POST['golongan_darah'] ?? '-',
            'no_hp'             => $_POST['no_hp'] ?? '',
            'alamat'            => $_POST['alamat'] ?? '',
            'keluhan_diagnosa'  => $_POST['keluhan_diagnosa'] ?? '',
            'tanggal_daftar'    => $_POST['tanggal_daftar'] ?? date('Y-m-d'),
            'status_pasien'     => $_POST['status_pasien'] ?? 'Rawat Jalan',
            'buat_akun'         => !empty($_POST['buat_akun']) ? 1 : 0,
            'username_akun'     => $_POST['username_akun'] ?? ($_POST['no_rm'] ?? ''),
            'password_akun'     => $_POST['password_akun'] ?? 'password'
        ];
        $resp = call_api('api_pasien.php?action=' . $formAction, 'POST', $payload);
    }

    if (!empty($resp['status'])) {
        $_SESSION['flash_msg'] = $resp['message'] ?? 'Operasi berhasil.';
    } else {
        $_SESSION['flash_err'] = $resp['message'] ?? 'Operasi gagal.';
    }
    header("Location: pasien.php");
    exit;
}

$editRow = null;
if (isset($_GET['edit']) && $canModify) {
    $resEdit = call_api('api_pasien.php', 'GET', [], ['id' => (int)$_GET['edit']]);
    if (!empty($resEdit['status'])) {
        $editRow = $resEdit['data'];
    }
}

$queryFilter = [];
if ($role !== 'ADMIN' && $id_poliklinik) {
    $queryFilter['id_poliklinik'] = $id_poliklinik;
}
$resPasien  = call_api('api_pasien.php', 'GET', [], $queryFilter);
$listPasien = ($resPasien['status'] ?? false) ? ($resPasien['data'] ?? []) : [];

$resPoli    = call_api('api_poliklinik.php', 'GET');
$listPoli   = ($resPoli['status'] ?? false) ? ($resPoli['data'] ?? []) : [];

$resDokter  = call_api('api_dokter.php', 'GET', [], $queryFilter);
$listDokter = ($resDokter['status'] ?? false) ? ($resDokter['data'] ?? []) : [];

$resAsr     = call_api('api_asuransi.php', 'GET');
$listAsr    = ($resAsr['status'] ?? false) ? ($resAsr['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL PELAYANAN PASIEN (WEB SERVICE: api_pasien.php)</span>
            <h1>Manajemen Data Pasien Rumah Sakit</h1>
            <p>Mendukung pencatatan Pasien peserta Asuransi maupun Umum, serta pembuatan akun Pengguna Aplikasi untuk Pasien.</p>
        </div>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <?php if ($canModify): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><?= $editRow ? 'Edit Data Pasien (' . e($editRow['no_rm']) . ')' : 'Registrasi Pasien Baru' ?></h2>
            </div>
            <form method="post" class="form-panel" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?>
                    <input type="hidden" name="id_pasien" value="<?= (int)$editRow['id_pasien'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nomor Rekam Medis (No. RM) *</label>
                        <input type="text" name="no_rm" value="<?= e($editRow['no_rm'] ?? ('RM-2026-000' . (count($listPasien) + 1))) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NIK KTP (16 Digit) *</label>
                        <input type="text" name="nik" value="<?= e($editRow['nik'] ?? '') ?>" placeholder="1771xxxxxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap Pasien *</label>
                        <input type="text" name="nama_pasien" value="<?= e($editRow['nama_pasien'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin &amp; Gol. Darah</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            <select name="jenis_kelamin">
                                <option value="L" <?= ($editRow['jenis_kelamin'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                                <option value="P" <?= ($editRow['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                            </select>
                            <select name="golongan_darah">
                                <?php foreach (['A', 'B', 'AB', 'O', '-'] as $gd): ?>
                                    <option value="<?= $gd ?>" <?= ($editRow['golongan_darah'] ?? '-') === $gd ? 'selected' : '' ?>>Gol. <?= $gd ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="<?= e($editRow['tempat_lahir'] ?? 'Bengkulu') ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" value="<?= e($editRow['tanggal_lahir'] ?? '2000-01-01') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Poliklinik Tujuan *</label>
                        <select name="id_poliklinik" required <?= ($role !== 'ADMIN' && $id_poliklinik) ? 'disabled' : '' ?>>
                            <?php foreach ($listPoli as $pl): ?>
                                <option value="<?= (int)$pl['id_poliklinik'] ?>"
                                    <?= ((int)($editRow['id_poliklinik'] ?? $id_poliklinik) === (int)$pl['id_poliklinik']) ? 'selected' : '' ?>>
                                    <?= e($pl['kode_poliklinik']) ?> - <?= e($pl['nama_poliklinik']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dokter Pemeriksa</label>
                        <select name="id_dokter">
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($listDokter as $dk): ?>
                                <option value="<?= (int)$dk['id_dokter'] ?>"
                                    <?= ((int)($editRow['id_dokter'] ?? 0) === (int)$dk['id_dokter']) ? 'selected' : '' ?>>
                                    <?= e($dk['nama_dokter']) ?> (<?= e($dk['nama_poliklinik']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Asuransi Kesehatan (Kosongkan bila Pasien Umum)</label>
                        <select name="id_asuransi">
                            <option value="">-- Pasien Umum / Mandiri (Tanpa Asuransi) --</option>
                            <?php foreach ($listAsr as $as): ?>
                                <option value="<?= (int)$as['id_asuransi'] ?>"
                                    <?= ((int)($editRow['id_asuransi'] ?? 0) === (int)$as['id_asuransi']) ? 'selected' : '' ?>>
                                    <?= e($as['nama_asuransi']) ?> (Ditanggung <?= e($as['persen_tanggungan']) ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nomor Kartu Asuransi / BPJS (Bila Menggunakan Asuransi)</label>
                        <input type="text" name="no_kartu_asuransi" value="<?= e($editRow['no_kartu_asuransi'] ?? '') ?>" placeholder="Contoh: 0001283910211">
                    </div>

                    <div class="form-group">
                        <label>Nomor HP Pasien</label>
                        <input type="text" name="no_hp" value="<?= e($editRow['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Daftar &amp; Status Layanan</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            <input type="date" name="tanggal_daftar" value="<?= e($editRow['tanggal_daftar'] ?? date('Y-m-d')) ?>">
                            <select name="status_pasien">
                                <?php foreach (['Rawat Jalan', 'Rawat Inap', 'Selesai', 'Rujuk'] as $sp): ?>
                                    <option value="<?= $sp ?>" <?= ($editRow['status_pasien'] ?? 'Rawat Jalan') === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <input type="text" name="alamat" value="<?= e($editRow['alamat'] ?? '') ?>" placeholder="Alamat domisili pasien">
                    </div>
                    <div class="form-group">
                        <label>Keluhan &amp; Diagnosa Medis</label>
                        <input type="text" name="keluhan_diagnosa" value="<?= e($editRow['keluhan_diagnosa'] ?? '') ?>" placeholder="Keluhan utama atau hasil diagnosa dokter">
                    </div>

                    <?php if (empty($editRow['id_pengguna'])): ?>
                        <div class="form-group full" style="background:#f0fdfa;padding:15px;border-radius:10px;border:1px solid #99f6e4;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px;">
                                <input type="checkbox" name="buat_akun" value="1" style="width:auto;" onchange="document.getElementById('boxAkunPasien').style.display = this.checked ? 'grid' : 'none';">
                                <strong>Daftarkan Pasien Ini Sebagai Pengguna Aplikasi (Buat Akun Login Role PASIEN)</strong>
                            </label>
                            <div id="boxAkunPasien" class="form-grid" style="display:none;">
                                <div class="form-group" style="margin:0;">
                                    <label>Username Login Pasien (Default: No. RM)</label>
                                    <input type="text" name="username_akun" placeholder="Kosongkan untuk otomatis memakai No. RM">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label>Password Login (Default: password)</label>
                                    <input type="text" name="password_akun" value="password">
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group full">
                            <span class="badge">✔ Pasien Memiliki Akun Pengguna: <?= e($editRow['username_akun']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <?php if ($editRow): ?>
                        <a href="pasien.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRow ? 'Simpan Perubahan' : '+ Simpan Data Pasien' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Daftar Pasien Terdaftar</h2>
            <span class="muted">Total: <?= count($listPasien) ?> Pasien</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. RM / NIK</th>
                        <th>Nama Pasien</th>
                        <th>Poliklinik &amp; Dokter</th>
                        <th>Penjamin / Asuransi</th>
                        <th>Keluhan / Diagnosa</th>
                        <th>Akun Aplikasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listPasien as $row): ?>
                        <tr>
                            <td>
                                <strong><?= e($row['no_rm']) ?></strong><br>
                                <small class="muted">NIK: <?= e($row['nik']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($row['nama_pasien']) ?></strong> (<?= e($row['jenis_kelamin']) ?>)<br>
                                <small class="muted">Lahir: <?= e($row['tanggal_lahir']) ?> · Gol. <?= e($row['golongan_darah']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($row['nama_poliklinik']) ?></strong><br>
                                <small class="muted"><?= e($row['nama_dokter'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($row['id_asuransi'])): ?>
                                    <span class="badge badge-blue"><?= e($row['nama_asuransi']) ?></span><br>
                                    <small class="muted">Kartu: <?= e($row['no_kartu_asuransi']) ?> (<?= e($row['persen_tanggungan']) ?>%)</small>
                                <?php else: ?>
                                    <span class="badge badge-gray">Umum / Mandiri</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['keluhan_diagnosa']) ?></td>
                            <td>
                                <?php if (!empty($row['id_pengguna'])): ?>
                                    <span class="badge">👤 <?= e($row['username_akun']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Non-Akun</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge"><?= e($row['status_pasien']) ?></span></td>
                            <td>
                                <div class="action-group">
                                    <a href="pasien.php?edit=<?= (int)$row['id_pasien'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <?php if ($canDelete): ?>
                                        <form method="post" onsubmit="return confirm('Hapus data pasien ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_pasien" value="<?= (int)$row['id_pasien'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
