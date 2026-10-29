<?php

declare(strict_types=1);
ini_set('display_errors', 1); // Enable error display
ini_set('display_startup_errors', 1); // Show startup errors
error_reporting(E_ALL); // Report all types of errors
require __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = [
        'ok' => false,
        'status' => null,
        'duration' => null,
        'curl_error' => null,
        'response' => null,
    ];
   
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $result['curl_error'] = 'Invalid request token. Refresh and retry.';
    } elseif (empty($config['api_key']) || $config['api_key'] === 'REPLACE_WITH_PIXELCUT_API_KEY') {
        $result['curl_error'] = 'API key is not configured.';
    } elseif (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $result['curl_error'] = 'Upload an image before testing the API.';
    } else {
        
        $file = $_FILES['image'];
        $info = @getimagesize($file['tmp_name']);
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        $mime = is_string($file['tmp_name'])
            ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])
            : false;

        if (
            $file['size'] < 1 ||
            $file['size'] > $config['max_upload_bytes'] ||
            !is_uploaded_file($file['tmp_name']) ||
            !$info ||
            !in_array($info[2], $allowedTypes, true) ||
            $info[0] < 1 ||
            $info[1] < 1 ||
            $info[0] * $info[1] > $config['max_pixels'] ||
            !is_string($mime) ||
            $mime !== image_type_to_mime_type($info[2])
        ) {
            $result['curl_error'] = 'Use a valid JPEG, PNG, or WebP image within the configured limits.';
        } elseif (!function_exists('imagecreatefromstring') || @imagecreatefromstring(file_get_contents($file['tmp_name'])) === false) {
            $result['curl_error'] = 'The uploaded image could not be decoded.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            $imageData = base64_encode(file_get_contents($file['tmp_name']));

            $payload = json_encode([
                'image_url' => 'data:' . $mime . ';base64,' . $imageData,
                'format' => 'png',
                'crop' => false,
                'margin' => '100px',
                'margin_left' => '50px',
                'margin_right' => '50px',
                'margin_top' => '50px',
                'margin_bottom' => '50px',
                'shadow' => [
                    'enabled' => false,
                    'opacity' => 0.5,
                    'light_source' => [
                        'size' => 0.3,
                        'position' => [
                            'x' => -0.3,
                            'y' => -0.6,
                            'z' => 0.7,
                        ],
                    ],
                ],
            ]);
            
          
            if (!is_string($payload)) {
                $result['curl_error'] = 'Could not create the API request payload.';
            } else {
                $curl = curl_init($config['api_url']);
                if ($curl === false) {
                    $result['curl_error'] = 'Could not initialize cURL.';
                } else {
                    $started = microtime(true);
                    curl_setopt_array($curl, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $payload,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'Accept: application/json',
                            'X-API-KEY: ' . $config['api_key'],
                        ],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => $config['timeout'],
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_MAXREDIRS => 0,
                    ]);
                    $response = curl_exec($curl);
                    $result['duration'] = round((microtime(true) - $started) * 1000) . ' ms';
                    $result['status'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($curl);
                    $result['curl_error'] = $curlError !== '' ? $curlError : null;
                    curl_close($curl);
                    echo $response;
                    if (is_string($response)) {
                        $decoded = json_decode($response, true);
                        
                        $result['response'] = is_array($decoded)
                            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                            : $response;
                        $result['ok'] = $result['curl_error'] === null && $result['status'] >= 200 && $result['status'] < 300;
                    }
                }
            }
        }
    }
}

function testHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pixelcut cURL API Test</title>
    <style>
        body { max-width: 760px; margin: 40px auto; padding: 0 20px; font: 16px/1.5 Arial, sans-serif; color: #1f2937; }
        h1 { margin-bottom: 8px; }
        .note { color: #6b7280; }
        form, .result { border: 1px solid #d1d5db; border-radius: 8px; padding: 20px; margin-top: 24px; }
        input, button { font: inherit; }
        button { cursor: pointer; padding: 9px 16px; border: 0; border-radius: 5px; background: #2563eb; color: white; }
        .result { background: #f9fafb; }
        .success { color: #166534; }
        .failure { color: #991b1b; }
        pre { overflow: auto; padding: 14px; background: #111827; color: #e5e7eb; border-radius: 5px; white-space: pre-wrap; word-break: break-word; }
        dt { font-weight: bold; margin-top: 8px; }
        dd { margin-left: 0; }
    </style>
</head>
<body>
    <h1>Pixelcut cURL API test</h1>
    <p class="note">Development diagnostic only. Upload an image to send a live request to the configured Pixelcut endpoint.</p>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= testHtml($_SESSION['csrf']) ?>">
        <p><label for="image">Image (JPEG, PNG, or WebP)</label></p>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,/image/*" required>
        <p><button type="submit">Test cURL request</button></p>
    </form>

    <?php if (is_array($result)): ?>
        <section class="result">
            <h2 class="<?= $result['ok'] ? 'success' : 'failure' ?>">
                <?= $result['ok'] ? 'Request succeeded' : 'Request failed' ?>
            </h2>
            <dl>
                <dt>HTTP status</dt>
                <dd><?= testHtml((string) ($result['status'] ?? 'Not sent')) ?></dd>
                <dt>Duration</dt>
                <dd><?= testHtml((string) ($result['duration'] ?? 'Not measured')) ?></dd>
                <?php if ($result['curl_error'] !== null): ?>
                    <dt>cURL error</dt>
                    <dd class="failure"><?= testHtml($result['curl_error']) ?></dd>
                <?php endif; ?>
            </dl>
            <?php if ($result['response'] !== null): ?>
                <h3>Provider response</h3>
                <pre><?= testHtml($result['response']) ?></pre>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</body>
</html>