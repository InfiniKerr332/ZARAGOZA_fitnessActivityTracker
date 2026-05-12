<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (isLoggedIn()) {
    logAction($_SESSION['user_id'], 'logout', 'User logged out');
}
session_destroy();
session_start();
setFlash('success', 'You have been logged out.');
redirect('/auth/login.php');
