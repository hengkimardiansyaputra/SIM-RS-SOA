<?php
/**
 * api_pengguna.php - SERVER B Web Service CRUD Tabel Pengguna & Roles (RBAC)
 */
require_once __DIR__ . '/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body   = get_request_body();
$action = $_GET['action'] ?? ($body['action'] ?? '');

// 1. READ (GET)
if ($method === 'GET') {
    if ($action === 'roles') {
        $res = mysqli_query($koneksi, "SELECT * FROM roles ORDER BY id_role ASC");
        $roles = [];
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $roles[] = $r;
            }
        }
        json_response(['status' => true, 'data' => $roles]);
    }

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT p.id_pengguna, p.id_role, p.id_poliklinik, p.username, p.nama_lengkap, p.email, p.no_hp,
                    p.status_aktif, p.last_login, r.kode_role, r.nama_role, pl.nama_poliklinik
             FROM pengguna p
             INNER JOIN roles r ON p.id_role = r.id_role
             LEFT JOIN poliklinik pl ON p.id_poliklinik = pl.id_poliklinik
             WHERE p.id_pengguna = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            json_response(['status' => false, 'message' => 'Data pengguna tidak ditemukan.'], 404);
        }
        json_response(['status' => true, 'data' => $row]);
    }

    $sql = "SELECT
                p.id_pengguna, p.id_role, p.id_poliklinik, p.username, p.nama_lengkap, p.email, p.no_hp,
                p.status_aktif, p.last_login, p.created_at,
                r.kode_role, r.nama_role,
                pl.nama_poliklinik,
                d.nama_dokter AS linked_dokter,
                ps.no_rm AS linked_no_rm
            FROM pengguna p
            INNER JOIN roles r ON p.id_role = r.id_role
            LEFT JOIN poliklinik pl ON p.id_poliklinik = pl.id_poliklinik
            LEFT JOIN dokter d ON d.id_pengguna = p.id_pengguna
            LEFT JOIN pasien ps ON ps.id_pengguna = p.id_pengguna
            ORDER BY p.id_pengguna ASC";
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
    $id_role       = (int)($body['id_role'] ?? 0);
    $id_poliklinik = !empty($body['id_poliklinik']) ? (int)$body['id_poliklinik'] : null;
    $username      = trim((string)($body['username'] ?? ''));
    $password      = (string)($body['password'] ?? '');
    $nama_lengkap  = trim((string)($body['nama_lengkap'] ?? ''));
    $email         = trim((string)($body['email'] ?? ''));
    $no_hp         = trim((string)($body['no_hp'] ?? ''));
    $status        = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($id_role <= 0 || $username === '' || $password === '' || $nama_lengkap === '') {
        json_response(['status' => false, 'message' => 'Role, Username, Password, dan Nama Lengkap wajib diisi.'], 400);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO pengguna (id_role, id_poliklinik, username, password_hash, nama_lengkap, email, no_hp, status_aktif)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iissssss", $id_role, $id_poliklinik, $username, $hash, $nama_lengkap, $email, $no_hp, $status);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menambah pengguna: ' . $err], 400);
    }

    $newId = (int)mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'pengguna', (string)$newId, 'INSERT', "Tambah pengguna $username");

    json_response(['status' => true, 'message' => 'Akun Pengguna berhasil ditambahkan.', 'id' => $newId], 201);
}

// 3. UPDATE
if ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
    $id_pengguna   = (int)($body['id_pengguna'] ?? ($_GET['id'] ?? 0));
    $id_role       = (int)($body['id_role'] ?? 0);
    $id_poliklinik = !empty($body['id_poliklinik']) ? (int)$body['id_poliklinik'] : null;
    $username      = trim((string)($body['username'] ?? ''));
    $password      = (string)($body['password'] ?? '');
    $nama_lengkap  = trim((string)($body['nama_lengkap'] ?? ''));
    $email         = trim((string)($body['email'] ?? ''));
    $no_hp         = trim((string)($body['no_hp'] ?? ''));
    $status        = in_array($body['status_aktif'] ?? 'Aktif', ['Aktif', 'Tidak Aktif'], true) ? $body['status_aktif'] : 'Aktif';

    if ($id_pengguna <= 0 || $id_role <= 0 || $username === '' || $nama_lengkap === '') {
        json_response(['status' => false, 'message' => 'ID, Role, Username, dan Nama Lengkap wajib diisi.'], 400);
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE pengguna
             SET id_role = ?, id_poliklinik = ?, username = ?, password_hash = ?, nama_lengkap = ?, email = ?, no_hp = ?, status_aktif = ?
             WHERE id_pengguna = ?"
        );
        mysqli_stmt_bind_param($stmt, "iissssssi", $id_role, $id_poliklinik, $username, $hash, $nama_lengkap, $email, $no_hp, $status, $id_pengguna);
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE pengguna
             SET id_role = ?, id_poliklinik = ?, username = ?, nama_lengkap = ?, email = ?, no_hp = ?, status_aktif = ?
             WHERE id_pengguna = ?"
        );
        mysqli_stmt_bind_param($stmt, "iisssssi", $id_role, $id_poliklinik, $username, $nama_lengkap, $email, $no_hp, $status, $id_pengguna);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal memperbarui pengguna: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'pengguna', (string)$id_pengguna, 'UPDATE', "Ubah pengguna ID #$id_pengguna ($username)");

    json_response(['status' => true, 'message' => 'Data Pengguna berhasil diperbarui.']);
}

// 4. DELETE
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $id = (int)($body['id_pengguna'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        json_response(['status' => false, 'message' => 'ID Pengguna tidak valid.'], 400);
    }
    if ($id === 1) {
        json_response(['status' => false, 'message' => 'Akun Administrator Utama tidak boleh dihapus.'], 400);
    }

    $stmt = mysqli_prepare($koneksi, "DELETE FROM pengguna WHERE id_pengguna = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
        json_response(['status' => false, 'message' => 'Gagal menghapus pengguna: ' . $err], 400);
    }
    mysqli_stmt_close($stmt);
    catat_audit_log($koneksi, $id_actor, 'pengguna', (string)$id, 'DELETE', "Hapus pengguna ID #$id");

    json_response(['status' => true, 'message' => 'Akun Pengguna berhasil dihapus.']);
}

json_response(['status' => false, 'message' => 'Method atau aksi tidak dikenali.'], 405);
?>
