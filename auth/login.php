<?php
require_once '../config/database.php';
require_once '../config/auth.php';
if(logged_in()) go_role();
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=trim($_POST['username']??''); $password=$_POST['password']??'';
    $s=$pdo->prepare("SELECT u.*, COALESCE(m.nama,p.nama,u.username) AS nama, m.id_divisi, p.id_pelamar FROM users u LEFT JOIN manajer m ON m.id_user=u.id_user LEFT JOIN pelamar p ON p.id_user=u.id_user WHERE u.username=? LIMIT 1");
    $s->execute([$username]); $u=$s->fetch();
    if($u && hash_equals($u['password'],hash('sha256',$password))){
        if(($u['role']==='manager' && empty($u['id_divisi'])) || ($u['role']==='pelamar' && empty($u['id_pelamar']))){$err='Profil akun belum lengkap. Hubungi HR Admin.';}
        else {unset($u['password']); $_SESSION['user']=$u; go_role();}
    } else {$err='Username atau password salah.';}
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login HRMS</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="login card"><h1>Login HRMS</h1><?php if($err):?><p class="error"><?=e($err)?></p><?php endif;?><form method="post"><label>Username<input name="username" required autofocus></label><label>Password<input type="password" name="password" required></label><button class="btn" type="submit">Login</button></form><p><a href="../pelamar/register.php">Daftar sebagai pelamar</a></p><p class="small">Demo: manager_it / password · hr_admin / password · rafi / password</p></div></body></html>
