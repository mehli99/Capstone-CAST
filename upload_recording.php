<?php
// POST:  session_code, video (multipart/form-data)
$code = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['session_code'] ?? ''); 

if (!$code || !isset($_FILES['video'])) {
    http_response_code(400);
    exit('missing data');
}

$targetDir = __DIR__ . '/../recordings/';
if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);

$basename  = $code . '_' . date('Ymd_His');        // e.g.  QM89T_20250713_140355
$ext       = '.webm';
$path      = $targetDir . $basename . $ext;

if (move_uploaded_file($_FILES['video']['tmp_name'], $path)) {
    echo 'OK';
} else {
    http_response_code(500);
    echo 'upload failed';
}
?>
