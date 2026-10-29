<?php
return [
 'api_key' => 'REPLACE_WITH_PIXELCUT_API_KEY',
 'api_url' => 'https://api.developer.pixelcut.ai/v1/remove-background',
 'max_upload_bytes' => 8 * 1024 * 1024,
 'max_pixels' => 16000000,
 'timeout' => 60,
 'rate_limit_per_hour' => 20,
 'retention_seconds' => 3600,
 'storage_dir' => dirname(__DIR__, 2) . '/removebg-private',
];
