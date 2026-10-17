<?php
require_once __DIR__.'/auth.php';
function page_header($title){
    $u=user();
    $role=$u['role']??'';
    $links=[];
    if($role==='hr') $links=[['Dashboard','dashboard.php'],['Pengajuan','pengajuan.php'],['Lowongan','lowongan.php'],['Pelamar','pelamar.php'],['Divisi','divisi.php'],['Manajer','manajer.php']];
    elseif($role==='manager') $links=[['Dashboard','dashboard.php'],['Ajukan Lowongan','ajukan_lowongan.php']];
    elseif($role==='pelamar') $links=[['Dashboard','dashboard.php'],['Cari Lowongan','lowongan.php']];
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#10213b"><title>'.e($title).' - HRMS Recruitment</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/style.css"></head><body>';
    echo '<header class="app-header"><a class="brand" href="dashboard.php"><span class="brand-mark">H</span><span>HRMS<span class="brand-sub">RECRUITMENT</span></span></a><nav class="main-nav">';
    foreach($links as $link){$active=basename($_SERVER['PHP_SELF'])===$link[1]?' active':''; echo '<a class="nav-link'.$active.'" href="'.e($link[1]).'">'.e($link[0]).'</a>';}
    echo '</nav><div class="user-menu"><div class="avatar">'.e(strtoupper(substr($u['nama']??'U',0,1))).'</div><div class="user-meta"><strong>'.e($u['nama']??'Guest').'</strong><span>'.e(strtoupper($role)).'</span></div><a class="logout-link" href="../auth/logout.php" aria-label="Logout" title="Logout">Keluar</a><button class="menu-toggle" type="button" onclick="document.body.classList.toggle(\'nav-open\')" aria-label="Buka navigasi">☰</button></div></header>';
    echo '<main class="page-shell"><div class="page-heading"><div><div class="eyebrow">WORKSPACE / '.e(strtoupper($role)).'</div><h1>'.e($title).'</h1></div><div class="heading-date">Human Resource Management</div></div>';
    if($m=flash()) echo '<div class="flash"><span class="flash-icon">✓</span><span>'.e($m).'</span></div>';
}
function page_footer(){echo '</main><footer class="app-footer"><span>HRMS Recruitment</span><span>Human Resource Management System · Prototype</span></footer><script>document.querySelectorAll("form").forEach(function(f){f.addEventListener("submit",function(){var b=f.querySelector("button[type=submit],button:not([type])");if(b&&!b.dataset.noLoading){b.classList.add("is-loading");b.setAttribute("aria-busy","true");}})});</script></body></html>';}
