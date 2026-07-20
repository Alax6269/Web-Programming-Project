<?php
session_start();
// Look "up" to find Component folder
include '../Component/AutoCheck.php';
require_once '../db_connect.php';
if (!isset($_SESSION['logged_in_user'])) {
header("Location: ../RoleSelectPage.php"); // Parent relative path
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


$selected_user_name = isset($_POST['user']) ? trim($_POST['user']) : '';

if ($selected_user_name === '') {
    header("Location: EditDataBasePage.php");
    exit();
}

$user_books = [];
$total_books_owned = 0;
$total_spendings = 0;

try {
    $stmt = $pdo->prepare("SELECT book_id, book_name, price, purchase_date FROM UserCollection WHERE user_name = :user ORDER BY purchase_date DESC");
    $stmt->execute(['user' => $selected_user_name]);
    $user_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_books_owned = count($user_books);
    foreach ($user_books as $book) {
        $total_spendings += (float)$book['price'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}


$search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
$sort_order = isset($_POST['sort']) ? $_POST['sort'] : 'default';

$filtered_books = [];
foreach ($user_books as $row) {
    if ($search_query !== '') {
        $formatted_price = "RM " . number_format((float)$row['price'], 2);
        $formatted_date = !empty($row['purchase_date']) ? date('d/m/Y', strtotime($row['purchase_date'])) : '';

        $formatted_book_id = str_pad((string)$row['book_id'], 4, '0', STR_PAD_LEFT);
        if (stripos($formatted_book_id, $search_query) === false &&
            stripos($row['book_name'], $search_query) === false &&
            ($formatted_date === '' || stripos($formatted_date, $search_query) === false) &&
            stripos($formatted_price, $search_query) === false) {
            continue;
        }
    }
    $filtered_books[] = $row;
}

if ($sort_order === 'id_asc') {
    usort($filtered_books, function($a, $b) { return (int)$a['book_id'] <=> (int)$b['book_id']; });
} elseif ($sort_order === 'name_asc') {
    usort($filtered_books, function($a, $b) { return strcasecmp($a['book_name'], $b['book_name']); });
} elseif ($sort_order === 'price_asc') {
    usort($filtered_books, function($a, $b) { return (float)$a['price'] <=> (float)$b['price']; });
} elseif ($sort_order === 'price_desc') {
    usort($filtered_books, function($a, $b) { return (float)$b['price'] <=> (float)$a['price']; });
} elseif ($sort_order === 'date_desc') {
    usort($filtered_books, function($a, $b) { return strcmp((string)$b['purchase_date'], (string)$a['purchase_date']); });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | User Data View</title>
    <link rel="stylesheet" href="../style/Header.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../style/UserData.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>

    <?php include '../Component/Header.php'; ?>

    <main class="ud-workspace-container">
        <h1 class="ud-main-title">User Data - <?php echo htmlspecialchars($selected_user_name, ENT_QUOTES, 'UTF-8'); ?></h1>

        <div class="ud-inner-card-wrapper">
           
            <form method="POST" action="UserDataPage.php" id="udFilterForm">
                <input type="hidden" name="user" value="<?php echo htmlspecialchars($selected_user_name, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ud-search-panel-row">
                    <input type="text" name="search" id="udSearchInput" placeholder="Search Bar" autocomplete="off"
                           value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" class="ud-oval-search-bar">
                </div>
                <input type="hidden" id="currentSortVal" name="sort" value="<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">
            </form>

            <div class="ud-split-layout-grid">
                <div class="ud-table-data-column">
                    <div class="ud-sort-wrapper-row">
                        <select id="sortDropdownMenu" class="ud-interactive-selector-dropdown">
                            <option value="default" <?php echo htmlspecialchars($sort_order === 'default' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Sort Items</option>
                            <option value="id_asc" <?php echo htmlspecialchars($sort_order === 'id_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Book ID: Lowest</option>
                            <option value="name_asc" <?php echo htmlspecialchars($sort_order === 'name_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Book Name: A-Z</option>
                            <option value="price_asc" <?php echo htmlspecialchars($sort_order === 'price_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Lowest</option>
                            <option value="price_desc" <?php echo htmlspecialchars($sort_order === 'price_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Highest</option>
                            <option value="date_desc" <?php echo htmlspecialchars($sort_order === 'date_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Purchase Date: Newest</option>
                        </select>
                    </div>

                    <table class="ud-rendered-data-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">No.</th>
                                <th style="width: 15%;">Book ID</th>
                                <th style="width: 45%;">Book Name</th>
                                <th style="width: 15%;">Price</th>
                                <th style="width: 15%;">Purchase Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_counter = 1;
                            if (count($filtered_books) > 0):
                                foreach ($filtered_books as $book): 
                                    ?>
                                    <tr class="ud-row-item-tr">
                                        <td><?php echo htmlspecialchars($row_counter++, ENT_QUOTES, 'UTF-8'); ?>.</td>
                                        <td><?php echo htmlspecialchars(str_pad((string)$book['book_id'], 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($book['book_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>RM <?php echo htmlspecialchars(number_format((float)$book['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(!empty($book['purchase_date']) ? date('d/m/Y', strtotime($book['purchase_date'])) : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <?php 
                                endforeach; 
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" class="bs-empty-placeholder-cell">No matching purchase records were located.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ud-metrics-stats-sidebar">
                    <div class="ud-stat-group-block">
                        <span class="ud-stat-field-title">Total Books User Own:</span>
                        <div class="ud-stat-display-box-value">
                            <span class="bs-stat-numeric-text"><?php echo htmlspecialchars($total_books_owned, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <div class="ud-stat-group-block">
                        <span class="ud-stat-field-title">User Total Spendings(RM):</span>
                        <div class="ud-stat-display-box-value">
                            <span class="bs-stat-numeric-text">RM <?php echo htmlspecialchars(number_format($total_spendings, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const sortDropdownMenu = document.getElementById('sortDropdownMenu');
        const udFilterForm = document.getElementById('udFilterForm');
        const currentSortVal = document.getElementById('currentSortVal');

        sortDropdownMenu.addEventListener('change', function() {
            currentSortVal.value = this.value;
            udFilterForm.submit();
        });
    </script>
</body>
</html>
