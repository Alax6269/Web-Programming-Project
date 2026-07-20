<?php
/*Creates session and bypass the Auto Check for this page*/
session_start();
define('SKIP_AUTH_CHECK', true);
include 'Component/AutoCheck.php';

require_once 'db_connect.php';    


/*If the role is selected then the user is redirected to Login Page*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $_SESSION['user_role'] = $_POST['role'];
    header("Location: LoginPage.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Selection</title>
    <link rel="stylesheet" href="style/Role.css">
</head>
<body>
    <div class="RoleBox">
        <h1>User Role Selection Page</h1>
        <p class="sub-text">Choose Role</p>
        
        <!--Container for role selection buttons-->
        <div class="ButtonPlacements">

            <!--Form to select the Regular User role-->
            <form method="POST" action="RoleSelectPage.php">
                <input type="hidden" name="role" value="Regular User">
                <button type="submit" class="RoleBtn">Regular User</button>
            </form>
            
            <!--Form to select the Administrator role-->
            <form method="POST" action="RoleSelectPage.php">
                <input type="hidden" name="role" value="Administrator">
                <button type="submit" class="RoleBtn">Administrator</button>
            </form>
        </div>
    </div>
</body>
</html>
