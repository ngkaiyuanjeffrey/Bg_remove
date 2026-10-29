<?php
declare(strict_types=1);
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$configFile = __DIR__ . '/../config/config.php';
if (!is_file($configFile)) { http_response_code(503); exit('Server configuration missing. See README.md.'); }
$config = require $configFile;
if (!is_array($config)) exit('Invalid server configuration');
$storage = $config['storage_dir'];
foreach ([$storage, "$storage/jobs", "$storage/limits"] as $dir) {
 if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) { http_response_code(503); exit('Storage unavailable'); }
}
if (session_status() !== PHP_SESSION_ACTIVE) { session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']); session_start(); }
function respond(int $code, array $data): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); exit; }
function cleanup(array $config): void {
 $dir = $config['storage_dir'].'/jobs';
 foreach (glob($dir.'/*.png') ?: [] as $file) { if (is_file($file) && filemtime($file) < time() - $config['retention_seconds']) @unlink($file); }
 foreach (glob($config['storage_dir'].'/limits/*') ?: [] as $file) { if (is_file($file) && filemtime($file) < time()-7200) @unlink($file); }
}
function limitRequest(array $config): bool {
 $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown'; $path = $config['storage_dir'].'/limits/'.hash('sha256',$ip.'|'.date('Y-m-d-H'));
 $f = fopen($path,'c+'); if (!$f) return false;
 try { if (!flock($f, LOCK_EX)) return false; $n = (int)stream_get_contents($f); if ($n >= $config['rate_limit_per_hour']) return false; rewind($f); ftruncate($f,0); fwrite($f,(string)($n+1)); fflush($f); return true; } finally { flock($f,LOCK_UN); fclose($f); }
}
