<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$config=require __DIR__.'/../config/config.php';
$dir=$config['storage_dir'].'/jobs';
foreach (glob($dir.'/*.png') ?: [] as $file) if (filemtime($file)<time()-$config['retention_seconds']) @unlink($file);
