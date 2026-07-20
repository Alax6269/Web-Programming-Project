<?php
ob_start(); /*Holds data for a dit before sending to browser*/
        
session_start();
include '../Component/AutoCheck.php';
require_once '../db_connect.php';
if (!isset($_SESSION['logged_in_user'])) {
header("Location: ../RoleSelectPage.php"); 
    exit();
}

$raw_role = 'user';
if (isset($_SESSION['user_role'])) {
    $raw_role = $_SESSION['user_role'];
} elseif (isset($_SESSION['role'])) {
    $raw_role = $_SESSION['role'];
}
$normalized_role = strtolower(trim($raw_role));

if ($normalized_role !== 'administrator' && $normalized_role !== 'admin') {
    header("Location: ../UserDashboardPage.php");
    exit();
}

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_book = null;
$error_message = "";

try {
    $stmt = $pdo->prepare("SELECT book_id, book_name, book_price, stock FROM BookData WHERE book_id = :id");
    $stmt->execute(['id' => $book_id]);
    $current_book = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

if (!$current_book) {
    header("Location: BookStocksPage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book_action'])) {
    $updated_name  = trim($_POST['book_name']);
    $updated_stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    $updated_price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

    if ($updated_name === "" || $updated_stock === false || $updated_price === false) {
        $error_message = "Please provide valid parameters inputs across all edit fields values.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE BookData SET book_name = :name, stock = :stock, book_price = :price WHERE book_id = :id");
            $stmt->execute([
                'name'  => $updated_name,
                'stock' => $updated_stock,
                'price' => $updated_price,
                'id'    => $book_id
            ]);
            header("Location: BookStocksPage.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Failed to update book data.";
            error_log($e->getMessage());
        }
    }

    $current_book['book_name'] = $updated_name;
    $current_book['stock'] = $updated_stock;
    $current_book['book_price'] = $updated_price;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Edit Book Parameters</title>
    <link rel="stylesheet" href="../style/Header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style/EditBook.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include '../Component/Header.php'; ?>

    <main class="eb-workspace-container">
        <h1 class="eb-main-title"><?php echo htmlspecialchars($current_book['book_name'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($error_message !== ""): ?>
            <div class="eb-alert-banner eb-alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="eb-form-card-wrapper">
            <form method="POST" action="EditBookDataPage.php?id=<?php echo $book_id; ?>" class="eb-edit-data-form">
                <input type="hidden" name="update_book_action" value="1">

                <div class="eb-form-field-group">
                    <label for="bookStockInput" class="eb-field-label">Stock:</label>
                    <input type="number" name="stock" id="bookStockInput" min="0" required
                           value="<?php echo htmlspecialchars($current_book['stock'], ENT_QUOTES, 'UTF-8'); ?>" class="eb-rectangular-input-box">
                </div>

                <div class="eb-form-field-group">
                    <label for="bookNameInput" class="eb-field-label">Book Name:</label>
                    <input type="text" name="book_name" id="bookNameInput" required autocomplete="off"
                           value="<?php echo htmlspecialchars($current_book['book_name'], ENT_QUOTES, 'UTF-8'); ?>" class="eb-rectangular-input-box">
                </div>

                <div class="eb-form-field-group">
                    <label for="bookPriceInput" class="eb-field-label">Price:</label>
                    <input type="number" name="price" id="bookPriceInput" step="0.01" min="0.00" required
                           value="<?php echo htmlspecialchars(number_format((float)$current_book['book_price'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" class="eb-rectangular-input-box">
                </div>

                <div class="eb-action-submit-row">
                    <button type="submit" class="eb-confirm-action-btn">CONFIRM</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
