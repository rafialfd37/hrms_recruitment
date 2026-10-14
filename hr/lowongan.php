<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/layout.php';
require_role('hr');

function redirect_lowongan(){ header('Location: lowongan.php'); exit; }
$editId = (int)($_GET['edit'] ?? 0);
$error = '';
$divisions = $pdo->query('SELECT id_divisi,nama_divisi FROM divisi ORDER BY nama_divisi')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id_lowongan'] ?? 0);
    if ($action === 'delete') {
        $q = $pdo->prepare('SELECT COUNT(*) FROM lamaran WHERE id_lowongan=?');
        $q->execute([$id]);
        if ((int)$q->fetchColumn() > 0) {
            flash('Lowongan tidak dapat dihapus karena sudah memiliki lamaran. Tutup lowongan agar riwayat seleksi tetap tersimpan.');
        } else {
            $q = $pdo->prepare('DELETE FROM lowongan WHERE id_lowongan=?');
            $q->execute([$id]);
            flash($q->rowCount() ? 'Lowongan berhasil dihapus.' : 'Lowongan tidak ditemukan.');
        }
        redirect_lowongan();
    }

    if ($action === 'save') {
        $division = (int)($_POST['id_divisi'] ?? 0);
        $title = trim($_POST['judul_lowongan'] ?? '');
        $desc = trim($_POST['job_description'] ?? '');
        $spec = trim($_POST['job_specification'] ?? '');
        $needed = (int)($_POST['jumlah_kebutuhan'] ?? 0);
        $deadline = trim($_POST['deadline'] ?? '') ?: null;
        $status = $_POST['status'] ?? 'Dipublikasikan';
        $allowedStatuses = ['Dipublikasikan','Ditutup'];
        $stageNames = $_POST['tahap_nama'] ?? [];
        $stageDescs = $_POST['tahap_desc'] ?? [];
        $stages = [];
        foreach ($stageNames as $i => $stageName) {
            $stageName = trim($stageName);
            if ($stageName !== '') $stages[] = ['nama'=>$stageName, 'deskripsi'=>trim($stageDescs[$i] ?? '')];
        }
        $q = $pdo->prepare('SELECT COUNT(*) FROM divisi WHERE id_divisi=?');
        $q->execute([$division]);
        $validDivision = (int)$q->fetchColumn() > 0;
        $existing = null;
        if ($id > 0) {
            $q = $pdo->prepare('SELECT * FROM lowongan WHERE id_lowongan=?');
            $q->execute([$id]); $existing = $q->fetch();
        }
        $appCount = 0;
        if ($id > 0) {
            $q = $pdo->prepare('SELECT COUNT(*) FROM lamaran WHERE id_lowongan=?');
            $q->execute([$id]); $appCount = (int)$q->fetchColumn();
        }
        if (!$validDivision) $error = 'Pilih divisi yang valid.';
        elseif ($title === '' || $desc === '' || $spec === '' || $needed < 1) $error = 'Judul, deskripsi, spesifikasi, dan jumlah kebutuhan wajib diisi dengan benar.';
        elseif (!in_array($status, $allowedStatuses, true)) $error = 'Status lowongan tidak valid.';
        elseif ($deadline !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) $error = 'Format deadline tidak valid.';
        elseif ($id > 0 && !$existing) $error = 'Lowongan tidak ditemukan.';
        elseif ($appCount === 0 && count($stages) === 0) $error = 'Tambahkan minimal satu tahapan seleksi.';
        elseif ($appCount > 0 && isset($_POST['ubah_tahapan']) && $_POST['ubah_tahapan'] === '1') $error = 'Tahapan seleksi tidak dapat diubah setelah ada lamaran masuk agar riwayat seleksi tetap konsisten.';

        if ($error === '') {
            try {
                $pdo->beginTransaction();
                if ($id > 0) {
                    $publishDate = $existing['tanggal_publish'] ?: date('Y-m-d H:i:s');
                    $q = $pdo->prepare('UPDATE lowongan SET id_divisi=?,judul_lowongan=?,job_description=?,job_specification=?,jumlah_kebutuhan=?,deadline=?,status=?,tanggal_publish=? WHERE id_lowongan=?');
                    $q->execute([$division,$title,$desc,$spec,$needed,$deadline,$status,$publishDate,$id]);
                } else {
                    $q = $pdo->prepare('INSERT INTO lowongan (id_divisi,judul_lowongan,job_description,job_specification,jumlah_kebutuhan,status,tanggal_publish,deadline) VALUES (?,?,?,?,?,?,NOW(),?)');
                    $q->execute([$division,$title,$desc,$spec,$needed,$status,$deadline]);
                    $id = (int)$pdo->lastInsertId();
                }
                if ($appCount === 0) {
                    $pdo->prepare('DELETE FROM tahapan_seleksi WHERE id_lowongan=?')->execute([$id]);
                    $q = $pdo->prepare('INSERT INTO tahapan_seleksi (id_lowongan,nama_tahapan,urutan,deskripsi) VALUES (?,?,?,?)');
                    foreach ($stages as $i=>$stage) $q->execute([$id,$stage['nama'],$i+1,$stage['deskripsi']]);
                }
                $pdo->commit();
                flash($existing ? 'Perubahan lowongan berhasil disimpan.' : 'Lowongan berhasil dibuat.');
                redirect_lowongan();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Gagal menyimpan lowongan. Periksa data lalu coba lagi.';
            }
        }
        $editId = $id;
    }
}

