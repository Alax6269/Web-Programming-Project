<?php
session_start();

/* Database connection credentials */
$db_host = 'fdb1030.awardspace.net';
$db_user = '4775341_ebookflash';
$db_pass = 'Admin01^@^(';
$db_name = '4775341_ebookflash';


/*The code will try to establish a secure connection to the Data Base*/
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [   /*Force PDO to throw exceptions on errors so the catch block can handle them*/
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            /*Fetch results as associative arrays by default like $row['column_name'])*/
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {

    /*If the connection fails, stop the script and display an error message for that specific error*/
    die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
