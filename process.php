<?php
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Method not allowed']);
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) respond(403, ['error' => 'Invalid request token. Refresh and retry.']);
cleanup($config);
if (!limitRequest($config)) respond(429, ['error' => 'Hourly processing limit reached. Try again later.']);
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) respond(400, ['error' => 'Image upload failed. Check your upload size.']);
$file = $_FILES['image'];
if ($file['size'] < 1 || $file['size'] > $config['max_upload_bytes'] || !is_uploaded_file($file['tmp_name'])) respond(400, ['error' => 'Invalid image size or upload']);
$info = @getimagesize($file['tmp_name']);
if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > $config['max_pixels']) respond(400, ['error' => 'Use a valid JPG, PNG or WebP image within the size limits.']);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $mime !== image_type_to_mime_type($info[2])) respond(400, ['error' => 'Invalid image content']);
if (!function_exists('imagecreatefromstring') || @imagecreatefromstring(file_get_contents($file['tmp_name'])) === false) respond(400, ['error' => 'Image could not be decoded']);
if (empty($config['api_key']) || $config['api_key'] === 'REPLACE_WITH_PIXELCUT_API_KEY') respond(503, ['error' => 'API key is not configured']);
// 1. Read the local temporary file and convert it to Base64
$imageBinary = file_get_contents($file['tmp_name']);
$imageBase64 = base64_encode($imageBinary);

// 2. Prepare the JSON payload structure required by Pixelcut
$payload = json_encode([
    'image_file_b64' => $imageBase64,
    'format'         => 'png' // optional: specify desired output format if needed
]);

$ch = curl_init($config['api_url']);
curl_setopt_array($ch, [
    CURLOPT_POST => false,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-KEY: ' . $config['api_key']
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => $config['timeout'],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_MAXREDIRS => 0
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_errno($ch);
curl_close($ch);
echo json_encode(['response' => $response, 'status' => $status, 'error' => $error]);
if ($response === false || $error) respond(502, ['error' => 'Could not reach the image processing provider']);
if ($status === 401 || $status === 403) respond(502, ['error' => 'Provider authentication failed']);
if ($status === 429) respond(429, ['error' => 'Provider rate limit or quota reached']);
if ($status < 200 || $status >= 300) respond(502, ['error' => 'Provider rejected the image. Check the provider response and API quota.']);
$data = json_decode($response, true);
$imageUrl = $data['result_url'] ?? $data['image_url'] ?? $data['url'] ?? null;
if (!is_string($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) respond(502, ['error' => 'Unexpected provider response format']);
$host = strtolower(parse_url($imageUrl, PHP_URL_HOST) ?? '');
// Restrict retrieval to trusted Pixelcut-controlled hosts; extend only after verifying official responses.
if (!in_array($host, ['cdn3.pixelcut.app', 'cdn.pixelcut.app', 'api.developer.pixelcut.ai'], true) || parse_url($imageUrl, PHP_URL_SCHEME) !== 'https') respond(502, ['error' => 'Untrusted provider image host']);
$ch = curl_init($imageUrl);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_MAXFILESIZE => 16 * 1024 * 1024]);
$bytes = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if (!is_string($bytes) || $http !== 200 || strlen($bytes) > 16 * 1024 * 1024 || @getimagesizefromstring($bytes) === false) respond(502, ['error' => 'Invalid processed image returned']);
$check = @getimagesizefromstring($bytes);
if ($check[2] !== IMAGETYPE_PNG) respond(502, ['error' => 'Provider did not return a PNG']);
$id = bin2hex(random_bytes(20));
file_put_contents($config['storage_dir'] . '/jobs/' . $id . '.png', $bytes, LOCK_EX);
$_SESSION['jobs'][$id] = time() + $config['retention_seconds'];
respond(200, ['url' => 'download.php?id=' . $id, 'expires_in' => $config['retention_seconds']]);
