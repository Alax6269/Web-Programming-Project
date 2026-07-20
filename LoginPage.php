<?php
/*Bypass the Auto Check for this page*/
define('SKIP_AUTH_CHECK', true);
include 'Component/AutoCheck.php';
require_once 'db_connect.php';

$user_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username']);
    $input_password = $_POST['password'];
    

    /*All of the valid username and password*/
    $valid_users = [
        ['username' => 'Bob123', 'password' => 'Bob!@#'],
        ['username' => 'Jack123', 'password' => 'Jack!@#'],
        ['username' => 'admin', 'password' => 'admin!@#$%^&*(!)'],
    ];
    
    /*Loops through the valid username options*/
    foreach ($valid_users as $user) {

        /*If the user entered the valid username and password then set session 'logged_in_user' value to it*/
        if ($input_username === $user['username'] && $input_password === $user['password']) {
            /*The logged_in_user value for this session is this*/
            $_SESSION['logged_in_user'] = $input_username;
             
            /*try will hide any error from user but will send it to the server back end instead*/
            try {
                /*This code prepares to add new row into UserData table in Data Base based on the user that just logged in*/
                $stmt = $pdo->prepare("INSERT INTO UserData (user_name, account_created_date, book_owned, total_spending)
                                       VALUES (:user, NOW(), 0, 0.00)
                                       ON DUPLICATE KEY UPDATE user_name = user_name");
                
                /*This code tells the Data Base that it will prepare the value first and only then the Data Base should run the INSERT command*/
                $stmt->execute(['user' => $input_username]);

            /*This will catch the errors and send it to the server side*/
            } catch (PDOException $e) {
                error_log($e->getMessage());
            }
            /*fter catching the error the use is sent back to Home Page*/
            header("Location: Homepage.php");
            exit();
        }
    }
    
    $user_error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Log In</title>
    <link rel="stylesheet" href="style/LoginPage.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="LoginInputBox">
        <h1>Log In Page</h1>
        <p class="SubTxt">Fill In The Form</p>
        
        <form action="LoginPage.php" method="POST">
            <input 
                type="text" 
                name="username" 
                placeholder="Username" 
                class="<?php echo $user_error ? 'ErrorStatus' : ''; ?>"
                required
            >
            
            <input 
                type="password" 
                name="password" 
                placeholder="Password" 
                class="<?php echo $user_error ? 'ErrorStatus' : ''; ?>"
                required
            >
            
            <button type="submit" class="LoginBtn">Log In</button>
            <?php if ($last_visited): ?>
              <p class="LastVisited">
                  Last visited: <?php echo htmlspecialchars($last_visited, ENT_QUOTES, 'UTF-8'); ?>
              </p>
            <?php else: ?>
               <p class="LastVisited">Welcome! This is your first time visiting.</p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
