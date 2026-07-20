<?php
session_start();
include 'Component/AutoCheck.php';
require_once 'db_connect.php';

/*If USer session is not defined then redirect user to LoginPage.php*/
if (!isset($_SESSION['logged_in_user'])) {
    echo "<script>window.location.href = 'LoginPage.php';</script>";
    exit();
}

/*Starts the session for Cart if it is empty*/
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*Any POST request from any pages that send 'id' or 'book_id' will trigger this code*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['book_id']) || isset($_POST['id']))) {
    $incoming_id = isset($_POST['id']) ? intval($_POST['id']) : intval($_POST['book_id']);
    if ($incoming_id > 0 && !in_array($incoming_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $incoming_id;
    }
}

/*Manages the delete request if theres any*/ 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        foreach ($_POST['selected_items'] as $id_to_delete) {
            $key = array_search(intval($id_to_delete), $_SESSION['cart']);
            if ($key !== false) {
                unset($_SESSION['cart'][$key]);
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}


$search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
$current_user = $_SESSION['logged_in_user'];
$mysql_book_data = [];


if (!empty($_SESSION['cart'])) {
    try {
        /*Takes data from Data Base table named BookData*/
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        $stmt = $pdo->prepare("SELECT book_id, book_name, book_price, stock FROM BookData WHERE book_id IN ($placeholders)");
        $stmt->execute($_SESSION['cart']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        /*Using the value of $current_user the code takes the data of all books that this user ownes from UserData Table in DataBase*/
        $owned_stmt = $pdo->prepare("SELECT book_id FROM UserCollection WHERE user_name = ?");
        $owned_stmt->execute([$current_user]);
        $owned_book_ids = $owned_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($results as $book) {
            $book_id_int = intval($book['book_id']);
            
           /*If the book data such as book_id taken from BookData table match with data in UserCollection DataBase Table then stores value of YES or No in variable named $is_owned for each individual book*/
            $is_owned = in_array($book_id_int, $owned_book_ids) ? 'YES' : 'NO';

            $mysql_book_data[$book_id_int] = [
                'name'  => $book['book_name'],
                'price' => floatval($book['book_price']),
                'stock' => intval($book['stock']),
                'owned' => $is_owned 
            ];
        }
    } catch (PDOException $e) {
        error_log("Cart fetch error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Cart</title>
    <link rel="stylesheet" href="style/Header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/Cart.css?v=<?php echo time(); ?>">
</head>
<body>

     <?php include 'Component/Header.php'; ?>

    <main class="cart-workspace">
        <h1 class="cart-title">Cart Page</h1>

        <form id="cartForm" method="POST" action="CartPage.php">
            <div class="cart-layout-grid">
                
                <div class="table-container-side">
                    <div class="search-wrapper">
                        <input type="text" name="search" id="cartSearchInput" placeholder="Search Here" 
                               value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" class="cart-search-input">
                    </div>

                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><input type="checkbox" id="selectAllCheckbox"></th>
                                <th style="width: 50%;">Book Name</th>
                                <th style="width: 15%;">Price</th>
                                <th style="width: 15%;">Available Stock</th>
                                <th style="width: 15%;">Owned?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $displayed_items_count = 0;
                            foreach ($_SESSION['cart'] as $book_id) {
                                if (isset($mysql_book_data[$book_id])) {
                                    $item = $mysql_book_data[$book_id];
                                    if ($search_query !== '' && stripos($item['name'], $search_query) === false) {
                                        continue;
                                    }
                                    $displayed_items_count++;
                                    ?>
                                    <tr class="cart-item-row">
                                        <td>
                                            <input type="checkbox" name="selected_items[]" value="<?php echo $book_id; ?>" 
                                                   class="item-checkbox" data-price="<?php echo $item['price']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>RM <?php echo htmlspecialchars(number_format($item['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="font-weight: bold; color: #000000;">
                                             <?php echo htmlspecialchars($item['owned'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            if ($displayed_items_count === 0):
                            ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #777;">Your cart folder matches zero records.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-control-side">
                    <button type="submit" name="action" value="delete" class="cart-side-btn delete-btn">DELETE</button>

                    <div class="metric-display-group">
                        <span class="metric-label">Item Selected:</span>
                        <div class="metric-box" id="SelectedBooks">0</div>
                    </div>

                    <div class="metric-display-group">
                        <span class="metric-label">Total Price</span>
                        <div class="metric-box" id="TotalPrice">RM 0.00</div>
                    </div>

                    <button type="button" onclick="submitToPurchase()" class="cart-side-btn purchase-btn">PURCHASE</button>
                </div>

            </div>
        </form>
    </main>

    <script>
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectedBooksDisplay = document.getElementById('SelectedBooks');
        const totalPriceDisplay = document.getElementById('TotalPrice');
        const cartForm = document.getElementById('cartForm');
        const searchInput = document.getElementById('cartSearchInput');

        function calculateCartSummary() {
            let checkedCount = 0;
            let combinedTotalPrice = 0.00;

            itemCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    checkedCount++;
                    const priceValue = parseFloat(checkbox.getAttribute('data-price'));
                    if (!isNaN(priceValue)) {
                        combinedTotalPrice += priceValue;
                    }
                }
            });

            selectedBooksDisplay.textContent = checkedCount;
            totalPriceDisplay.textContent = 'RM ' + combinedTotalPrice.toFixed(2);
        }

        if(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    
                    checkbox.checked = this.checked;
                });
                calculateCartSummary();
            });
        }

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', calculateCartSummary);
        });

        if(searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    cartForm.action = 'CartPage.php';
                    HTMLFormElement.prototype.submit.call(cartForm);
                }
            });
        }

        function submitToPurchase() {
            let hasChecked = false;
            itemCheckboxes.forEach(cb => { if(cb.checked) hasChecked = true; });
            
            if(!hasChecked) {
                alert('Please select at least one item to purchase.');
                return;
            }
            
            cartForm.action = 'PurchaseConfirmationPage.php';
            HTMLFormElement.prototype.submit.call(cartForm);
        }
    </script>
</body>
</html>
