<?php
/*starts the session and check if the user is logged in*/
session_start();
include 'Component/AutoCheck.php';
require_once 'db_connect.php';


/*Looks for incoming id via POST method*/
$target_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$book_data = null;
$autosubmit_buy_form = false; // Flag to handle dynamic POST forwarding for BUY NOW actions

if ($target_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM BookData WHERE book_id = :id");
        $stmt->execute(['id' => $target_id]);
        $book_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

/*If recieved any action POST request then trigger this code which is used for adding books to the cart array*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $book_data) {
    $target_book_id = intval($_POST['id']);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($_POST['action'] === 'add_to_cart') {
        if (!in_array($target_book_id, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $target_book_id;
        }
        header("Location: CartPage.php");
        exit();
    } elseif ($_POST['action'] === 'buy_now') {
        $autosubmit_buy_form = true;
    }
}

/*If the book data is not there then redirects user back to HomePage.php*/
if (!$book_data) {
    header("Location: Homepage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Preview - <?php echo htmlspecialchars($book_data['book_name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style/Header.css?v=1.0">
    <link rel="stylesheet" href="style/BookPages.css?v=1.0">
</head>
<body>

    <?php include 'Component/Header.php'; ?>
     
    <!--Main container for the page-->
    <main class="PreviewContainer">
       
        <!--If the auto submit for buy form is set to True then runs this code-->
        <?php if ($autosubmit_buy_form): ?>

            <form id="autoBuyForm" method="POST" action="PurchaseConfirmationPage.php">
                <!--Puts the selected book id into the selected_item array for purchase confirmation page-->
                <input type="hidden" name="selected_items[]" value="<?php echo intval($target_book_id); ?>">
            </form>

            <!--This submits the form above automatically-->
            <script>document.getElementById('autoBuyForm').submit();</script>
            <!--After submitting the form this message shows up to user while thay wait-->
            <p style="text-align: center; font-size: 1.2rem; margin-top: 50px;">Redirecting to secure checkout...</p>

        <!--If the auto submit for buy form is not set to True then runs this code-->    
        <?php else: ?>
            
            <!--Shows page main heading-->
            <h1 class="MainTitle">Preview Page</h1>

            <div class="PreviewGrid-Layout ">
                
                <div class="DataColumn LeftText">

                    <!--Box that shows the data of book price-->
                    <div class="info-group">
                        <span class="TextLabel">Price:</span>
                        <div class="Value-display-box">
                            RM <?php echo htmlspecialchars(number_format($book_data['book_price'], 2), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <!--Box that shows the data of book genre-->
                    <div class="info-group">
                        <span class="TextLabel">Book Genre:</span>
                        <div class="Value-display-box">
                            <?php echo htmlspecialchars($book_data['genre'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <!--Box that shows the data of book published date-->
                    <div class="info-group">
                        <span class="TextLabel">Published Date:</span>
                        <div class="Value-display-box">
                            <?php echo htmlspecialchars($book_data['publish_date'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <!--Box that shows the data of book author name-->
                    <div class="info-group">
                        <span class="TextLabel">Book Author:</span>
                        <div class="Value-display-box">
                            <?php echo htmlspecialchars($book_data['author'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>

                <!--Sets the book Name as the heading 2-->
                <div class="Center-content-column">
                    <h2 class="BookName">
                        [#<?php echo htmlspecialchars(str_pad($book_data['book_id'], 3, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?>] 
                        <?php echo htmlspecialchars($book_data['book_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    
                    <!--Box that shows the description of the book-->
                    <div class="DescriptionBox">
                        <h3 class="Heading-DescriptionBox">Book Description:</h3>
                        <div class="DescriptionContent-Format">
                            <p class="DescriptionText">
                                Welcome to "<?php echo htmlspecialchars($book_data['book_name'], ENT_QUOTES, 'UTF-8'); ?>", an essential reference workbook in our <?php echo htmlspecialchars($book_data['genre'], ENT_QUOTES, 'UTF-8'); ?> library category. Masterfully crafted by acclaimed industry specialist <?php echo htmlspecialchars($book_data['author'], ENT_QUOTES, 'UTF-8'); ?> and published on <?php echo htmlspecialchars($book_data['publish_date'], ENT_QUOTES, 'UTF-8'); ?>, this textbook offers clear step-by-step guides, code layout optimization workflows, and targeted skill development lessons. Download this collection resource today!
                            </p>
                        </div>
                    </div>

                    <!--Button area for BUY and Add To Cart button-->
                    <div class="ActioButtonRows">
                        <!--Sends the form data using POST method with an action value-->
                        <form action="IndividualBookPreviewPage.php" method="POST" class="inline-control-form" style="width: 100%; display: flex; gap: 15px; justify-content: center;">
                            <!--The input is hidden visually from users but it have the book id in here so the php code knows what book we are buying or adding to cart-->
                            <input type="hidden" name="id" value="<?php echo intval($book_data['book_id']); ?>">
                            
                            <!--BUY button-->
                            <button type="submit" name="action" value="buy_now" class="PreviewBtn">BUY</button>
                            <!--Add To Cart button-->
                            <button type="submit" name="action" value="add_to_cart" class="PreviewBtn">Add To Cart</button>
                        </form>
                    </div>
                </div>

                <div class="DataColumn RightText">
                    <!--Box to show current book stock-->
                    <div class="info-group">
                        <span class="TextLabel">Book Stock:</span>
                        <div class="Value-display-box">
                            <?php echo htmlspecialchars($book_data['stock'], ENT_QUOTES, 'UTF-8'); ?> units
                        </div>
                    </div>

                    <!--Box to show how much of this book were sold-->
                    <div class="info-group">
                        <span class="TextLabel">Units Sold:</span>
                        <div class="Value-display-box">
                            <?php echo htmlspecialchars($book_data['sold'], ENT_QUOTES, 'UTF-8'); ?> items sold
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>

</body>
</html>
