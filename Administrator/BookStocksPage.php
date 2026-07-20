<?php
/*Start session and bring in auto-check components and database setup*/
session_start();
include '../Component/AutoCheck.php';
require_once '../db_connect.php';

/*Redirect to login/role select if user is not logged in*/
if (!isset($_SESSION['logged_in_user'])) {
    header("Location: ../RoleSelectPage.php"); 
    exit();
}

/*Check session variables to identify the user's role*/
$raw_role = 'user';
if (isset($_SESSION['user_role'])) {
    $raw_role = $_SESSION['user_role'];

} elseif (isset($_SESSION['role'])) {
    $raw_role = $_SESSION['role'];
}

/*Remove extra spaces and make role lowercase for secure comparison*/
$normalized_role = strtolower(trim($raw_role));

/*Restrict page access strictly to administrators only*/
if ($normalized_role !== 'administrator' && $normalized_role !== 'admin') {
    header("Location: ../UserDashboardPage.php");
    exit();
}

/*Fetch all book inventory items from the Data Base*/
$book_data_db = [];

/*The code will check for error*/
try {
    /*Try to select book data from BookData table on Data Base*/
    $stmt = $pdo->query("SELECT book_id, book_name, book_price, stock FROM BookData ORDER BY book_id");
    /*Fetch all data that was recieved*/
    $book_data_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*If got error then the code sends error message to the back end server */
} catch (PDOException $e) {
    error_log($e->getMessage());
}

/*Calculate total available book count*/
$total_books_available = count($book_data_db);

/*Capture user search and sort selections from POST request*/
$search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
$sort_order = isset($_POST['sort']) ? $_POST['sort'] : 'default';



/*empty the filtered books array before runnug the codebelow*/
$filtered_books = [];

/*Loop through every book data that was retrieved and stores it temporarily inside of $row one at a time*/
foreach ($book_data_db as $row) {
    /*If search query is not empty runs this code*/
    if ($search_query !== '') {
        $formatted_book_id = str_pad((string)$row['book_id'], 4, '0', STR_PAD_LEFT);
        $formatted_price = "RM " . number_format((float)$row['book_price'], 2);
        /*If the book that user typed in does not exist then the code skips that Book and dont shows it*/
        if (stripos($formatted_book_id, $search_query) === false &&
            stripos($row['book_name'], $search_query) === false &&
            stripos((string)$row['stock'], $search_query) === false &&
            stripos($formatted_price, $search_query) === false) {
            continue;
        }
    }
    /*Append the matching book row to the filtered array*/
    $filtered_books[] = $row;
}


