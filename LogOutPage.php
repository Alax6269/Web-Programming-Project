<?php
/*Creates session and bypass the Auto Check for this page before deleting session data*/
session_start();
define('SKIP_AUTH_CHECK', true);
include 'Component/AutoCheck.php';

/*Clears all session variables*/
$_SESSION = array();

/*If got session cookie then destroys it*/
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Logged Out</title>
    <link rel="stylesheet" href="/src/style/LogOutPage.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <main class="LogOutContainer">
        <!--Tels that the user have been logged out-->
        <h1 class="LogOut-Main-Title">You Have Been Logged Out</h1>

        <!--Shows the reason why the user is seeing this page-->
        <div class="LogOut-Reason-Container">
            <span class="LogOut-Reason-Heading ">Reason:</span>
            <p class="LogOut-Reason-Description">Session Ended or You Have Been LOGGED-OUT</p>
        </div>

        <div class="LogOut-Action-Area"> 
            <!--The link to goes back to Role Select Page-->
            <a href="RoleSelectPage.php" class="LogOut-Redirect-Btn">Back To Log-In Page</a>
        </div>
    </main>
</body>
</html>
