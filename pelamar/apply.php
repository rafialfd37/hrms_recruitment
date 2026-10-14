<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_role('pelamar');

$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT * FROM lowongan WHERE id_lowongan=? AND status='Dipublikasikan' AND (deadline IS NULL OR deadline>=CURDATE())");
$s->execute([$id]);
$job = $s->fetch();
if (!$job) die('Lowongan tidak tersedia.');

$q = $pdo->prepare('SELECT id_tahapan FROM tahapan_seleksi WHERE id_lowongan=? ORDER BY urutan LIMIT 1');
$q->execute([$id]);
$t = $q->fetchColumn();
if (!$t) die('Lowongan belum memiliki tahapan seleksi.');

$q = $pdo->prepare('SELECT id_lamaran FROM lamaran WHERE id_lowongan=? AND id_pelamar=?');
$q->execute([$id, user()['id_pelamar']]);
if ($q->fetch()) { flash('Kamu sudah pernah melamar posisi ini.'); header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['cv'] ?? null;
    $allowed = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'CV wajib diunggah.';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Ukuran CV maksimal 5 MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            $error = 'Format CV harus PDF, DOC, atau DOCX.';
        } else {
            $ext = $allowed[$mime];
            $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/cv';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $target = $uploadDir . '/' . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $target)) {
                $error = 'CV gagal disimpan di server.';
            } else {
                try {
                    $q = $pdo->prepare('INSERT INTO lamaran(id_lowongan,id_pelamar,id_tahapan_aktif,cv_path,cv_original_name) VALUES(?,?,?,?,?)');
                    $q->execute([$id, user()['id_pelamar'], $t, 'uploads/cv/' . $safeName, $file['name']]);
                    flash('Lamaran dan CV berhasil dikirim.');
                    header('Location: dashboard.php');
                    exit;
                } catch (PDOException $e) {
                    @unlink($target);
                    $error = 'Lamaran gagal disimpan. Kamu mungkin sudah pernah melamar posisi ini.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Upload CV - HRMS</title><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<nav><strong>HRMS Recruitment</strong><span><?=e(user()['nama'])?></span><a href="../auth/logout.php">Logout</a></nav>
<main>
<h1>Lamaran: <?=e($job['judul_lowongan'])?></h1>
<div class="card">
<p>CV wajib diunggah sebagai bagian dari proses lamaran.</p>
<p><strong>Format:</strong> PDF, DOC, atau DOCX &nbsp; <strong>Maksimal:</strong> 5 MB</p>
<?php if ($error): ?><div class="error"><?=e($error)?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>CV</label>
<input type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
<br><br><button class="btn" type="submit">Kirim Lamaran</button> <a class="btn secondary" href="detail_lowongan.php?id=<?=$id?>">Batal</a>
</form>
</div>
</main>
</body></html>
