<?php
/*Check if user is logged in*/
include 'Component/AutoCheck.php';
/*Connects to Data Base once to retrieve data*/
require_once 'db_connect.php';

$FakeText = str_repeat('~',1); /*Fake text*/ 

/*Stores the currently logged in userinfo in $current_user*/
$current_user = $_SESSION['logged_in_user'];

/*When id is sent by POST method this line will first look what it contains and turn it into whole number but if the data is not a number then it will use 0 as the other value*/
$target_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
/*This is just to reset the book data to empty before we fetch data from Data Base*/
$book_data = null;


/*If the form submitted contain number that is more then 0 then this code runs*/
if ($target_id > 0) {

    /*The code will try to run this code and throw any error that happens back to the server side but not to the user*/
    try {
        /*the c. tells the code which column from to pick data on a specific Data base table*/
        $query = "SELECT c.book_id, c.book_name, b.publish_date, b.author 
                  FROM UserCollection c
                  /*Looks for the column that have the same Book_id value in both UserCollection and BookData table*/
                  INNER JOIN BookData b ON c.book_id = b.book_id 
                  /*And then it only takes the data that belong to the currently logged in user and the specific one book*/
                  WHERE c.user_name = :user AND c.book_id = :id";
                  
        /*The code will prepare the operation result made above instead of running it instantly*/          
        $stmt = $pdo->prepare($query);

        /*Tells the code what value user and id would have before executing this code*/
        $stmt->execute([
            'user' => $current_user,
            'id'   => $target_id
        ]);

        /*This will tell the code to translate the data from Sql format to PHP format so my php code can run with the data*/
        /*ASSOC = Associative Array which uses the table column as array index instead of numbers like 0, 1 or so on*/
        $book_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    /*Catches the error and send it back to server side but wont show it to users*/
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Owned Book Preview</title>
    <link rel="stylesheet" href="style/Header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/OwnedBook.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <!--Includes the header component behavior-->
    <?php include 'Component/Header.php'; ?>

    <!--Main wrapper container for the owned book preview page-->
    <main class="OwnedContainer">
        
        <!--Displays the book name as h1--> 
        <h1 class="PageTitle">
            <?php echo htmlspecialchars($book_data ? $book_data['book_name'] : 'Unknown Book Record', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
 
        <!--Checks if the book data selected is in the data base table-->
        <?php if ($book_data): ?>
            <div class="MetaRow">

                <!--The box to display published date-->
                <div class="InfoBox">
                    <label>Published Date:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book_data['publish_date'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>
                
                <!--The box to display author name-->
                <div class="InfoBox">
                    <label>Author:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book_data['author'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>
            </div>

            <!--The box to display the book content-->
            <div class="NotifyBox">
                <p><?php echo str_repeat('~', 1500); ?></p> 
            </div>
            
        <!--If the data is not in Data Base table then runs show the page like this instead-->
        <?php else: ?>
            <div class="MetaRow">

                <!--The box that shows published date will be empty-->
                <div class="InfoBox">
                    <label>Published Date:</label>
                    <input type="text" value="--/--/----" disabled>
                </div>

                 <!--The box that shows author name will be empty-->
                <div class="InfoBox">
                    <label>Author:</label>
                    <input type="text" value="Unknown Author" disabled>
                </div>
            </div>
            
             <!--The box that shows there is no book data for this book in the Data Base-->
            <div class="NotifyBox">
                <p>No active collection details were found matching your request selection line. Please return to your main Dashboard profile to choose a valid book.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
