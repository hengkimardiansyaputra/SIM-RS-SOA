<?php
/**
 * api_poliklinik.php - SERVER B Web Service CRUD Tabel Poliklinik
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? '');

// 1. READ (GET)
if ($method === 'GET' && $action === '') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM poliklinik WHERE id_poliklinik = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            json_response(['status' => false, 'message' => 'Data poliklinik tidak ditemukan.'], 404);
        }
        json_response(['status' => true, 'data' => $row]);
    }

    $sql = "SELECT
                pl.*,
                (SELECT COUNT(*) FROM dokter d WHERE d.id_poliklinik = pl.id_poliklinik) AS jumlah_dokter,
                (SELECT COUNT(*) FROM pasien ps WHERE ps.id_poliklinik = pl.id_poliklinik) AS jumlah_pasien
            FROM poliklinik pl
            ORDER BY pl.id_poliklinik ASC";
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

// 2. CREATE (POST action=create atau POST biasa)
if (($method === 'POST' && ($action === '' || $action === 'create'))) {
    $kode   = strtoupper(trim((string)($body['kode_poliklinik'] ?? '')));
    $nama   = trim((string)($body['nama_poliklinik'] ?? ''));
    $gedung = trim((string)($body['gedung_lantai'] ?? ''));
    $jam    = trim((string)($body['jam_operasional'] ?? '08:00 - 16:00 WIB'));
    $ket    = trim((string)($body['keterangan'] ?? ''));
    $status = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($kode === '' || $nama === '') {
        json_response(['status' => false, 'message' => 'Kode dan Nama Poliklinik wajib diisi.'], 400);
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO poliklinik (kode_poliklinik, nama_poliklinik, gedung_lantai, jam_operasional, keterangan, status_aktif)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "ssssss", $kode, $nama, $gedung, $jam, $ket, $status);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menambah poliklinik: ' . $err], 400);
    }

    $newId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'poliklinik', (string)$newId, 'INSERT', "Tambah poliklinik $kode - $nama");

    json_response(['status' => true, 'message' => 'Data Poliklinik berhasil ditambahkan.', 'id' => $newId], 201);
}

// 3. UPDATE (PUT atau POST action=update)
if ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
    $id     = (int)($body['id_poliklinik'] ?? ($_GET['id'] ?? 0));
    $kode   = strtoupper(trim((string)($body['kode_poliklinik'] ?? '')));
    $nama   = trim((string)($body['nama_poliklinik'] ?? ''));
    $gedung = trim((string)($body['gedung_lantai'] ?? ''));
    $jam    = trim((string)($body['jam_operasional'] ?? ''));
    $ket    = trim((string)($body['keterangan'] ?? ''));
    $status = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($id <= 0 || $kode === '' || $nama === '') {
        json_response(['status' => false, 'message' => 'ID, Kode, dan Nama Poliklinik wajib diisi.'], 400);
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE poliklinik
         SET kode_poliklinik = ?, nama_poliklinik = ?, gedung_lantai = ?, jam_operasional = ?, keterangan = ?, status_aktif = ?
         WHERE id_poliklinik = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssssi", $kode, $nama, $gedung, $jam, $ket, $status, $id);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal memperbarui poliklinik: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'poliklinik', (string)$id, 'UPDATE', "Ubah poliklinik ID #$id ($kode)");

    json_response(['status' => true, 'message' => 'Data Poliklinik berhasil diperbarui.']);
}

// 4. DELETE (DELETE atau POST action=delete)
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $id = (int)($body['id_poliklinik'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        json_response(['status' => false, 'message' => 'ID Poliklinik tidak valid.'], 400);
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM poliklinik WHERE id_poliklinik = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Tidak dapat menghapus Poliklinik karena masih terkait dengan Dokter/Pasien: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'poliklinik', (string)$id, 'DELETE', "Hapus poliklinik ID #$id");

    json_response(['status' => true, 'message' => 'Data Poliklinik berhasil dihapus.']);
}

json_response(['status' => false, 'message' => 'Method atau aksi tidak dikenali.'], 405);
?>
