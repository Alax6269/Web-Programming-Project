<?php
/*Session Confirm Component*/
if (!isset($_SESSION['logged_in_user'])) {
    header("Location: RoleSelectPage.php");
    exit();
}
?>
