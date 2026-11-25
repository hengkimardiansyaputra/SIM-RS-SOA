<?php
/**
 * laporan.php - SERVER A Modul Laporan Rumah Sakit & Audit Log
 * Mengonsumsi Web Service Server B: api_laporan.php
 */
require_once __DIR__ . '/auth_check.php';
wajib_role(['ADMIN', 'DIREKTUR', 'PETUGAS_POLI', 'DOKTER']);

$role          = $_SESSION['kode_role'] ?? '';
$scopePoli     = $_SESSION['id_poliklinik'] ?? null;

$filterPoli   = ($role !== 'ADMIN' && $role !== 'DIREKTUR' && $scopePoli)
                ? (int)$scopePoli
                : (int)($_GET['id_poliklinik'] ?? 0);
$filterAsr    = (int)($_GET['id_asuransi'] ?? 0);
$filterBiaya  = trim($_GET['pembiayaan'] ?? '');
$filterStatus = trim($_GET['status_pasien'] ?? '');

$resLap = call_api('api_laporan.php', 'GET', [], [
    'id_poliklinik' => $filterPoli ?: null,
    'id_asuransi'   => $filterAsr ?: null,
    'pembiayaan'    => $filterBiaya ?: null,
    'status_pasien' => $filterStatus ?: null
]);

$lapData         = ($resLap['status'] ?? false) ? ($resLap['data'] ?? []) : [];
$rekapPoliklinik = $lapData['rekap_poliklinik'] ?? [];
$rekapAsuransi   = $lapData['rekap_asuransi'] ?? [];
$laporanPasien   = $lapData['laporan_pasien'] ?? [];
$auditLogs       = $lapData['audit_logs'] ?? [];

$resPoli  = call_api('api_poliklinik.php', 'GET');
$listPoli = ($resPoli['status'] ?? false) ? ($resPoli['data'] ?? []) : [];

