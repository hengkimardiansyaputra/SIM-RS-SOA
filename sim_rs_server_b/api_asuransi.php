<?php
/**
 * api_asuransi.php - SERVER B Web Service CRUD Tabel Asuransi
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? '');

// 1. READ (GET)
if ($method === 'GET' && $action === '') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM asuransi WHERE id_asuransi = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            json_response(['status' => false, 'message' => 'Data asuransi tidak ditemukan.'], 404);
        }
        json_response(['status' => true, 'data' => $row]);
    }

    $sql = "SELECT
                a.*,
                (SELECT COUNT(*) FROM pasien ps WHERE ps.id_asuransi = a.id_asuransi) AS jumlah_pasien
            FROM asuransi a
            ORDER BY a.id_asuransi ASC";
    $res = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
    json_response(['status' => true, 'data' => $rows]);
}

$id_actor = isset($body['actor_id']) ? (int)$body['actor_id'] : null;

// 2. CREATE
if ($method === 'POST' && ($action === '' || $action === 'create')) {
    $kode    = strtoupper(trim((string)($body['kode_asuransi'] ?? '')));
    $nama    = trim((string)($body['nama_asuransi'] ?? ''));
    $jenis   = trim((string)($body['jenis_asuransi'] ?? 'BPJS Kesehatan'));
    $persen  = (float)($body['persen_tanggungan'] ?? 100);
    $plafon  = (float)($body['plafon_maksimal'] ?? 0);
    $telepon = trim((string)($body['telepon_callcenter'] ?? ''));
    $status  = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($kode === '' || $nama === '') {
        json_response(['status' => false, 'message' => 'Kode dan Nama Asuransi wajib diisi.'], 400);
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO asuransi (kode_asuransi, nama_asuransi, jenis_asuransi, persen_tanggungan, plafon_maksimal, telepon_callcenter, status_aktif)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "sssddss", $kode, $nama, $jenis, $persen, $plafon, $telepon, $status);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menambah asuransi: ' . $err], 400);
    }

    $newId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'asuransi', (string)$newId, 'INSERT', "Tambah asuransi $kode - $nama");

    json_response(['status' => true, 'message' => 'Data Asuransi berhasil ditambahkan.', 'id' => $newId], 201);
}

// 3. UPDATE
if ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
    $id      = (int)($body['id_asuransi'] ?? ($_GET['id'] ?? 0));
    $kode    = strtoupper(trim((string)($body['kode_asuransi'] ?? '')));
    $nama    = trim((string)($body['nama_asuransi'] ?? ''));
    $jenis   = trim((string)($body['jenis_asuransi'] ?? 'BPJS Kesehatan'));
    $persen  = (float)($body['persen_tanggungan'] ?? 100);
    $plafon  = (float)($body['plafon_maksimal'] ?? 0);
    $telepon = trim((string)($body['telepon_callcenter'] ?? ''));
    $status  = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($id <= 0 || $kode === '' || $nama === '') {
        json_response(['status' => false, 'message' => 'ID, Kode, dan Nama Asuransi wajib diisi.'], 400);
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE asuransi
         SET kode_asuransi = ?, nama_asuransi = ?, jenis_asuransi = ?, persen_tanggungan = ?, plafon_maksimal = ?, telepon_callcenter = ?, status_aktif = ?
         WHERE id_asuransi = ?"
    );
    mysqli_stmt_bind_param($stmt, "sssddssi", $kode, $nama, $jenis, $persen, $plafon, $telepon, $status, $id);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal memperbarui asuransi: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'asuransi', (string)$id, 'UPDATE', "Ubah asuransi ID #$id ($kode)");

    json_response(['status' => true, 'message' => 'Data Asuransi berhasil diperbarui.']);
}

// 4. DELETE
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $id = (int)($body['id_asuransi'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        json_response(['status' => false, 'message' => 'ID Asuransi tidak valid.'], 400);
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM asuransi WHERE id_asuransi = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menghapus asuransi: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'asuransi', (string)$id, 'DELETE', "Hapus asuransi ID #$id");

    json_response(['status' => true, 'message' => 'Data Asuransi berhasil dihapus.']);
}

json_response(['status' => false, 'message' => 'Method atau aksi tidak dikenali.'], 405);
?>
