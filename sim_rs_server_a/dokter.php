<?php
/**
 * dokter.php - SERVER A Modul CRUD Data Dokter
 * Mengonsumsi Web Service Server B: api_dokter.php
 * Mendukung fitur: Dokter (Bisa menjadi Pengguna Aplikasi)
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN', 'PETUGAS_POLI', 'DOKTER']);

$role          = $_SESSION['kode_role'] ?? '';
$id_poliklinik = $_SESSION['id_poliklinik'] ?? null;
$canModify     = ($role === 'ADMIN');

$msg = $_SESSION['flash_msg'] ?? '';
$err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $canModify) {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'create';

    if ($formAction === 'delete') {
        $resp = call_api('api_dokter.php?action=delete', 'POST', [
            'action'    => 'delete',
            'id_dokter' => (int)($_POST['id_dokter'] ?? 0)
        ]);
    } else {
        $payload = [
            'action'         => $formAction,
            'id_dokter'      => (int)($_POST['id_dokter'] ?? 0),
            'id_poliklinik'  => (int)($_POST['id_poliklinik'] ?? 0),
            'nip_dokter'     => $_POST['nip_dokter'] ?? '',
            'no_sip'         => $_POST['no_sip'] ?? '',
            'nama_dokter'    => $_POST['nama_dokter'] ?? '',
            'spesialisasi'   => $_POST['spesialisasi'] ?? '',
            'jenis_kelamin'  => $_POST['jenis_kelamin'] ?? 'L',
            'no_hp'          => $_POST['no_hp'] ?? '',
            'email'          => $_POST['email'] ?? '',
            'jadwal_praktik' => $_POST['jadwal_praktik'] ?? '',
            'status_dokter'  => $_POST['status_dokter'] ?? 'Aktif',
            'buat_akun'      => !empty($_POST['buat_akun']) ? 1 : 0,
            'username_akun'  => $_POST['username_akun'] ?? '',
            'password_akun'  => $_POST['password_akun'] ?? 'password'
        ];
        $resp = call_api('api_dokter.php?action=' . $formAction, 'POST', $payload);
    }

    if (!empty($resp['status'])) {
        $_SESSION['flash_msg'] = $resp['message'] ?? 'Operasi berhasil.';
    } else {
        $_SESSION['flash_err'] = $resp['message'] ?? 'Operasi gagal.';
    }
    header("Location: dokter.php");
    exit;
}

$editRow = null;
if (isset($_GET['edit']) && $canModify) {
    $resEdit = call_api('api_dokter.php', 'GET', [], ['id' => (int)$_GET['edit']]);
    if (!empty($resEdit['status'])) {
        $editRow = $resEdit['data'];
    }
}

$queryFilter = [];
if (in_array($role, ['PETUGAS_POLI', 'DOKTER'], true) && $id_poliklinik) {
    $queryFilter['id_poliklinik'] = $id_poliklinik;
}
$resDokter  = call_api('api_dokter.php', 'GET', [], $queryFilter);
$listDokter = ($resDokter['status'] ?? false) ? ($resDokter['data'] ?? []) : [];

$resPoli  = call_api('api_poliklinik.php', 'GET');
$listPoli = ($resPoli['status'] ?? false) ? ($resPoli['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dokter - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL TENAGA MEDIS (WEB SERVICE: api_dokter.php)</span>
            <h1>Data Dokter &amp; Hak Akses Pengguna Dokter</h1>
            <p>Dokter dapat didaftarkan sekaligus sebagai Pengguna Aplikasi (Role: <code>DOKTER</code>) untuk mengakses SIMRS.</p>
        </div>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <?php if ($canModify): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><?= $editRow ? 'Edit Data Dokter' : 'Tambah Dokter Baru' ?></h2>
            </div>
            <form method="post" class="form-panel" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?>
                    <input type="hidden" name="id_dokter" value="<?= (int)$editRow['id_dokter'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>NIP Dokter *</label>
                        <input type="text" name="nip_dokter" value="<?= e($editRow['nip_dokter'] ?? '') ?>" placeholder="1988xxxxxxxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor SIP (Surat Izin Praktik) *</label>
                        <input type="text" name="no_sip" value="<?= e($editRow['no_sip'] ?? '') ?>" placeholder="503/xxx/SIP-DOK/2026" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap &amp; Gelar Dokter *</label>
                        <input type="text" name="nama_dokter" value="<?= e($editRow['nama_dokter'] ?? '') ?>" placeholder="dr. Nama Dokter, Sp.PD" required>
                    </div>
                    <div class="form-group">
                        <label>Spesialisasi *</label>
                        <input type="text" name="spesialisasi" value="<?= e($editRow['spesialisasi'] ?? 'Dokter Umum') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Poliklinik Penugasan *</label>
                        <select name="id_poliklinik" required>
                            <option value="">-- Pilih Poliklinik --</option>
                            <?php foreach ($listPoli as $pl): ?>
                                <option value="<?= (int)$pl['id_poliklinik'] ?>" <?= ((int)($editRow['id_poliklinik'] ?? 0) === (int)$pl['id_poliklinik']) ? 'selected' : '' ?>>
                                    <?= e($pl['kode_poliklinik']) ?> - <?= e($pl['nama_poliklinik']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="L" <?= ($editRow['jenis_kelamin'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                            <option value="P" <?= ($editRow['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nomor HP</label>
                        <input type="text" name="no_hp" value="<?= e($editRow['no_hp'] ?? '') ?>" placeholder="0812xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= e($editRow['email'] ?? '') ?>" placeholder="dokter@rs-umb.ac.id">
                    </div>
                    <div class="form-group">
                        <label>Jadwal Praktik</label>
                        <input type="text" name="jadwal_praktik" value="<?= e($editRow['jadwal_praktik'] ?? 'Senin - Jumat (08:00 - 14:00)') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Dokter</label>
                        <select name="status_dokter">
                            <?php foreach (['Aktif', 'Cuti', 'Tidak Aktif'] as $st): ?>
                                <option value="<?= $st ?>" <?= ($editRow['status_dokter'] ?? 'Aktif') === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (empty($editRow['id_pengguna'])): ?>
                        <div class="form-group full" style="background:#f0fdfa;padding:15px;border-radius:10px;border:1px solid #99f6e4;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px;">
                                <input type="checkbox" name="buat_akun" value="1" style="width:auto;" onchange="document.getElementById('boxAkunDokter').style.display = this.checked ? 'grid' : 'none';">
                                <strong>Jadikan Dokter Ini Sebagai Pengguna Aplikasi (Buat Akun Login Role DOKTER)</strong>
                            </label>
                            <div id="boxAkunDokter" class="form-grid" style="display:none;">
                                <div class="form-group" style="margin:0;">
                                    <label>Username Login Dokter</label>
                                    <input type="text" name="username_akun" placeholder="Contoh: dr.maya">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label>Password Login (Default: password)</label>
                                    <input type="text" name="password_akun" value="password">
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group full">
                            <span class="badge">✔ Terhubung ke Akun Pengguna: <?= e($editRow['username_akun']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <?php if ($editRow): ?>
                        <a href="dokter.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRow ? 'Simpan Perubahan' : '+ Simpan Data Dokter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Daftar Dokter Rumah Sakit</h2>
            <span class="muted">Total: <?= count($listDokter) ?> Dokter</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIP / No. SIP</th>
                        <th>Nama Dokter</th>
                        <th>Spesialisasi</th>
                        <th>Poliklinik</th>
                        <th>Jadwal Praktik</th>
                        <th>Akun Pengguna</th>
                        <th>Status</th>
                        <?php if ($canModify): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listDokter as $row): ?>
                        <tr>
                            <td>
                                <strong><?= e($row['nip_dokter']) ?></strong><br>
                                <small class="muted"><?= e($row['no_sip']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($row['nama_dokter']) ?></strong> (<?= e($row['jenis_kelamin']) ?>)<br>
                                <small class="muted"><?= e($row['no_hp']) ?> · <?= e($row['email']) ?></small>
                            </td>
                            <td><?= e($row['spesialisasi']) ?></td>
                            <td><span class="badge"><?= e($row['nama_poliklinik']) ?></span></td>
                            <td><?= e($row['jadwal_praktik']) ?></td>
                            <td>
                                <?php if (!empty($row['id_pengguna'])): ?>
                                    <span class="badge badge-blue">👤 <?= e($row['username_akun']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Belum Ada Akun</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge"><?= e($row['status_dokter']) ?></span></td>
                            <?php if ($canModify): ?>
                                <td>
                                    <div class="action-group">
                                        <a href="dokter.php?edit=<?= (int)$row['id_dokter'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="post" onsubmit="return confirm('Hapus data dokter ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_dokter" value="<?= (int)$row['id_dokter'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
