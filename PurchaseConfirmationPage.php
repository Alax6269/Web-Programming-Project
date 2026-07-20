<?php
session_start();
include 'Component/AutoCheck.php';
require_once 'db_connect.php';

/*If user is not logged in then redirect them to Login Page*/
if (!isset($_SESSION['logged_in_user'])) {
    echo "<script>window.location.href = 'LoginPage.php';</script>";
    exit();
}

/*Is the user did not select any books from the cart table then the code will redirect them back to the cart page instead of bringin them to purchase page*/
if (!isset($_POST['selected_items']) || !is_array($_POST['selected_items'])) {
    echo "<script>window.location.href = 'CartPage.php';</script>";
    exit();
}


/*Values are stored here*/
$current_user = $_SESSION['logged_in_user'];
$items_to_buy = array_map('intval', $_POST['selected_items']);
$is_confirmed = isset($_POST['confirm_purchase']);
$total_cost = 0.00;
$success_status = false;
$error_message = '';

/*Before clicking the confirm button the code will calculate the total price of selected book using this code*/
if (!$is_confirmed) {
    try {
        foreach ($items_to_buy as $book_id) {
            $stmt = $pdo->prepare("SELECT book_price FROM BookData WHERE book_id = :id");
            $stmt->execute(['id' => $book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($book) {
                $total_cost += floatval($book['book_price']);
            }
        }
    } catch (Exception $e) {
        $total_cost = 0;
    }

/*Now if the user have clicked the confirm button then this code will run and process the purchase*/    
} else {
    try {
        $pdo->beginTransaction();
        
        /*Chech if the uer own this book and if the book is in stock before confirming the purchase*/
        foreach ($items_to_buy as $book_id) {
            $stmt = $pdo->prepare("SELECT * FROM BookData WHERE book_id = :id");
            $stmt->execute(['id' => $book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$book) {
                throw new Exception("Book ID #{$book_id} not found.");
            }


            if (intval($book['stock']) <= 0) {
                throw new Exception("'{$book['book_name']}' is out of stock!");
            }


            $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserCollection WHERE user_name = :user AND book_id = :id");
            $stmt->execute(['user' => $current_user, 'id' => $book_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("You already own '{$book['book_name']}'.");
            }

            $total_cost += floatval($book['book_price']);
        }

        $entered_cash = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0.00;
        if ($entered_cash < $total_cost) {
            throw new Exception("Insufficient Funds! Received RM " . number_format($entered_cash, 2) . ", but needed RM " . number_format($total_cost, 2) . ".");
        }

       
        /*This code update the total spending of the user and total books that they own to the data base*/
        $stmt = $pdo->prepare("INSERT INTO UserData (user_name, book_owned, total_spending) 
                               VALUES (:user, :books_count, :spending)
                               ON DUPLICATE KEY UPDATE 
                               book_owned = book_owned + :books_count, 
                               total_spending = total_spending + :spending");
        $stmt->execute([
            'user'        => $current_user,
            'books_count' => count($items_to_buy),
            'spending'    => $total_cost
        ]);


        /*After user buys a book the data base will get its stock updated by this code*/
        foreach ($items_to_buy as $book_id) {
            $stmt = $pdo->prepare("SELECT * FROM BookData WHERE book_id = :id");
            $stmt->execute(['id' => $book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("INSERT INTO UserCollection (user_name, book_id, book_name, price, purchase_date) 
                                   VALUES (:user, :id, :name, :price, NOW())");
            $stmt->execute([
                'user'  => $current_user,
                'id'    => $book['book_id'],
                'name'  => $book['book_name'],
                'price' => $book['book_price']
            ]);

            $stmt = $pdo->prepare("UPDATE BookData SET stock = stock - 1, sold = sold + 1 WHERE book_id = :id");
            $stmt->execute(['id' => $book['book_id']]);
 
            
            /*If the book is purchased then removes it from the user cart table*/
            $key = array_search($book_id, $_SESSION['cart']);
            if ($key !== false) {
                unset($_SESSION['cart'][$key]);
            }
        }

        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $pdo->commit();
        $success_status = true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Checkout Confirmation</title>
    <link rel="stylesheet" href="style/Header.css?v=1.0">
    <link rel="stylesheet" href="style/PurchaseConfirmation.css?v=1.0">
</head>
<body>

    <?php include 'Component/Header.php'; ?>

    <main class="Confirmation-Area">
        <!--If the state of form submission is Succesful then this code runs-->
        <?php if ($success_status): ?>
            <h1 class="success-heading">Purchase Successful!</h1>
            <p class="success-message">Your item transaction processed flawlessly. Your newly purchased textbooks have been added to your Collections.</p>
            <div class="cost-text">Total Debited: RM <?php echo number_format($total_cost, 2); ?></div>
            <?php if ($entered_cash > $total_cost): ?>
                <div class="change-text" style="font-weight: bold; margin-top: 10px; color: #00ff00;">Change: RM <?php echo number_format($entered_cash - $total_cost, 2); ?></div>
            <?php endif; ?>
            <a href="UserDashboardPage.php" class="action-link">Go To My Collections →</a>
        
        <!--Shows this insted if the form submission is unsuccesfull-->    
        <?php elseif ($is_confirmed && !$success_status): ?>
            <h1 class="failed-heading">Transaction Failed</h1>
            <p class="error-msg-box">
                <strong>Reason:</strong> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <a href="CartPage.php" class="action-link">Return to Cart</a>
            


        <!--This code is for the form for confirming purchase-->    
        <?php else: ?>
            <h1 class="confirm-heading">Confirm Purchase?</h1>
            
            <form method="POST" action="PurchaseConfirmationPage.php">
                <?php foreach ($items_to_buy as $book_id): ?>
                    <input type="hidden" name="selected_items[]" value="<?php echo intval($book_id); ?>">
                <?php endforeach; ?>
                
                <div class="confirm-form">
                    <div class="form-group">
                        <!--Shows Total Price-->
                        <label for="total">Total Is:</label>
                        <input type="text" id="total" value="RM <?php echo number_format($total_cost, 2); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <!--Promt For Entering Money-->
                        <label for="money">Enter Money:</label>
                        <input type="number" id="money" name="amount_paid" placeholder="Enter amount" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <!--Confirm button to submit the form-->
                    <button type="submit" name="confirm_purchase" value="1" class="confirm-btn">CONFIRM</button>
                    <a href="CartPage.php" class="cancel-btn" style="text-decoration: none; margin-left: 10px;">CANCEL</a>
                </div>
            </form>
        <?php endif; ?>
    </main>


    <script>

    /*Client-side JavaScript to validate sufficient funds before submitting the form*/
    document.addEventListener("DOMContentLoaded", function () {
        /*form = form  that have the action set to PurchaseConfirmationPage.php*/
        const form = document.querySelector("form[action='PurchaseConfirmationPage.php']");
        /*If form is empty then stops the rest of the function code*/
        if (!form) return;
         
        /*Tels JS to hold onto submit data money and total for event function*/
        form.addEventListener("submit", function (event) {
            const moneyInput = document.getElementById("money");
            const totalInput = document.getElementById("total");
            
            /*If there is either no money input or total input then the code stops here*/
            if (!moneyInput || !totalInput) return;
            
            /*Defines entered amount for JS to understand or set it to 0*/
            const enteredAmount = parseFloat(moneyInput.value) || 0;
            /*Converts string input to decimals for JS to understand or set it to 0*/
            const totalCost = parseFloat(totalInput.value.replace(/[^\d.]/g, '')) || 0;

            /*if entered amount is lower then total cost then the JS will not send back data to PHP and will not refresh the page*/
            if (enteredAmount < totalCost) {
                event.preventDefault(); 
                /*The allert message after preventing default form submission*/
                alert("Insufficient Funds! You entered RM " + enteredAmount.toFixed(2) + " but the total cost is RM " + totalCost.toFixed(2) + ".");
                
                moneyInput.style.borderColor = "#cc0000";
                moneyInput.style.color = "#cc0000";
                /*Automatically puts user cursor onto the money input box for better UX design*/
                moneyInput.focus();
            }
        });
    });
    </script>
</body>
</html>