/*Sort the filtered inventory array based on selected dropdown option*/
if ($sort_order === 'id_asc') {
    usort($filtered_books, function($a, $b) { return (int)$a['book_id'] <=> (int)$b['book_id']; });
} elseif ($sort_order === 'name_asc') {
    usort($filtered_books, function($a, $b) { return strcasecmp($a['book_name'], $b['book_name']); });
} elseif ($sort_order === 'price_asc') {
    usort($filtered_books, function($a, $b) { return (float)$a['book_price'] <=> (float)$a['book_price']; });
} elseif ($sort_order === 'price_desc') {
    usort($filtered_books, function($a, $b) { return (float)$b['book_price'] <=> (float)$a['book_price']; });
} elseif ($sort_order === 'stock_desc') {
    usort($filtered_books, function($a, $b) { return (int)$b['stock'] <=> (int)$a['stock']; });
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Book Stocks Inventory</title>
    <link rel="stylesheet" href="../style/Header.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../style/BookStocks.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>

    <!--Renders the reusable navigation header component-->
    <?php include '../Component/Header.php'; ?>

    <main class="bs-workspace-container">
        <h1 class="bs-main-title">Book Stocks</h1>

        <div class="bs-inner-card-wrapper">
            
            <!--Search bar form with hidden sort field to preserve sorting state-->
            <form method="POST" action="BookStocksPage.php" id="bsFilterForm">
                <div class="bs-search-panel-row">
                    <input type="text" name="search" id="bsSearchInput" placeholder="Search Bar" autocomplete="off"
                           value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" class="bs-oval-search-bar">
                </div>
                <input type="hidden" id="currentSortVal" name="sort" value="<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">
            </form>

            <div class="bs-split-layout-grid">
                <div class="bs-table-data-column">
                    
                    <!--Sort selection dropdown menu-->
                    <div class="bs-sort-wrapper-row">
                        <select id="sortDropdownMenu" class="bs-interactive-selector-dropdown">
                            <option value="default" <?php echo htmlspecialchars($sort_order === 'default' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Sort Items</option>
                            <option value="id_asc" <?php echo htmlspecialchars($sort_order === 'id_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Book ID: Lowest</option>
                            <option value="name_asc" <?php echo htmlspecialchars($sort_order === 'name_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Book Name: A-Z</option>
                            <option value="price_asc" <?php echo htmlspecialchars($sort_order === 'price_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Lowest</option>
                            <option value="price_desc" <?php echo htmlspecialchars($sort_order === 'price_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Highest</option>
                            <option value="stock_desc" <?php echo htmlspecialchars($sort_order === 'stock_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Stock Level: Highest</option>
                        </select>
                    </div>

                    <!--Display filtered and sorted book inventory table-->
                    <table class="bs-rendered-data-table">
                        <thead>
                            <tr>
                                <th style="width: 8%;">No.</th>
                                <th style="width: 14%;">Book ID</th>
                                <th style="width: 38%;">Book Name</th>
                                <th style="width: 16%;">Price</th>
                                <th style="width: 12%;">Stock</th>
                                <th style="width: 12%;">Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_counter = 1;

                            /*If filtered books is more then 0 then loop through all to render it onto the table row*/
                            if (count($filtered_books) > 0):
                                foreach ($filtered_books as $book): 
                                    ?>
                                    <tr class="bs-row-item-tr">
                                        <td><?php echo htmlspecialchars($row_counter++, ENT_QUOTES, 'UTF-8'); ?>.</td>
                                        <td><?php echo htmlspecialchars(str_pad((string)$book['book_id'], 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($book['book_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>RM <?php echo htmlspecialchars(number_format((float)$book['book_price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((int)$book['stock'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center">
                                            <a href="EditBookDataPage.php?id=<?php echo htmlspecialchars((int)$book['book_id'], ENT_QUOTES, 'UTF-8'); ?>" class="bs-row-edit-link">EDIT</a>
                                        </td>
                                    </tr>
                                    <?php 
                                endforeach; 
                            else:
                                ?>
                                <tr>
                                    <td colspan="6" class="bs-empty-placeholder-cell">No matching stock items located.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sidebar showing total stock metrics -->
                <div class="bs-metrics-stats-sidebar">
                    <div class="bs-stat-group-block">
                        <span class="bs-stat-field-title">Total Books Available:</span>
                        <div class="bs-stat-display-box-value">
                            <span class="bs-stat-numeric-text"><?php echo htmlspecialchars($total_books_available, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!--Auto-submit form when sort dropdown selection changes-->
    <script>

        /*Locates the <select> drop down sort menu*/
        const sortDropdownMenu = document.getElementById('sortDropdownMenu');
        /*Locates the <form method="POST"> to submiting search bar form*/
        const bsFilterForm = document.getElementById('bsFilterForm');
        /*Locates the <input type="hidden"> thats inside of the form for choosing sorting option*/
        const currentSortVal = document.getElementById('currentSortVal');

        /*To update the selected sorting method once user click on a different sorting option*/
        sortDropdownMenu.addEventListener('change', function() {
            /*The newly selected sorting option value*/
            currentSortVal.value = this.value;
            /*Submits the form*/
            bsFilterForm.submit();
        });
    </script>
</body>
</html>