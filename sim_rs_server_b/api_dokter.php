<?php
/**
 * api_dokter.php - SERVER B Web Service CRUD Tabel Dokter
 * Mendukung fitur: Dokter (Bisa menjadi Pengguna Aplikasi)
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? '');

// 1. READ (GET)
if ($method === 'GET' && $action === '') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM v_dokter_lengkap WHERE id_dokter = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            json_response(['status' => false, 'message' => 'Data dokter tidak ditemukan.'], 404);
        }
        json_response(['status' => true, 'data' => $row]);
    }

    $where = [];
    $params = [];
    $types = '';

    if (!empty($_GET['id_poliklinik'])) {
        $where[]  = "id_poliklinik = ?";
        $params[] = (int)$_GET['id_poliklinik'];
        $types   .= "i";
    }
    if (!empty($_GET['id_pengguna'])) {
        $where[]  = "id_pengguna = ?";
        $params[] = (int)$_GET['id_pengguna'];
        $types   .= "i";
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT * FROM v_dokter_lengkap $whereSql ORDER BY id_dokter ASC";

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

// 2. CREATE (Tambah Dokter + Opsi Buat Akun Pengguna Role DOKTER)
if ($method === 'POST' && ($action === '' || $action === 'create')) {
    $id_poliklinik = (int)($body['id_poliklinik'] ?? 0);
    $nip_dokter    = trim((string)($body['nip_dokter'] ?? ''));
    $no_sip        = trim((string)($body['no_sip'] ?? ''));
    $nama_dokter   = trim((string)($body['nama_dokter'] ?? ''));
    $spesialisasi  = trim((string)($body['spesialisasi'] ?? 'Dokter Umum'));
    $jk            = in_array($body['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $body['jenis_kelamin'] : 'L';
    $no_hp         = trim((string)($body['no_hp'] ?? ''));
    $email         = trim((string)($body['email'] ?? ''));
    $jadwal        = trim((string)($body['jadwal_praktik'] ?? ''));
    $status        = in_array($body['status_dokter'] ?? 'Aktif', ['Aktif', 'Cuti', 'Tidak Aktif'], true) ? $body['status_dokter'] : 'Aktif';

    $buat_akun     = !empty($body['buat_akun']);
    $username_akun = trim((string)($body['username_akun'] ?? ''));
    $password_akun = (string)($body['password_akun'] ?? 'password');

    if ($id_poliklinik <= 0 || $nip_dokter === '' || $no_sip === '' || $nama_dokter === '') {
        json_response(['status' => false, 'message' => 'Poliklinik, NIP, No. SIP, dan Nama Dokter wajib diisi.'], 400);
    }

    mysqli_begin_transaction($koneksi);
    try {
        $id_pengguna = !empty($body['id_pengguna']) ? (int)$body['id_pengguna'] : null;

        if ($buat_akun && $username_akun !== '') {
            $hash = password_hash($password_akun !== '' ? $password_akun : 'password', PASSWORD_BCRYPT);
            $id_role_dokter = 3; // Role DOKTER
            $stmtUser = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengguna (id_role, id_poliklinik, username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')"
            );
            mysqli_stmt_bind_param($stmtUser, "iisssss", $id_role_dokter, $id_poliklinik, $username_akun, $hash, $nama_dokter, $email, $no_hp);
            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception('Gagal membuat akun pengguna dokter: ' . mysqli_error($koneksi));
            }
            $id_pengguna = (int)mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmtUser);
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO dokter (id_pengguna, id_poliklinik, nip_dokter, no_sip, nama_dokter, spesialisasi, jenis_kelamin, no_hp, email, jadwal_praktik, status_dokter)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iisssssssss",
            $id_pengguna,
            $id_poliklinik,
            $nip_dokter,
            $no_sip,
            $nama_dokter,
            $spesialisasi,
            $jk,
            $no_hp,
            $email,
            $jadwal,
            $status
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal menyimpan data dokter: ' . mysqli_error($koneksi));
        }

        $newId = (int)mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);

        mysqli_commit($koneksi);
        catat_audit_log($koneksi, $id_actor, 'dokter', (string)$newId, 'INSERT', "Tambah dokter $nama_dokter ($nip_dokter)");

        json_response(['status' => true, 'message' => 'Data Dokter berhasil ditambahkan.', 'id' => $newId], 201);
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        json_response(['status' => false, 'message' => $e->getMessage()], 400);
    }
}

// 3. UPDATE
if ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
    $id_dokter     = (int)($body['id_dokter'] ?? ($_GET['id'] ?? 0));
    $id_poliklinik = (int)($body['id_poliklinik'] ?? 0);
    $nip_dokter    = trim((string)($body['nip_dokter'] ?? ''));
    $no_sip        = trim((string)($body['no_sip'] ?? ''));
    $nama_dokter   = trim((string)($body['nama_dokter'] ?? ''));
    $spesialisasi  = trim((string)($body['spesialisasi'] ?? ''));
    $jk            = in_array($body['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $body['jenis_kelamin'] : 'L';
    $no_hp         = trim((string)($body['no_hp'] ?? ''));
    $email         = trim((string)($body['email'] ?? ''));
    $jadwal        = trim((string)($body['jadwal_praktik'] ?? ''));
    $status        = in_array($body['status_dokter'] ?? 'Aktif', ['Aktif', 'Cuti', 'Tidak Aktif'], true) ? $body['status_dokter'] : 'Aktif';

    $buat_akun     = !empty($body['buat_akun']);
    $username_akun = trim((string)($body['username_akun'] ?? ''));
    $password_akun = (string)($body['password_akun'] ?? '');

    if ($id_dokter <= 0 || $id_poliklinik <= 0 || $nip_dokter === '' || $nama_dokter === '') {
        json_response(['status' => false, 'message' => 'ID, Poliklinik, NIP, dan Nama Dokter wajib diisi.'], 400);
    }

    mysqli_begin_transaction($koneksi);
    try {
        // Cek apakah dokter sudah punya id_pengguna
        $resOld = mysqli_query($koneksi, "SELECT id_pengguna FROM dokter WHERE id_dokter = $id_dokter");
        $rowOld = $resOld ? mysqli_fetch_assoc($resOld) : null;
        $id_pengguna = !empty($rowOld['id_pengguna']) ? (int)$rowOld['id_pengguna'] : null;

        if ($id_pengguna === null && $buat_akun && $username_akun !== '') {
            $hash = password_hash($password_akun !== '' ? $password_akun : 'password', PASSWORD_BCRYPT);
            $id_role_dokter = 3;
            $stmtUser = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengguna (id_role, id_poliklinik, username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')"
            );
            mysqli_stmt_bind_param($stmtUser, "iisssss", $id_role_dokter, $id_poliklinik, $username_akun, $hash, $nama_dokter, $email, $no_hp);
            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception('Gagal membuat akun pengguna dokter: ' . mysqli_error($koneksi));
            }
            $id_pengguna = (int)mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmtUser);
        } elseif ($id_pengguna !== null) {
            // Sinkronkan nama & poliklinik pada akun pengguna dokter
            $stmtUpdU = mysqli_prepare($koneksi, "UPDATE pengguna SET id_poliklinik = ?, nama_lengkap = ?, email = ?, no_hp = ? WHERE id_pengguna = ?");
            mysqli_stmt_bind_param($stmtUpdU, "isssi", $id_poliklinik, $nama_dokter, $email, $no_hp, $id_pengguna);
            mysqli_stmt_execute($stmtUpdU);
            mysqli_stmt_close($stmtUpdU);
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE dokter
             SET id_pengguna = ?, id_poliklinik = ?, nip_dokter = ?, no_sip = ?, nama_dokter = ?, spesialisasi = ?,
                 jenis_kelamin = ?, no_hp = ?, email = ?, jadwal_praktik = ?, status_dokter = ?
             WHERE id_dokter = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iisssssssssi",
            $id_pengguna,
            $id_poliklinik,
            $nip_dokter,
            $no_sip,
            $nama_dokter,
            $spesialisasi,
            $jk,
            $no_hp,
            $email,
            $jadwal,
            $status,
            $id_dokter
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal memperbarui dokter: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($koneksi);
        catat_audit_log($koneksi, $id_actor, 'dokter', (string)$id_dokter, 'UPDATE', "Ubah dokter ID #$id_dokter ($nama_dokter)");

        json_response(['status' => true, 'message' => 'Data Dokter berhasil diperbarui.']);
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        json_response(['status' => false, 'message' => $e->getMessage()], 400);
    }
}

// 4. DELETE
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $id = (int)($body['id_dokter'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        json_response(['status' => false, 'message' => 'ID Dokter tidak valid.'], 400);
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM dokter WHERE id_dokter = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menghapus dokter: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'dokter', (string)$id, 'DELETE', "Hapus dokter ID #$id");

    json_response(['status' => true, 'message' => 'Data Dokter berhasil dihapus.']);
}

json_response(['status' => false, 'message' => 'Method atau aksi tidak dikenali.'], 405);
?>
