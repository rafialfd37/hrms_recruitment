<?php require_once 'config/auth.php'; if(logged_in()) go_role(); header('Location: auth/login.php'); exit;
