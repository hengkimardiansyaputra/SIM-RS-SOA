<?php
/**
 * asuransi.php - SERVER A Modul CRUD Data Asuransi
 * Mengonsumsi Web Service Server B: api_asuransi.php
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN', 'PETUGAS_POLI']);

$role      = $_SESSION['kode_role'] ?? '';
$canModify = ($role === 'ADMIN');

$msg = $_SESSION['flash_msg'] ?? '';
$err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $canModify) {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'create';

    if ($formAction === 'delete') {
        $resp = call_api('api_asuransi.php?action=delete', 'POST', [
            'action'      => 'delete',
            'id_asuransi' => (int)($_POST['id_asuransi'] ?? 0)
        ]);
    } elseif ($formAction === 'update') {
        $resp = call_api('api_asuransi.php?action=update', 'POST', [
            'action'             => 'update',
            'id_asuransi'        => (int)($_POST['id_asuransi'] ?? 0),
            'kode_asuransi'      => $_POST['kode_asuransi'] ?? '',
            'nama_asuransi'      => $_POST['nama_asuransi'] ?? '',
            'jenis_asuransi'     => $_POST['jenis_asuransi'] ?? 'BPJS Kesehatan',
            'persen_tanggungan'  => (float)($_POST['persen_tanggungan'] ?? 100),
            'plafon_maksimal'    => (float)($_POST['plafon_maksimal'] ?? 0),
            'telepon_callcenter' => $_POST['telepon_callcenter'] ?? '',
            'status_aktif'       => $_POST['status_aktif'] ?? 'Aktif'
        ]);
    } else {
        $resp = call_api('api_asuransi.php?action=create', 'POST', [
            'action'             => 'create',
            'kode_asuransi'      => $_POST['kode_asuransi'] ?? '',
            'nama_asuransi'      => $_POST['nama_asuransi'] ?? '',
            'jenis_asuransi'     => $_POST['jenis_asuransi'] ?? 'BPJS Kesehatan',
            'persen_tanggungan'  => (float)($_POST['persen_tanggungan'] ?? 100),
            'plafon_maksimal'    => (float)($_POST['plafon_maksimal'] ?? 0),
            'telepon_callcenter' => $_POST['telepon_callcenter'] ?? '',
            'status_aktif'       => $_POST['status_aktif'] ?? 'Aktif'
        ]);
    }

    if (!empty($resp['status'])) {
        $_SESSION['flash_msg'] = $resp['message'] ?? 'Operasi berhasil.';
    } else {
        $_SESSION['flash_err'] = $resp['message'] ?? 'Operasi gagal.';
    }
    header("Location: asuransi.php");
    exit;
}

$editRow = null;
if (isset($_GET['edit']) && $canModify) {
    $resEdit = call_api('api_asuransi.php', 'GET', [], ['id' => (int)$_GET['edit']]);
    if (!empty($resEdit['status'])) {
        $editRow = $resEdit['data'];
    }
}

$resList = call_api('api_asuransi.php', 'GET');
$listAsuransi = ($resList['status'] ?? false) ? ($resList['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Asuransi - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL PENJAMIN PEMBIAYAAN (WEB SERVICE: api_asuransi.php)</span>
            <h1>Manajemen Data Asuransi Kesehatan</h1>
            <p>Pengelolaan data BPJS Kesehatan, Asuransi Swasta, dan Korporasi untuk pasien yang menggunakan asuransi.</p>
        </div>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <?php if ($canModify): ?>
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><?= $editRow ? 'Edit Data Asuransi' : 'Tambah Mitra Asuransi Baru' ?></h2>
            </div>
            <form method="post" class="form-panel" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?>
                    <input type="hidden" name="id_asuransi" value="<?= (int)$editRow['id_asuransi'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Kode Asuransi *</label>
                        <input type="text" name="kode_asuransi" value="<?= e($editRow['kode_asuransi'] ?? '') ?>" placeholder="Contoh: ASR-ALLIANZ" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Asuransi / Program Penjamin *</label>
                        <input type="text" name="nama_asuransi" value="<?= e($editRow['nama_asuransi'] ?? '') ?>" placeholder="Contoh: Allianz SmartHealth Care" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Asuransi</label>
                        <select name="jenis_asuransi">
                            <?php foreach (['BPJS Kesehatan', 'Asuransi Swasta', 'Perusahaan/Korporasi', 'Jaminan Pemerintah'] as $j): ?>
                                <option value="<?= e($j) ?>" <?= ($editRow['jenis_asuransi'] ?? '') === $j ? 'selected' : '' ?>><?= e($j) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Persentase Tanggungan (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="persen_tanggungan" value="<?= e($editRow['persen_tanggungan'] ?? '100.00') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Plafon Maksimal Tanggungan (Rp)</label>
                        <input type="number" step="1000" min="0" name="plafon_maksimal" value="<?= e($editRow['plafon_maksimal'] ?? '50000000') ?>">
                    </div>
                    <div class="form-group">
                        <label>Call Center / Telepon Klaim</label>
                        <input type="text" name="telepon_callcenter" value="<?= e($editRow['telepon_callcenter'] ?? '') ?>" placeholder="Contoh: 165 / 1500136">
                    </div>
                </div>

                <div class="form-actions">
                    <?php if ($editRow): ?>
                        <a href="asuransi.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRow ? 'Simpan Perubahan' : '+ Tambah Asuransi' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Daftar Asuransi Penjamin Pasien</h2>
            <span class="muted">Total: <?= count($listAsuransi) ?> Asuransi</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Asuransi</th>
                        <th>Jenis Penjamin</th>
                        <th>Tanggungan (%)</th>
                        <th>Plafon Maksimal</th>
                        <th>Call Center</th>
                        <th>Pasien Terdaftar</th>
                        <?php if ($canModify): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listAsuransi as $row): ?>
                        <tr>
                            <td><span class="badge badge-blue"><?= e($row['kode_asuransi']) ?></span></td>
                            <td><strong><?= e($row['nama_asuransi']) ?></strong></td>
                            <td><?= e($row['jenis_asuransi']) ?></td>
                            <td><strong><?= e($row['persen_tanggungan']) ?>%</strong></td>
                            <td>Rp <?= number_format((float)$row['plafon_maksimal'], 0, ',', '.') ?></td>
                            <td><?= e($row['telepon_callcenter']) ?></td>
                            <td><span class="badge"><?= (int)($row['jumlah_pasien'] ?? 0) ?> Pasien</span></td>
                            <?php if ($canModify): ?>
                                <td>
                                    <div class="action-group">
                                        <a href="asuransi.php?edit=<?= (int)$row['id_asuransi'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="post" onsubmit="return confirm('Hapus data asuransi ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_asuransi" value="<?= (int)$row['id_asuransi'] ?>">
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