$resAsr   = call_api('api_asuransi.php', 'GET');
$listAsr  = ($resAsr['status'] ?? false) ? ($resAsr['data'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rumah Sakit - SIMRS Pasien SOA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include __DIR__ . '/topbar.php'; ?>
<?php include __DIR__ . '/sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading-flex">
        <div>
            <span class="eyebrow">MODUL PELAPORAN EKSEKUTIF (WEB SERVICE: api_laporan.php)</span>
            <h1>Laporan Terpadu Pasien, Poliklinik &amp; Asuransi</h1>
            <p>Disajikan secara real-time dari Server B (Basisdata &amp; Web Service) ke Server A.</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-primary no-print">🖨️ Cetak Laporan</button>
    </div>

    <!-- 1. Rekapitulasi Per Poliklinik -->
    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>1. Laporan Rekapitulasi Pasien &amp; Dokter per Poliklinik</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Poliklinik</th>
                        <th>Dokter Aktif</th>
                        <th>Total Pasien</th>
                        <th>Pasien Asuransi</th>
                        <th>Pasien Umum</th>
                        <th>Rawat Jalan</th>
                        <th>Rawat Inap</th>
                        <th>Selesai / Rujuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekapPoliklinik as $rp): ?>
                        <tr>
                            <td><span class="badge"><?= e($rp['kode_poliklinik']) ?></span></td>
                            <td><strong><?= e($rp['nama_poliklinik']) ?></strong></td>
                            <td><?= (int)$rp['jumlah_dokter'] ?> Dokter</td>
                            <td><strong><?= (int)$rp['total_pasien'] ?> Pasien</strong></td>
                            <td><span class="badge badge-blue"><?= (int)$rp['pasien_asuransi'] ?> Asuransi</span></td>
                            <td><span class="badge badge-gray"><?= (int)$rp['pasien_umum'] ?> Umum</span></td>
                            <td><?= (int)$rp['rawat_jalan'] ?></td>
                            <td><?= (int)$rp['rawat_inap'] ?></td>
                            <td><?= (int)$rp['selesai'] + (int)$rp['rujuk'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Rekapitulasi Per Asuransi -->
    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>2. Laporan Rekapitulasi Pasien Berdasarkan Penjamin Asuransi</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode Asuransi</th>
                        <th>Nama Asuransi Penjamin</th>
                        <th>Jenis Penjamin</th>
                        <th>Persentase Tanggungan</th>
                        <th>Plafon Maksimal</th>
                        <th>Jumlah Pasien Peserta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekapAsuransi as $ra): ?>
                        <tr>
                            <td><span class="badge badge-blue"><?= e($ra['kode_asuransi']) ?></span></td>
                            <td><strong><?= e($ra['nama_asuransi']) ?></strong></td>
                            <td><?= e($ra['jenis_asuransi']) ?></td>
                            <td><?= e($ra['persen_tanggungan']) ?>%</td>
                            <td>Rp <?= number_format((float)$ra['plafon_maksimal'], 0, ',', '.') ?></td>
                            <td><strong><?= (int)$ra['jumlah_pasien'] ?> Pasien</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Filter & Laporan Detail Pasien -->
    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2>3. Laporan Detail Data Pasien Rumah Sakit</h2>
            <span class="muted">Menampilkan <?= count($laporanPasien) ?> Pasien</span>
        </div>

        <form method="get" class="form-panel no-print" style="border-bottom:1px solid var(--border);">
            <div class="form-grid" style="grid-template-columns: repeat(4, 1fr);">
                <div class="form-group" style="margin:0;">
                    <label>Filter Poliklinik</label>
                    <select name="id_poliklinik">
                        <option value="">-- Semua Poliklinik --</option>
                        <?php foreach ($listPoli as $pl): ?>
                            <option value="<?= (int)$pl['id_poliklinik'] ?>" <?= $filterPoli === (int)$pl['id_poliklinik'] ? 'selected' : '' ?>>
                                <?= e($pl['nama_poliklinik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Kategori Pembiayaan</label>
                    <select name="pembiayaan">
                        <option value="">-- Semua (Asuransi &amp; Umum) --</option>
                        <option value="asuransi" <?= $filterBiaya === 'asuransi' ? 'selected' : '' ?>>Peserta Asuransi</option>
                        <option value="umum" <?= $filterBiaya === 'umum' ? 'selected' : '' ?>>Pasien Umum / Mandiri</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Mitra Asuransi</label>
                    <select name="id_asuransi">
                        <option value="">-- Semua Asuransi --</option>
                        <?php foreach ($listAsr as $as): ?>
                            <option value="<?= (int)$as['id_asuransi'] ?>" <?= $filterAsr === (int)$as['id_asuransi'] ? 'selected' : '' ?>>
                                <?= e($as['nama_asuransi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Status Pasien</label>
                    <select name="status_pasien">
                        <option value="">-- Semua Status --</option>
                        <?php foreach (['Rawat Jalan', 'Rawat Inap', 'Selesai', 'Rujuk'] as $st): ?>
                            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <a href="laporan.php" class="btn btn-outline btn-sm">Reset Filter</a>
                <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter Laporan</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tgl Daftar</th>
                        <th>No. RM / NIK</th>
                        <th>Nama Pasien</th>
                        <th>Poliklinik</th>
                        <th>Dokter Pemeriksa</th>
                        <th>Status Pembiayaan / Asuransi</th>
                        <th>Diagnosa / Keluhan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporanPasien)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:24px;">Tidak ada data pasien yang sesuai filter.</td></tr>
                    <?php else: foreach ($laporanPasien as $ps): ?>
                        <tr>
                            <td><?= e($ps['tanggal_daftar']) ?></td>
                            <td>
                                <strong><?= e($ps['no_rm']) ?></strong><br>
                                <small class="muted"><?= e($ps['nik']) ?></small>
                            </td>
                            <td>
                                <strong><?= e($ps['nama_pasien']) ?></strong> (<?= e($ps['jenis_kelamin']) ?>)<br>
                                <small class="muted"><?= e($ps['no_hp']) ?></small>
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
                            <td><?= e($ps['keluhan_diagnosa']) ?></td>
                            <td><span class="badge"><?= e($ps['status_pasien']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($role === 'ADMIN'): ?>
        <div class="dashboard-panel no-print">
            <div class="panel-heading">
                <h2>4. Riwayat Audit Log Aktivitas Web Service (Server B)</h2>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Tabel</th>
                            <th>Aksi</th>
                            <th>IP Address</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($auditLogs, 0, 15) as $al): ?>
                            <tr>
                                <td><small><?= e($al['waktu']) ?></small></td>
                                <td><strong><?= e($al['nama_lengkap'] ?? 'Sistem') ?></strong> (<code><?= e($al['username'] ?? '-') ?></code>)</td>
                                <td><code><?= e($al['tabel_nama']) ?></code></td>
                                <td><span class="badge"><?= e($al['aksi']) ?></span></td>
                                <td><code><?= e($al['ip_address']) ?></code></td>
                                <td><?= e($al['keterangan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
