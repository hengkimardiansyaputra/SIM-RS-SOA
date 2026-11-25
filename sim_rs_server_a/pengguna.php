<?php
/**
 * pengguna.php - SERVER A Modul CRUD Manajemen Pengguna & Role RBAC
 * Mengonsumsi Web Service Server B: api_pengguna.php
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN']);

$msg = $_SESSION['flash_msg'] ?? '';
$err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'create';

    if ($formAction === 'delete') {
        $resp = call_api('api_pengguna.php?action=delete', 'POST', [
            'action'      => 'delete',
            'id_pengguna' => (int)($_POST['id_pengguna'] ?? 0)
        ]);
    } else {
        $resp = call_api('api_pengguna.php?action=' . $formAction, 'POST', [
            'action'        => $formAction,
            'id_pengguna'   => (int)($_POST['id_pengguna'] ?? 0),
            'id_role'       => (int)($_POST['id_role'] ?? 0),
            'id_poliklinik' => !empty($_POST['id_poliklinik']) ? (int)$_POST['id_poliklinik'] : null,
            'username'      => $_POST['username'] ?? '',
            'password'      => $_POST['password'] ?? '',
            'nama_lengkap'  => $_POST['nama_lengkap'] ?? '',
            'email'         => $_POST['email'] ?? '',
            'no_hp'         => $_POST['no_hp'] ?? '',
            'status_aktif'  => $_POST['status_aktif'] ?? 'Aktif'
        ]);
    }

    if (!empty($resp['status'])) {
        $_SESSION['flash_msg'] = $resp['message'] ?? 'Operasi berhasil.';
    } else {
        $_SESSION['flash_err'] = $resp['message'] ?? 'Operasi gagal.';
    }
    header("Location: pengguna.php");
    exit;
}

$editRow = null;
if (isset($_GET['edit'])) {
    $resEdit = call_api('api_pengguna.php', 'GET', [], ['id' => (int)$_GET['edit']]);
    if (!empty($resEdit['status'])) {
        $editRow = $resEdit['data'];
    }
}

$resUsers  = call_api('api_pengguna.php', 'GET');
$listUsers = ($resUsers['status'] ?? false) ? ($resUsers['data'] ?? []) : [];

$resRoles  = call_api('api_pengguna.php', 'GET', [], ['action' => 'roles']);
$listRoles = ($resRoles['status'] ?? false) ? ($resRoles['data'] ?? []) : [];

$resPoli   = call_api('api_poliklinik.php', 'GET');
$listPoli  = ($resPoli['status'] ?? false) ? ($resPoli['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna & RBAC - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL KEAMANAN &amp; RBAC (WEB SERVICE: api_pengguna.php)</span>
            <h1>Manajemen Pengguna &amp; Hak Akses (RBAC)</h1>
            <p>Pengelolaan akun Administrator, Petugas Poliklinik, Dokter, Manajemen, serta Pasien pengguna aplikasi.</p>
        </div>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2><?= $editRow ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna Baru' ?></h2>
        </div>
        <form method="post" class="form-panel" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="<?= $editRow ? 'update' : 'create' ?>">
            <?php if ($editRow): ?>
                <input type="hidden" name="id_pengguna" value="<?= (int)$editRow['id_pengguna'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Username Login *</label>
                    <input type="text" name="username" value="<?= e($editRow['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password <?= $editRow ? '(Kosongkan bila tidak diubah)' : '*' ?></label>
                    <input type="password" name="password" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Biarkan kosong jika tetap' : 'Masukkan password' ?>">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap Pengguna *</label>
                    <input type="text" name="nama_lengkap" value="<?= e($editRow['nama_lengkap'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Role RBAC *</label>
                    <select name="id_role" required>
                        <?php foreach ($listRoles as $rl): ?>
                            <option value="<?= (int)$rl['id_role'] ?>" <?= ((int)($editRow['id_role'] ?? 0) === (int)$rl['id_role']) ? 'selected' : '' ?>>
                                <?= e($rl['kode_role']) ?> — <?= e($rl['nama_role']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Scope Unit Poliklinik (Untuk Petugas Poli / Dokter / Pasien)</label>
                    <select name="id_poliklinik">
                        <option value="">-- Seluruh Unit Rumah Sakit (Global) --</option>
                        <?php foreach ($listPoli as $pl): ?>
                            <option value="<?= (int)$pl['id_poliklinik'] ?>" <?= ((int)($editRow['id_poliklinik'] ?? 0) === (int)$pl['id_poliklinik']) ? 'selected' : '' ?>>
                                <?= e($pl['kode_poliklinik']) ?> - <?= e($pl['nama_poliklinik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Akun</label>
                    <select name="status_aktif">
                        <option value="Aktif" <?= ($editRow['status_aktif'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= ($editRow['status_aktif'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($editRow['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp" value="<?= e($editRow['no_hp'] ?? '') ?>">
                </div>
            </div>

            <div class="form-actions">
                <?php if ($editRow): ?>
                    <a href="pengguna.php" class="btn btn-outline">Batal</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $editRow ? 'Simpan Perubahan' : '+ Tambah Pengguna' ?></button>
            </div>
        </form>
    </div>

    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>Daftar Pengguna Aplikasi &amp; Hak Akses RBAC</h2>
            <span class="muted">Total: <?= count($listUsers) ?> Pengguna</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role RBAC</th>
                        <th>Scope Poliklinik</th>
                        <th>Entitas Terkait</th>
                        <th>Login Terakhir</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listUsers as $u): ?>
                        <tr>
                            <td>#<?= (int)$u['id_pengguna'] ?></td>
                            <td><strong><code><?= e($u['username']) ?></code></strong></td>
                            <td>
                                <strong><?= e($u['nama_lengkap']) ?></strong><br>
                                <small class="muted"><?= e($u['email']) ?></small>
                            </td>
                            <td><span class="badge badge-blue"><?= e($u['kode_role']) ?></span></td>
                            <td><?= e($u['nama_poliklinik'] ?? 'Seluruh RS') ?></td>
                            <td>
                                <?php if (!empty($u['linked_dokter'])): ?>
                                    <span class="badge">🩺 Dokter: <?= e($u['linked_dokter']) ?></span>
                                <?php elseif (!empty($u['linked_no_rm'])): ?>
                                    <span class="badge">🧑‍⚕️ Pasien: <?= e($u['linked_no_rm']) ?></span>
                                <?php else: ?>
                                    <span class="muted">Internal RS</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= e($u['last_login'] ?? 'Belum pernah') ?></small></td>
                            <td><span class="badge"><?= e($u['status_aktif']) ?></span></td>
                            <td>
                                <div class="action-group">
                                    <a href="pengguna.php?edit=<?= (int)$u['id_pengguna'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <?php if ((int)$u['id_pengguna'] !== 1): ?>
                                        <form method="post" onsubmit="return confirm('Hapus akun pengguna ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
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
