<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function e($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
function logged_in(){return isset($_SESSION['user']);}
function require_login(){if(!logged_in()){header('Location: ../auth/login.php');exit;}}
function require_role($role){require_login();if($_SESSION['user']['role']!==$role){http_response_code(403);die('Akses ditolak.');}}
function go_role(){ $r=$_SESSION['user']['role']??''; $map=['manager'=>'../manager/dashboard.php','hr'=>'../hr/dashboard.php','pelamar'=>'../pelamar/dashboard.php']; header('Location: '.($map[$r]??'../auth/login.php')); exit; }
function flash($msg=null){if($msg!==null){$_SESSION['flash']=$msg;return;} $m=$_SESSION['flash']??null;unset($_SESSION['flash']);return $m;}
function user(){return $_SESSION['user']??null;}
