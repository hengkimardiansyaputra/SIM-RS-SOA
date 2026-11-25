<?php
/**
 * koneksi.php - SERVER B (Server Basisdata & Web Service)
 * Sistem Informasi Manajemen Pasien Rumah Sakit (SIMRS SOA)
 * PHP Native + MySQLi
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host     = "localhost";
$username = "root";
$password = "";
$database = "db_rumah_sakit_soa";
$port     = 3306;

$koneksi = mysqli_connect($host, $username, $password, $database, $port);

if (!$koneksi) {
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => 'Koneksi database Server B gagal: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($koneksi, "utf8mb4");

/**
 * Mengirimkan response JSON standar dari Server B ke Server A
 */
function json_response(array $data, int $http_code = 200): void
{
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($http_code);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Membaca body request baik dari JSON (php://input) maupun form-urlencoded ($_POST)
 */
function get_request_body(): array
{
    $raw = file_get_contents("php://input");
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST ?: [];
}

/**
 * Mencatat aktivitas ke tabel audit_log di Server B
 */
function catat_audit_log(
    mysqli $koneksi,
    ?int $id_pengguna,
    string $tabel_nama,
    ?string $record_id,
    string $aksi,
    ?string $keterangan = null
): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO audit_log (id_pengguna, tabel_nama, record_id, aksi, ip_address, keterangan)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssss", $id_pengguna, $tabel_nama, $record_id, $aksi, $ip, $keterangan);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
