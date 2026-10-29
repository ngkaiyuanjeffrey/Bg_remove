<?php
require __DIR__.'/includes/bootstrap.php';
$id=$_GET['id']??'';
if (!is_string($id) || !preg_match('/^[a-f0-9]{40}$/D',$id) || ($_SESSION['jobs'][$id]??0)<time()) { http_response_code(403); exit('Download expired or unauthorized'); }
$path=$config['storage_dir'].'/jobs/'.$id.'.png';
if (!is_file($path)) { http_response_code(404); exit('File expired'); }
header('Content-Type: image/png'); header('Content-Disposition: inline; filename="removebg.png"'); header('Content-Length: '.filesize($path)); readfile($path);
