<?php
require_once __DIR__.'/auth.php';
function page_header($title){
    $u=user();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).' - HRMS</title><link rel="stylesheet" href="../assets/style.css"></head><body><nav><strong>HRMS Recruitment</strong><span>'.e($u['nama']??'Guest').' '.(isset($u['role'])?'('.e($u['role']).')':'').'</span><a href="../auth/logout.php">Logout</a></nav><main><h1>'.e($title).'</h1>';
    if($m=flash()) echo '<div class="flash">'.e($m).'</div>';
}
function page_footer(){echo '</main></body></html>';}
