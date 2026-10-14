<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_role('hr');

$id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT cv_path, cv_original_name FROM lamaran WHERE id_lamaran=?');
$q->execute([$id]);
$cv = $q->fetch();
if (!$cv) die('CV tidak ditemukan.');

$file = __DIR__ . '/../' . $cv['cv_path'];
if (!is_file($file)) die('File CV tidak ditemukan di server.');

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . basename($cv['cv_original_name']) . '"');
readfile($file);
exit;
