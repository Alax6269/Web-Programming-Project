<?php
// Auto-check if session is expired or invalid
if (!defined('SKIP_AUTH_CHECK')) {
    session_start();
    
    if (!isset($_SESSION['logged_in_user'])) {
        header("Location: RoleSelectPage.php");
        exit();
    }
}

date_default_timezone_set('Asia/Kuala_Lumpur');
setcookie('last_visited', date('Y-m-d H:i:s'), time() + (86400 * 7), "/");
if (isset($_COOKIE['last_visited'])) {
    $last_visited = $_COOKIE['last_visited'];
} else {
    $last_visited = null;
}
?>
