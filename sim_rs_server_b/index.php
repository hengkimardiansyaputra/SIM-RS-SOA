<?php
/**
 * index.php - Portal Status & Dokumentasi Endpoint SERVER B
 * Server Basisdata dan Web Service SIMRS SOA
 */
require_once __DIR__ . '/koneksi.php';

$endpoints = [
    ['file' => 'api_auth.php',       'method' => 'POST',                 'deskripsi' => 'Web Service Login, Verifikasi RBAC, dan Logout Pengguna'],
    ['file' => 'api_dashboard.php',  'method' => 'GET',                  'deskripsi' => 'Web Service Statistik Dashboard & Profil sesuai Scope RBAC'],
    ['file' => 'api_poliklinik.php', 'method' => 'GET, POST, PUT, DELETE', 'deskripsi' => 'Web Service CRUD Data Poliklinik Rumah Sakit'],
    ['file' => 'api_asuransi.php',   'method' => 'GET, POST, PUT, DELETE', 'deskripsi' => 'Web Service CRUD Data Penjamin / Asuransi Pasien'],
    ['file' => 'api_dokter.php',     'method' => 'GET, POST, PUT, DELETE', 'deskripsi' => 'Web Service CRUD Data Dokter & Integrasi Akun Pengguna Dokter'],
    ['file' => 'api_pasien.php',     'method' => 'GET, POST, PUT, DELETE', 'deskripsi' => 'Web Service CRUD Data Pasien, Asuransi & Akun Pengguna Pasien'],
    ['file' => 'api_pengguna.php',   'method' => 'GET, POST, PUT, DELETE', 'deskripsi' => 'Web Service CRUD Manajemen Pengguna & Role RBAC'],
    ['file' => 'api_laporan.php',    'method' => 'GET',                  'deskripsi' => 'Web Service Laporan Rekapitulasi Poliklinik, Asuransi, Pasien & Audit Log'],
];

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    json_response([
        'status'    => true,
        'server'    => 'SERVER B - Database & Web Service SIMRS SOA',
        'ip_host'   => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
        'folder'    => 'C:/xampp/htdocs/sim_rs_server_b',
        'database'  => 'db_rumah_sakit_soa',
        'endpoints' => $endpoints
    ]);
}

$counts = [];
foreach (['poliklinik', 'asuransi', 'pengguna', 'dokter', 'pasien', 'roles', 'audit_log'] as $tbl) {
    $res = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$tbl`");
    $row = $res ? mysqli_fetch_assoc($res) : ['total' => 0];
    $counts[$tbl] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERVER B - Basisdata & Web Service SIMRS SOA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 32px; }
        .container { max-width: 1080px; margin: 0 auto; }
        .header-card { background: linear-gradient(135deg, #1e293b, #0f766e); border: 1px solid #334155; border-radius: 16px; padding: 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .badge-online { background: #10b981; color: #052e16; padding: 6px 14px; border-radius: 999px; font-weight: 800; font-size: 13px; }
        h1 { margin: 8px 0; font-size: 26px; }
        .meta { color: #cbd5e1; font-size: 14px; line-height: 1.6; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .stat { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 16px; text-align: center; }
        .stat span { display: block; font-size: 12px; color: #94a3b8; text-transform: uppercase; }
        .stat strong { display: block; font-size: 24px; color: #38bdf8; margin-top: 6px; }
        .panel { background: #1e293b; border: 1px solid #334155; border-radius: 14px; overflow: hidden; }
        .panel-head { padding: 18px 22px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 18px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { background: #0f172a; color: #94a3b8; font-size: 12px; text-transform: uppercase; }
        code { background: #0f172a; color: #38bdf8; padding: 4px 8px; border-radius: 6px; font-family: Consolas, monospace; }
        .method { background: #065f46; color: #a7f3d0; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        a.btn { background: #0ea5e9; color: #fff; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-card">
        <div>
            <span class="badge-online">● SERVER B AKTIF (ONLINE)</span>
            <h1>Server B — Server Basisdata &amp; RESTful Web Service</h1>
            <div class="meta">
                <strong>IP Address / Host:</strong> <code><?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? '127.0.0.1') ?> (localhost)</code> &nbsp;|&nbsp;
                <strong>Folder:</strong> <code>C:\xampp\htdocs\sim_rs_server_b</code> &nbsp;|&nbsp;
                <strong>Database:</strong> <code>db_rumah_sakit_soa</code>
            </div>
        </div>
        <a class="btn" href="?format=json">Lihat JSON Metadata</a>
    </div>

    <div class="grid">
        <?php foreach ($counts as $tbl => $tot): ?>
            <div class="stat">
                <span>Tabel <?= htmlspecialchars($tbl) ?></span>
                <strong><?= $tot ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <div class="panel-head">
            <strong>Daftar Layanan Web Service (API Endpoints) untuk Server A</strong>
            <span style="color:#94a3b8;font-size:13px;">Format Pertukaran Data: JSON</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Endpoint File</th>
                    <th>HTTP Method</th>
                    <th>Deskripsi Layanan SOA</th>
                    <th>Uji Endpoint</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($endpoints as $ep): ?>
                    <tr>
                        <td><code>/sim_rs_server_b/<?= htmlspecialchars($ep['file']) ?></code></td>
                        <td><span class="method"><?= htmlspecialchars($ep['method']) ?></span></td>
                        <td><?= htmlspecialchars($ep['deskripsi']) ?></td>
                        <td><a class="btn" href="<?= htmlspecialchars($ep['file']) ?>" target="_blank">Buka JSON</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
