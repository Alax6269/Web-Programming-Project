<?php
session_start();
define('SKIP_AUTH_CHECK', true);
include 'Component/AutoCheck.php';

/*Since JS uses GET and not POST this code will be using GET to gather the msg*/
$error_reason = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$is_timeout = ($error_reason === 'timeout');

/*This code destroy the session after the timer of 30s from JS runs out*/
if ($is_timeout) {
    session_unset();
    session_destroy();
}

/*When the user click on the Refresh btn in ErrorPage.php this code gets executed which destroys the session and creates a new session*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_action'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $session_renewed = true;
} else {
    $session_renewed = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attention Required</title>
    <link rel="stylesheet" href="style/ErrorPage.css">
</head>
<body class="err-workspace-container"> 
    
    <?php if ($session_renewed): ?>
        <!--If they clicked refresh, only show the success message-->
        <div class="err-alert-success">
            New session established! 
            <a href="RoleSelectPage.php" class="err-alert-link">Click here to Log In</a>.
        </div>
        
    <?php else: ?>
        <!--If they just arrived, show the error and the refresh button-->
        <h1 class="err-main-title">Attention!</h1>

        <?php if ($is_timeout): ?>
            <p class="err-text-line">Your session timed out due to inactivity.</p>
        <?php elseif ($error_reason !== ''): ?>
            <p class="err-text-line">Something went wrong (<?php echo htmlspecialchars($error_reason, ENT_QUOTES, 'UTF-8'); ?>).</p>
        <?php else: ?>
            <p class="err-text-line">The page you requested could not be found or an error occurred.</p>
        <?php endif; ?>
        
        <form method="POST" action="ErrorPage.php" class="err-action-form">
            <input type="hidden" name="refresh_action" value="1">
            <button type="submit" class="err-refresh-btn">REFRESH PAGE</button>
        </form>
    <?php endif; ?>
    
</body>
</html>
