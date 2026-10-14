<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/layout.php';
require_role('hr');
$errors=[];
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
$edit=null;
if($editId){$q=$pdo->prepare('SELECT id_divisi,nama_divisi FROM divisi WHERE id_divisi=?');$q->execute([$editId]);$edit=$q->fetch();if(!$edit){flash('Divisi tidak ditemukan.');header('Location: divisi.php');exit;}}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $id=filter_input(INPUT_POST,'id_divisi',FILTER_VALIDATE_INT);
    if($action==='save'){
        $nama=trim($_POST['nama_divisi']??'');
        if($nama==='')$errors[]='Nama divisi wajib diisi.';
        if(mb_strlen($nama)>100)$errors[]='Nama divisi maksimal 100 karakter.';
        $q=$pdo->prepare('SELECT id_divisi FROM divisi WHERE nama_divisi=? AND id_divisi<>?');$q->execute([$nama,$id?:0]);
        if($q->fetch())$errors[]='Nama divisi sudah digunakan.';
        if(!$errors){
            if($id){$q=$pdo->prepare('UPDATE divisi SET nama_divisi=? WHERE id_divisi=?');$q->execute([$nama,$id]);flash('Divisi berhasil diperbarui.');}
            else{$q=$pdo->prepare('INSERT INTO divisi(nama_divisi) VALUES(?)');$q->execute([$nama]);flash('Divisi berhasil ditambahkan.');}
            header('Location: divisi.php');exit;
        }
        $edit=['id_divisi'=>$id,'nama_divisi'=>$nama];
    } elseif($action==='delete' && $id){
        $q=$pdo->prepare('SELECT (SELECT COUNT(*) FROM manajer WHERE id_divisi=?) + (SELECT COUNT(*) FROM lowongan WHERE id_divisi=?) AS terpakai');$q->execute([$id,$id]);$used=(int)$q->fetchColumn();
        if($used>0)flash('Divisi tidak dapat dihapus karena masih digunakan oleh akun manajer atau lowongan.');
        else{$q=$pdo->prepare('DELETE FROM divisi WHERE id_divisi=?');$q->execute([$id]);flash('Divisi berhasil dihapus.');}
        header('Location: divisi.php');exit;
    }
}
$rows=$pdo->query('SELECT d.id_divisi,d.nama_divisi,COUNT(DISTINCT m.id_manajer) jumlah_manajer,COUNT(DISTINCT l.id_lowongan) jumlah_lowongan FROM divisi d LEFT JOIN manajer m ON m.id_divisi=d.id_divisi LEFT JOIN lowongan l ON l.id_divisi=d.id_divisi GROUP BY d.id_divisi ORDER BY d.nama_divisi')->fetchAll();
page_header('Kelola Divisi');
?>
<div class="card"><h2><?= $edit?'Edit Divisi':'Tambah Divisi' ?></h2>
<?php if($errors):?><div class="error"><ul><?php foreach($errors as $err):?><li><?=e($err)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id_divisi" value="<?=e($edit['id_divisi']??'')?>"><label>Nama Divisi<input name="nama_divisi" maxlength="100" required value="<?=e($edit['nama_divisi']??'')?>"></label><button class="btn"><?= $edit?'Simpan Perubahan':'Tambah Divisi' ?></button><?php if($edit):?> <a class="btn secondary" href="divisi.php">Batal</a><?php endif;?> <a class="btn secondary" href="dashboard.php">Kembali</a></form></div>
<div class="card"><h2>Daftar Divisi</h2><table><tr><th>ID</th><th>Nama Divisi</th><th>Jumlah Manajer</th><th>Jumlah Lowongan</th><th>Aksi</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['id_divisi'])?></td><td><?=e($r['nama_divisi'])?></td><td><?=e($r['jumlah_manajer'])?></td><td><?=e($r['jumlah_lowongan'])?></td><td><div class="actions"><a class="btn" href="divisi.php?edit=<?=e($r['id_divisi'])?>">Edit</a><form method="post" onsubmit="return confirm('Yakin ingin menghapus divisi ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_divisi" value="<?=e($r['id_divisi'])?>"><button class="btn danger" type="submit">Hapus</button></form></div></td></tr><?php endforeach;?>
</table><p class="small">Divisi yang masih digunakan oleh akun manajer atau lowongan tidak dapat dihapus.</p></div><?php page_footer();?>