$job = null; $jobStages = []; $applicationCount = 0;
if ($editId > 0) {
    $q = $pdo->prepare('SELECT * FROM lowongan WHERE id_lowongan=?'); $q->execute([$editId]); $job = $q->fetch();
    if ($job) {
        $q = $pdo->prepare('SELECT * FROM tahapan_seleksi WHERE id_lowongan=? ORDER BY urutan'); $q->execute([$editId]); $jobStages = $q->fetchAll();
        $q = $pdo->prepare('SELECT COUNT(*) FROM lamaran WHERE id_lowongan=?'); $q->execute([$editId]); $applicationCount = (int)$q->fetchColumn();
    }
}
$rows = $pdo->query('SELECT l.*,d.nama_divisi,(SELECT COUNT(*) FROM lamaran la WHERE la.id_lowongan=l.id_lowongan) AS jumlah_lamaran FROM lowongan l JOIN divisi d ON d.id_divisi=l.id_divisi ORDER BY l.id_lowongan DESC')->fetchAll();
page_header('Kelola Lowongan');
?>
<?php if ($error !== ''): ?><div class="flash"><?=e($error)?></div><?php endif; ?>
<div class="card">
<h2><?=$job ? 'Edit Lowongan' : 'Tambah Lowongan'?></h2>
<p>Kelola lowongan yang dipublikasikan HR. Pengajuan dari manajer tetap diproses melalui menu Pengajuan Lowongan.</p>
<form method="post">
<input type="hidden" name="action" value="save"><input type="hidden" name="id_lowongan" value="<?=e($job['id_lowongan'] ?? 0)?>">
<label>Divisi<select name="id_divisi" required><option value="">-- Pilih Divisi --</option><?php foreach($divisions as $d): ?><option value="<?=$d['id_divisi']?>" <?=((int)($job['id_divisi'] ?? ($_POST['id_divisi'] ?? 0))===(int)$d['id_divisi'])?'selected':''?>><?=e($d['nama_divisi'])?></option><?php endforeach; ?></select></label>
<label>Judul Lowongan<input name="judul_lowongan" maxlength="150" required value="<?=e($job['judul_lowongan'] ?? ($_POST['judul_lowongan'] ?? ''))?>"></label>
<label>Jumlah Kebutuhan<input type="number" name="jumlah_kebutuhan" min="1" required value="<?=e($job['jumlah_kebutuhan'] ?? ($_POST['jumlah_kebutuhan'] ?? 1))?>"></label>
<label>Job Description<textarea name="job_description" required><?=e($job['job_description'] ?? ($_POST['job_description'] ?? ''))?></textarea></label>
<label>Job Specification<textarea name="job_specification" required><?=e($job['job_specification'] ?? ($_POST['job_specification'] ?? ''))?></textarea></label>
<label>Deadline<input type="date" name="deadline" value="<?=e($job['deadline'] ?? ($_POST['deadline'] ?? ''))?>"></label>
<label>Status<select name="status"><option value="Dipublikasikan" <?=($job['status'] ?? ($_POST['status'] ?? 'Dipublikasikan'))==='Dipublikasikan'?'selected':''?>>Dipublikasikan</option><option value="Ditutup" <?=($job['status'] ?? ($_POST['status'] ?? ''))==='Ditutup'?'selected':''?>>Ditutup</option></select></label>
<h3>Tahapan Seleksi</h3>
<?php if ($job && $applicationCount > 0): ?>
<p>Sudah ada <?=$applicationCount?> lamaran. Tahapan dikunci agar hasil seleksi dan lamaran yang sedang berjalan tidak rusak.</p>
<ol><?php foreach($jobStages as $st): ?><li><?=e($st['nama_tahapan'])?> — <?=e($st['deskripsi'])?></li><?php endforeach; ?></ol>
<?php else: ?>
<div id="steps">
<?php $initialStages = $_POST['tahap_nama'] ?? array_column($jobStages,'nama_tahapan'); $initialDescs = $_POST['tahap_desc'] ?? array_column($jobStages,'deskripsi'); if (!$initialStages) {$initialStages=[''];$initialDescs=[''];} foreach($initialStages as $i=>$stageName): ?>
<div class="card"><label>Nama Tahap<input name="tahap_nama[]" value="<?=e($stageName)?>" placeholder="Contoh: Seleksi Administrasi" required></label><label>Deskripsi Tahap<textarea name="tahap_desc[]" placeholder="Deskripsi tahap"><?=e($initialDescs[$i] ?? '')?></textarea></label></div>
<?php endforeach; ?>
</div><button type="button" class="btn secondary" onclick="addStep()">+ Tambah Tahap</button>
<?php endif; ?>
<button class="btn success" type="submit">Simpan Lowongan</button><?php if($job): ?> <a class="btn secondary" href="lowongan.php">Batal Edit</a><?php endif; ?>
</form>
</div>
<h2>Daftar Lowongan</h2>
<table><tr><th>Posisi</th><th>Divisi</th><th>Kebutuhan</th><th>Deadline</th><th>Status</th><th>Lamaran</th><th>Aksi</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=e($r['judul_lowongan'])?></td><td><?=e($r['nama_divisi'])?></td><td><?=e($r['jumlah_kebutuhan'])?></td><td><?=e($r['deadline'] ?: '-')?></td><td><?=e($r['status'])?></td><td><?=e($r['jumlah_lamaran'])?></td><td class="actions"><a class="btn" href="lowongan.php?edit=<?=$r['id_lowongan']?>">Edit</a><form method="post" onsubmit="return confirm('Hapus lowongan ini? Tindakan ini tidak dapat dibatalkan.');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_lowongan" value="<?=$r['id_lowongan']?>"><button class="btn danger" type="submit" <?=((int)$r['jumlah_lamaran']>0)?'disabled title="Lowongan memiliki lamaran"':''?>>Hapus</button></form><a class="btn secondary" href="seleksi.php?id=<?=$r['id_lowongan']?>">Seleksi</a></td></tr><?php endforeach; ?>
</table>
<script>function addStep(){const d=document.createElement('div');d.className='card';d.innerHTML='<label>Nama Tahap<input name="tahap_nama[]" placeholder="Nama tahap" required></label><label>Deskripsi Tahap<textarea name="tahap_desc[]" placeholder="Deskripsi tahap"></textarea></label>';document.getElementById('steps').appendChild(d)}</script>
<?php page_footer(); ?>
