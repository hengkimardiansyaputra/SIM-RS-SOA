<?php
/**
 * poliklinik.php - SERVER A Modul CRUD Data Poliklinik
 * Mengonsumsi Web Service Server B: api_poliklinik.php
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN', 'PETUGAS_POLI']);

$role      = $_SESSION['kode_role'] ?? '';
$canModify = ($role === 'ADMIN');

$msg   = $_SESSION['flash_msg'] ?? '';
$err   = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $canModify) {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'create';

    if ($formAction === 'delete') {
        $resp = call_api('api_poliklinik.php?action=delete', 'POST', [
            'action'        => 'delete',
            'id_poliklinik' => (int)($_POST['id_poliklinik'] ?? 0)
        ]);
    } elseif ($formAction === 'update') {
        $resp = call_api('api_poliklinik.php?action=update', 'POST', [
            'action'          => 'update',
            'id_poliklinik'   => (int)($_POST['id_poliklinik'] ?? 0),
            'kode_poliklinik' => $_POST['kode_poliklinik'] ?? '',
            'nama_poliklinik' => $_POST['nama_poliklinik'] ?? '',
            'gedung_lantai'   => $_POST['gedung_lantai'] ?? '',
            'jam_operasional' => $_POST['jam_operasional'] ?? '',
            'keterangan'      => $_POST['keterangan'] ?? '',
            'status_aktif'    => $_POST['status_aktif'] ?? 'Aktif'
        ]);
    } else {
        $resp = call_api('api_poliklinik.php?action=create', 'POST', [
            'action'          => 'create',
            'kode_poliklinik' => $_POST['kode_poliklinik'] ?? '',
            'nama_poliklinik' => $_POST['nama_poliklinik'] ?? '',
            'gedung_lantai'   => $_POST['gedung_lantai'] ?? '',
            'jam_operasional' => $_POST['jam_operasional'] ?? '',
            'keterangan'      => $_POST['keterangan'] ?? '',
            'status_aktif'    => $_POST['status_aktif'] ?? 'Aktif'
        ]);
    }

    if (!empty($resp['status'])) {
        $_SESSION['flash_msg'] = $resp['message'] ?? 'Operasi berhasil.';
    } else {
        $_SESSION['flash_err'] = $resp['message'] ?? 'Operasi gagal.';
    }
    header("Location: poliklinik.php");
    exit;
}

$editRow = null;
if (isset($_GET['edit']) && $canModify) {
    $resEdit = call_api('api_poliklinik.php', 'GET', [], ['id' => (int)$_GET['edit']]);
    if (!empty($resEdit['status'])) {
        $editRow = $resEdit['data'];
    }
}

$resList = call_api('api_poliklinik.php', 'GET');
$listPoliklinik = ($resList['status'] ?? false) ? ($resList['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Poliklinik - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL DATA MASTER (WEB SERVICE: api_poliklinik.php)</span>
            <h1>Manajemen Data Poliklinik</h1>
            <p>Pengelolaan unit layanan poliklinik Rumah Sakit melalui Web Service Server B.</p>
        </div>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <?php if ($canModify): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><?= $editRow ? 'Edit Data Poliklinik' : 'Tambah Poliklinik Baru' ?></h2>
            </div>
            <form method="post" class="form-panel" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?>
                    <input type="hidden" name="id_poliklinik" value="<?= (int)$editRow['id_poliklinik'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Kode Poliklinik *</label>
                        <input type="text" name="kode_poliklinik" value="<?= e($editRow['kode_poliklinik'] ?? '') ?>" placeholder="Contoh: POLI-MATA" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Poliklinik *</label>
                        <input type="text" name="nama_poliklinik" value="<?= e($editRow['nama_poliklinik'] ?? '') ?>" placeholder="Contoh: Poliklinik Kesehatan Mata" required>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Gedung / Lantai</label>
                        <input type="text" name="gedung_lantai" value="<?= e($editRow['gedung_lantai'] ?? '') ?>" placeholder="Contoh: Gedung B - Lantai 2">
                    </div>
                    <div class="form-group">
                        <label>Jam Operasional</label>
                        <input type="text" name="jam_operasional" value="<?= e($editRow['jam_operasional'] ?? '08:00 - 16:00 WIB') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Poliklinik</label>
                        <select name="status_aktif">
                            <option value="Aktif" <?= ($editRow['status_aktif'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Tidak Aktif" <?= ($editRow['status_aktif'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan Layanan</label>
                        <input type="text" name="keterangan" value="<?= e($editRow['keterangan'] ?? '') ?>" placeholder="Deskripsi singkat layanan poliklinik">
                    </div>
                </div>

                <div class="form-actions">
                    <?php if ($editRow): ?>
                        <a href="poliklinik.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRow ? 'Simpan Perubahan' : '+ Tambah Poliklinik' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Daftar Poliklinik Rumah Sakit</h2>
            <span class="muted">Total: <?= count($listPoliklinik) ?> Poliklinik</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Poliklinik</th>
                        <th>Gedung / Lantai</th>
                        <th>Jam Operasional</th>
                        <th>Dokter</th>
                        <th>Pasien</th>
                        <th>Status</th>
                        <?php if ($canModify): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listPoliklinik as $row): ?>
                        <tr>
                            <td><span class="badge"><?= e($row['kode_poliklinik']) ?></span></td>
                            <td>
                                <strong><?= e($row['nama_poliklinik']) ?></strong><br>
                                <small class="muted"><?= e($row['keterangan']) ?></small>
                            </td>
                            <td><?= e($row['gedung_lantai']) ?></td>
                            <td><?= e($row['jam_operasional']) ?></td>
                            <td><?= (int)($row['jumlah_dokter'] ?? 0) ?> Dokter</td>
                            <td><?= (int)($row['jumlah_pasien'] ?? 0) ?> Pasien</td>
                            <td><span class="badge <?= $row['status_aktif'] === 'Aktif' ? '' : 'badge-gray' ?>"><?= e($row['status_aktif']) ?></span></td>
                            <?php if ($canModify): ?>
                                <td>
                                    <div class="action-group">
                                        <a href="poliklinik.php?edit=<?= (int)$row['id_poliklinik'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="post" onsubmit="return confirm('Hapus poliklinik ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_poliklinik" value="<?= (int)$row['id_poliklinik'] ?>">
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
