<?php
/**
 * client_api.php - SERVER A (Server Utama Aplikasi UI)
 * Helper komunikasi HTTP RESTful Web Service ke SERVER B menggunakan PHP cURL
 * Tanpa koneksi database langsung di Server A (Murni Arsitektur SOA)
 */

define('SERVER_B_BASE_URL', 'http://localhost/sim_rs_server_b');
define('SERVER_A_NAME', 'SERVER A - UI Aplikasi SIMRS (http://localhost/sim_rs_server_a)');
define('SERVER_B_NAME', 'SERVER B - Database & Web Service (http://localhost/sim_rs_server_b)');

/**
 * Memanggil endpoint Web Service pada Server B
 *
 * @param string $endpoint Nama file endpoint di Server B, misal: 'api_pasien.php'
 * @param string $method   HTTP Method: 'GET', 'POST', 'PUT', 'DELETE'
 * @param array  $data     Body payload (akan dikirim sebagai JSON)
 * @param array  $query    Query string parameter URL
 * @return array           Response array terdekode dari JSON Server B
 */
function call_api(string $endpoint, string $method = 'GET', array $data = [], array $query = []): array
{
    $url = rtrim(SERVER_B_BASE_URL, '/') . '/' . ltrim($endpoint, '/');

    if (!empty($query)) {
        $filteredQuery = array_filter($query, static fn($v) => $v !== null && $v !== '');
        if (!empty($filteredQuery)) {
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $sep . http_build_query($filteredQuery);
        }
    }

    // Sertakan ID pengguna yang sedang login di Server A untuk pencatatan Audit Log di Server B
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['id_pengguna']) && !isset($data['actor_id'])) {
        $data['actor_id'] = (int)$_SESSION['id_pengguna'];
    }

    $method = strtoupper($method);
    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json; charset=UTF-8',
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    }

    $responseBody = curl_exec($ch);
    $httpCode     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false || $curlError !== '') {
        return [
            'status'    => false,
            'http_code' => 500,
            'message'   => 'Gagal menghubungi Web Service Server B: ' . $curlError
        ];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return [
            'status'    => false,
            'http_code' => $httpCode,
            'message'   => 'Response dari Server B bukan JSON yang valid.'
        ];
    }

    $decoded['http_code'] = $httpCode;
    return $decoded;
}

/**
 * Helper escape output HTML
 */
function e($val): string
{
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
