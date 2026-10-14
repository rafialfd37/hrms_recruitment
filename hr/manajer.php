<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/layout.php';
require_role('hr');
$errors=[];
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
$edit=null;
if($editId){$q=$pdo->prepare("SELECT m.id_manajer,m.id_user,m.id_divisi,m.nama,u.username FROM manajer m JOIN users u ON u.id_user=m.id_user WHERE m.id_manajer=? AND u.role='manager'");$q->execute([$editId]);$edit=$q->fetch();if(!$edit){flash('Akun manajer tidak ditemukan.');header('Location: manajer.php');exit;}}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $id=filter_input(INPUT_POST,'id_manajer',FILTER_VALIDATE_INT);
    if($action==='save'){
        $nama=trim($_POST['nama']??'');$username=trim($_POST['username']??'');$password=$_POST['password']??'';$id_divisi=filter_input(INPUT_POST,'id_divisi',FILTER_VALIDATE_INT);
        if($id){$q=$pdo->prepare("SELECT m.id_manajer,m.id_user FROM manajer m JOIN users u ON u.id_user=m.id_user WHERE m.id_manajer=? AND u.role='manager'");$q->execute([$id]);$target=$q->fetch();if(!$target)$errors[]='Akun manajer tidak ditemukan.';}
        if($nama==='')$errors[]='Nama manajer wajib diisi.';
        if(!preg_match('/^[A-Za-z0-9._-]{3,50}$/',$username))$errors[]='Username harus 3–50 karakter dan hanya berisi huruf, angka, titik, _ atau -.';
        if(!$id && strlen($password)<6)$errors[]='Password minimal 6 karakter.';
        if($id && $password!=='' && strlen($password)<6)$errors[]='Password baru harus minimal 6 karakter.';
        if(!$id_divisi)$errors[]='Pilih divisi.';
        $q=$pdo->prepare('SELECT id_divisi FROM divisi WHERE id_divisi=?');$q->execute([$id_divisi]);if(!$q->fetch())$errors[]='Divisi tidak ditemukan.';
        $q=$pdo->prepare('SELECT id_user FROM users WHERE username=? AND id_user<>?');$q->execute([$username,$target['id_user']??0]);if($q->fetch())$errors[]='Username sudah digunakan.';
        if(!$errors){
            try{
                $pdo->beginTransaction();
                if($id){$q=$pdo->prepare('UPDATE users SET username=? WHERE id_user=?');$q->execute([$username,$target['id_user']]);if($password!==''){$q=$pdo->prepare('UPDATE users SET password=? WHERE id_user=?');$q->execute([hash('sha256',$password),$target['id_user']]);}$q=$pdo->prepare('UPDATE manajer SET nama=?,id_divisi=? WHERE id_manajer=?');$q->execute([$nama,$id_divisi,$id]);flash('Akun manajer berhasil diperbarui.');}
                else{$q=$pdo->prepare("INSERT INTO users(username,password,role) VALUES(?,?,'manager')");$q->execute([$username,hash('sha256',$password)]);$uid=(int)$pdo->lastInsertId();$q=$pdo->prepare('INSERT INTO manajer(id_user,id_divisi,nama) VALUES(?,?,?)');$q->execute([$uid,$id_divisi,$nama]);flash('Akun manajer berhasil dibuat.');}
                $pdo->commit();header('Location: manajer.php');exit;
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]='Gagal menyimpan akun. Periksa data dan pastikan username unik.';}
        }
        $edit=['id_manajer'=>$id,'nama'=>$nama,'username'=>$username,'id_divisi'=>$id_divisi];
    }elseif($action==='delete' && $id){
        $q=$pdo->prepare("SELECT m.id_user FROM manajer m JOIN users u ON u.id_user=m.id_user WHERE m.id_manajer=? AND u.role='manager'");$q->execute([$id]);$target=$q->fetch();
        if(!$target)flash('Akun manajer tidak ditemukan.');
        else{$pdo->beginTransaction();try{$q=$pdo->prepare('DELETE FROM users WHERE id_user=?');$q->execute([$target['id_user']]);$pdo->commit();flash('Akun manajer berhasil dihapus.');}catch(Throwable $e){$pdo->rollBack();flash('Akun manajer gagal dihapus.');}}
        header('Location: manajer.php');exit;
    }
}
$divisi=$pdo->query('SELECT id_divisi,nama_divisi FROM divisi ORDER BY nama_divisi')->fetchAll();
$manajer=$pdo->query("SELECT m.id_manajer,m.id_user,m.nama,u.username,u.created_at,d.nama_divisi FROM manajer m JOIN users u ON u.id_user=m.id_user JOIN divisi d ON d.id_divisi=m.id_divisi WHERE u.role='manager' ORDER BY u.created_at DESC,m.nama")->fetchAll();
page_header('Kelola Akun Manajer'); ?>
<div class="card"><h2><?= $edit?'Edit Akun Manajer':'Tambah Akun Manajer' ?></h2><p class="small">Data login disimpan di <code>users</code>, sedangkan nama dan penempatan divisi disimpan di <code>manajer</code>.</p>
<?php if($errors):?><div class="error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<?php if(!$divisi):?><p class="error">Belum ada divisi. Buat divisi terlebih dahulu.</p><?php else:?><form method="post" autocomplete="off"><input type="hidden" name="action" value="save"><input type="hidden" name="id_manajer" value="<?=e($edit['id_manajer']??'')?>"><label>Nama lengkap manajer<input name="nama" maxlength="150" required value="<?=e($edit['nama']??'')?>"></label><label>Username login<input name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required value="<?=e($edit['username']??'')?>"></label><label>Password <?= $edit?'(kosongkan jika tidak ingin mengganti)':'awal' ?><input type="password" name="password" minlength="6" <?= $edit?'':'required' ?> autocomplete="new-password"></label><label>Divisi<select name="id_divisi" required><option value="">-- Pilih divisi --</option><?php foreach($divisi as $d):?><option value="<?=e($d['id_divisi'])?>" <?=((string)($edit['id_divisi']??'')===(string)$d['id_divisi'])?'selected':''?>><?=e($d['nama_divisi'])?></option><?php endforeach;?></select></label><button class="btn"><?= $edit?'Simpan Perubahan':'Simpan Akun Manajer' ?></button><?php if($edit):?> <a class="btn secondary" href="manajer.php">Batal</a><?php endif;?> <a class="btn secondary" href="dashboard.php">Kembali</a></form><?php endif;?></div>
<div class="card"><h2>Daftar Akun Manajer</h2><?php if(!$manajer):?><p>Belum ada akun manajer.</p><?php else:?><table><tr><th>Nama</th><th>Username</th><th>Divisi</th><th>Dibuat</th><th>Aksi</th></tr><?php foreach($manajer as $m):?><tr><td><?=e($m['nama'])?></td><td><?=e($m['username'])?></td><td><?=e($m['nama_divisi'])?></td><td><?=e($m['created_at'])?></td><td><div class="actions"><a class="btn" href="manajer.php?edit=<?=e($m['id_manajer'])?>">Edit</a><form method="post" onsubmit="return confirm('Hapus akun manajer ini? Akun login terkait juga akan dihapus.')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_manajer" value="<?=e($m['id_manajer'])?>"><button class="btn danger" type="submit">Hapus</button></form></div></td></tr><?php endforeach;?></table><?php endif;?></div>
<?php page_footer(); ?>
