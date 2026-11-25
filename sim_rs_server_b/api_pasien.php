<?php
/**
 * api_pasien.php - SERVER B Web Service CRUD Tabel Pasien
 * Mendukung fitur:
 * - Pasien (Bisa menjadi Pengguna Aplikasi) via FK id_pengguna
 * - Asuransi (Bila ada pasien yang menggunakan Asuransi) via FK id_asuransi
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? '');

// 1. READ (GET)
if ($method === 'GET' && $action === '') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM v_pasien_lengkap WHERE id_pasien = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            json_response(['status' => false, 'message' => 'Data pasien tidak ditemukan.'], 404);
        }
        json_response(['status' => true, 'data' => $row]);
    }

    $where  = [];
    $params = [];
    $types  = '';

    if (!empty($_GET['id_poliklinik'])) {
        $where[]  = "id_poliklinik = ?";
        $params[] = (int)$_GET['id_poliklinik'];
        $types   .= "i";
    }
    if (!empty($_GET['id_dokter'])) {
        $where[]  = "id_dokter = ?";
        $params[] = (int)$_GET['id_dokter'];
        $types   .= "i";
    }
    if (!empty($_GET['id_pengguna'])) {
        $where[]  = "id_pengguna = ?";
        $params[] = (int)$_GET['id_pengguna'];
        $types   .= "i";
    }
    if (!empty($_GET['id_asuransi'])) {
        $where[]  = "id_asuransi = ?";
        $params[] = (int)$_GET['id_asuransi'];
        $types   .= "i";
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT * FROM v_pasien_lengkap $whereSql ORDER BY id_pasien DESC";

    $stmt = mysqli_prepare($koneksi, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }
    mysqli_stmt_close($stmt);

    json_response(['status' => true, 'data' => $rows]);
}

$id_actor = isset($body['actor_id']) ? (int)$body['actor_id'] : null;

// 2. CREATE (Tambah Pasien + Dukungan Asuransi Opsional + Opsi Akun Pengguna Pasien)
if ($method === 'POST' && ($action === '' || $action === 'create')) {
    $id_poliklinik     = (int)($body['id_poliklinik'] ?? 0);
    $id_dokter         = !empty($body['id_dokter']) ? (int)$body['id_dokter'] : null;
    $id_asuransi       = !empty($body['id_asuransi']) ? (int)$body['id_asuransi'] : null;
    $no_rm             = strtoupper(trim((string)($body['no_rm'] ?? '')));
    $nik               = trim((string)($body['nik'] ?? ''));
    $no_kartu_asuransi = $id_asuransi ? trim((string)($body['no_kartu_asuransi'] ?? '')) : null;
    $nama_pasien       = trim((string)($body['nama_pasien'] ?? ''));
    $jk                = in_array($body['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $body['jenis_kelamin'] : 'L';
    $tempat_lahir      = trim((string)($body['tempat_lahir'] ?? ''));
    $tanggal_lahir     = trim((string)($body['tanggal_lahir'] ?? ''));
    $gol_darah         = in_array($body['golongan_darah'] ?? '-', ['A', 'B', 'AB', 'O', '-'], true) ? $body['golongan_darah'] : '-';
    $no_hp             = trim((string)($body['no_hp'] ?? ''));
    $alamat            = trim((string)($body['alamat'] ?? ''));
    $keluhan           = trim((string)($body['keluhan_diagnosa'] ?? ''));
    $tanggal_daftar    = trim((string)($body['tanggal_daftar'] ?? date('Y-m-d')));
    $status_pasien     = in_array($body['status_pasien'] ?? 'Rawat Jalan', ['Rawat Jalan', 'Rawat Inap', 'Selesai', 'Rujuk'], true)
                         ? $body['status_pasien'] : 'Rawat Jalan';

    $buat_akun     = !empty($body['buat_akun']);
    $username_akun = trim((string)($body['username_akun'] ?? $no_rm));
    $password_akun = (string)($body['password_akun'] ?? 'password');

    if ($id_poliklinik <= 0 || $no_rm === '' || $nik === '' || $nama_pasien === '' || $tanggal_lahir === '') {
        json_response(['status' => false, 'message' => 'No. RM, NIK, Nama Pasien, Tanggal Lahir, dan Poliklinik wajib diisi.'], 400);
    }

    mysqli_begin_transaction($koneksi);
    try {
        $id_pengguna = !empty($body['id_pengguna']) ? (int)$body['id_pengguna'] : null;

        if ($buat_akun && $username_akun !== '') {
            $hash = password_hash($password_akun !== '' ? $password_akun : 'password', PASSWORD_BCRYPT);
            $id_role_pasien = 5; // Role PASIEN
            $email_pasien = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $no_rm)) . '@pasien.rs-umb.ac.id';
            $stmtUser = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengguna (id_role, id_poliklinik, username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')"
            );
            mysqli_stmt_bind_param($stmtUser, "iisssss", $id_role_pasien, $id_poliklinik, $username_akun, $hash, $nama_pasien, $email_pasien, $no_hp);
            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception('Gagal membuat akun pengguna pasien: ' . mysqli_error($koneksi));
            }
            $id_pengguna = (int)mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmtUser);
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO pasien (
                id_pengguna, id_poliklinik, id_dokter, id_asuransi,
                no_rm, nik, no_kartu_asuransi, nama_pasien, jenis_kelamin,
                tempat_lahir, tanggal_lahir, golongan_darah, no_hp, alamat,
                keluhan_diagnosa, tanggal_daftar, status_pasien
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iiiisssssssssssss",
            $id_pengguna,
            $id_poliklinik,
            $id_dokter,
            $id_asuransi,
            $no_rm,
            $nik,
            $no_kartu_asuransi,
            $nama_pasien,
            $jk,
            $tempat_lahir,
            $tanggal_lahir,
            $gol_darah,
            $no_hp,
            $alamat,
            $keluhan,
            $tanggal_daftar,
            $status_pasien
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal menyimpan data pasien: ' . mysqli_error($koneksi));
        }

        $newId = (int)mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);

        mysqli_commit($koneksi);
        catat_audit_log($koneksi, $id_actor, 'pasien', (string)$newId, 'INSERT', "Tambah pasien $no_rm - $nama_pasien");

        json_response(['status' => true, 'message' => 'Data Pasien berhasil ditambahkan.', 'id' => $newId], 201);
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        json_response(['status' => false, 'message' => $e->getMessage()], 400);
    }
}

// 3. UPDATE
if ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
    $id_pasien         = (int)($body['id_pasien'] ?? ($_GET['id'] ?? 0));
    $id_poliklinik     = (int)($body['id_poliklinik'] ?? 0);
    $id_dokter         = !empty($body['id_dokter']) ? (int)$body['id_dokter'] : null;
    $id_asuransi       = !empty($body['id_asuransi']) ? (int)$body['id_asuransi'] : null;
    $no_rm             = strtoupper(trim((string)($body['no_rm'] ?? '')));
    $nik               = trim((string)($body['nik'] ?? ''));
    $no_kartu_asuransi = $id_asuransi ? trim((string)($body['no_kartu_asuransi'] ?? '')) : null;
    $nama_pasien       = trim((string)($body['nama_pasien'] ?? ''));
    $jk                = in_array($body['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $body['jenis_kelamin'] : 'L';
    $tempat_lahir      = trim((string)($body['tempat_lahir'] ?? ''));
    $tanggal_lahir     = trim((string)($body['tanggal_lahir'] ?? ''));
    $gol_darah         = in_array($body['golongan_darah'] ?? '-', ['A', 'B', 'AB', 'O', '-'], true) ? $body['golongan_darah'] : '-';
    $no_hp             = trim((string)($body['no_hp'] ?? ''));
    $alamat            = trim((string)($body['alamat'] ?? ''));
    $keluhan           = trim((string)($body['keluhan_diagnosa'] ?? ''));
    $tanggal_daftar    = trim((string)($body['tanggal_daftar'] ?? date('Y-m-d')));
    $status_pasien     = in_array($body['status_pasien'] ?? 'Rawat Jalan', ['Rawat Jalan', 'Rawat Inap', 'Selesai', 'Rujuk'], true)
                         ? $body['status_pasien'] : 'Rawat Jalan';

    $buat_akun     = !empty($body['buat_akun']);
    $username_akun = trim((string)($body['username_akun'] ?? $no_rm));
    $password_akun = (string)($body['password_akun'] ?? '');

    if ($id_pasien <= 0 || $id_poliklinik <= 0 || $no_rm === '' || $nama_pasien === '') {
        json_response(['status' => false, 'message' => 'ID, Poliklinik, No. RM, dan Nama Pasien wajib diisi.'], 400);
    }

    mysqli_begin_transaction($koneksi);
    try {
        $resOld = mysqli_query($koneksi, "SELECT id_pengguna FROM pasien WHERE id_pasien = $id_pasien");
        $rowOld = $resOld ? mysqli_fetch_assoc($resOld) : null;
        $id_pengguna = !empty($rowOld['id_pengguna']) ? (int)$rowOld['id_pengguna'] : null;

        if ($id_pengguna === null && $buat_akun && $username_akun !== '') {
            $hash = password_hash($password_akun !== '' ? $password_akun : 'password', PASSWORD_BCRYPT);
            $id_role_pasien = 5;
            $email_pasien = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $no_rm)) . '@pasien.rs-umb.ac.id';
            $stmtUser = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengguna (id_role, id_poliklinik, username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')"
            );
            mysqli_stmt_bind_param($stmtUser, "iisssss", $id_role_pasien, $id_poliklinik, $username_akun, $hash, $nama_pasien, $email_pasien, $no_hp);
            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception('Gagal membuat akun pengguna pasien: ' . mysqli_error($koneksi));
            }
            $id_pengguna = (int)mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmtUser);
        } elseif ($id_pengguna !== null) {
            $stmtUpdU = mysqli_prepare($koneksi, "UPDATE pengguna SET id_poliklinik = ?, nama_lengkap = ?, no_hp = ? WHERE id_pengguna = ?");
            mysqli_stmt_bind_param($stmtUpdU, "issi", $id_poliklinik, $nama_pasien, $no_hp, $id_pengguna);
            mysqli_stmt_execute($stmtUpdU);
            mysqli_stmt_close($stmtUpdU);
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE pasien
             SET id_pengguna = ?, id_poliklinik = ?, id_dokter = ?, id_asuransi = ?,
                 no_rm = ?, nik = ?, no_kartu_asuransi = ?, nama_pasien = ?, jenis_kelamin = ?,
                 tempat_lahir = ?, tanggal_lahir = ?, golongan_darah = ?, no_hp = ?, alamat = ?,
                 keluhan_diagnosa = ?, tanggal_daftar = ?, status_pasien = ?
             WHERE id_pasien = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iiiisssssssssssssi",
            $id_pengguna,
            $id_poliklinik,
            $id_dokter,
            $id_asuransi,
            $no_rm,
            $nik,
            $no_kartu_asuransi,
            $nama_pasien,
            $jk,
            $tempat_lahir,
            $tanggal_lahir,
            $gol_darah,
            $no_hp,
            $alamat,
            $keluhan,
            $tanggal_daftar,
            $status_pasien,
            $id_pasien
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal memperbarui pasien: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($koneksi);
        catat_audit_log($koneksi, $id_actor, 'pasien', (string)$id_pasien, 'UPDATE', "Ubah pasien ID #$id_pasien ($no_rm)");

        json_response(['status' => true, 'message' => 'Data Pasien berhasil diperbarui.']);
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        json_response(['status' => false, 'message' => $e->getMessage()], 400);
    }
}

// 4. DELETE
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $id = (int)($body['id_pasien'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        json_response(['status' => false, 'message' => 'ID Pasien tidak valid.'], 400);
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM pasien WHERE id_pasien = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menghapus pasien: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'pasien', (string)$id, 'DELETE', "Hapus pasien ID #$id");

    json_response(['status' => true, 'message' => 'Data Pasien berhasil dihapus.']);
}

json_response(['status' => false, 'message' => 'Method atau aksi tidak dikenali.'], 405);
?>
