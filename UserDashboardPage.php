<?php
/*This code starts the session and check if the user is logged in or not*/
session_start();
if (file_exists('Component/AutoCheck.php')) {
    include 'Component/AutoCheck.php';
}


/*If the user in not logged in then redirect them to Role Select Page*/
if (!isset($_SESSION['logged_in_user'])) {
    header("Location: src/RoleSelectPage.php");
    exit();
}

require_once 'db_connect.php';


/*This code determine which user data to fetch for*/
$current_username = isset($_POST['user']) ? trim($_POST['user']) : $_SESSION['logged_in_user'];
$user_collections_db = [];
$total_books_owned = 0;


/*This code fetch data for the user that is logged in currently*/
try {
    $stmt = $pdo->prepare("SELECT book_id AS id, book_name AS name, price, purchase_date FROM UserCollection WHERE user_name = :user");
    $stmt->execute(['user' => $current_username]);
    $user_collections_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_books_owned = count($user_collections_db);
} catch (PDOException $e) {
    error_log($e->getMessage());
}


/*This code handles any POST request for searching or sorting for this page*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
    $sort_order = isset($_POST['sort']) ? $_POST['sort'] : 'default';
} else {
    $search_query = '';
    $sort_order = 'default';
}


/*This code stores the searched book name entered into a variable to use later*/
$filtered_collection = [];
foreach ($user_collections_db as $book) {
    if ($search_query !== '' && stripos($book['name'], $search_query) === false) {
        continue;
    }
    $filtered_collection[] = $book;
}

/*When a user select a sorting option in the page this code will handels the sorting logic based on their sorting option*/
if ($sort_order === 'name_asc') {
    usort($filtered_collection, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
} elseif ($sort_order === 'name_desc') {
    usort($filtered_collection, function($a, $b) { return strcasecmp($b['name'], $a['name']); });
} elseif ($sort_order === 'price_asc') {
    usort($filtered_collection, function($a, $b) { return $a['price'] <=> $b['price']; });
} elseif ($sort_order === 'price_desc') {
    usort($filtered_collection, function($a, $b) { return $b['price'] <=> $a['price']; });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | My Collection</title>
    <link rel="stylesheet" href="style/Header.css?v=<?php echo htmlspecialchars('1.0', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="style/Dashboard.css?v=<?php echo htmlspecialchars('1.0', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>

    <?php 
    if (file_exists('Component/Header.php')) {
        include 'Component/Header.php'; 
    }
    ?>

    <main class="collection-workspace">
        <h1 class="collection-main-title">Book Collection</h1>

        <!--Form container for filtering and sorting the collection-->
        <form method="POST" action="UserDashboardPage.php" id="collectionFilterForm">
            <!--Hidden context tracking token preserves admin navigation session across filtering queries-->
            <input type="hidden" name="user" value="<?php echo htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="search-wrapper-row">
                <!--This input is for search sorting data vis POST method for the table in this page-->
                <input type="text" name="search" id="collectionSearchInput" placeholder="Search Bar" autocomplete="off"
                       value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" class="collection-search-input">
            </div>

            <div class="collection-layout-split">
                <div class="table-content-side">
                    <!--Sorting dropdown menu will triggers an automatic form submission when selecting an option-->
                    <div class="sort-action-container">
                        <select name="sort" onchange="document.getElementById('collectionFilterForm').submit();" class="collection-sort-select">
                            <option value="default" <?php echo htmlspecialchars($sort_order === 'default' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Sort Items ▾</option>
                            <option value="name_asc" <?php echo htmlspecialchars($sort_order === 'name_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Name: Ascending</option>
                            <option value="name_desc" <?php echo htmlspecialchars($sort_order === 'name_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Name: Descending</option>
                            <option value="price_asc" <?php echo htmlspecialchars($sort_order === 'price_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Ascending</option>
                            <option value="price_desc" <?php echo htmlspecialchars($sort_order === 'price_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Price: Descending</option>
                        </select>
                    </div>
                    <!--This is the table that diplays the sorted Books Data-->
                    <table class="collection-table">
                        <thead>
                            <tr>
                                <th style="width: 8%;">No</th>
                                <th style="width: 47%;">Book Name</th>
                                <th style="width: 15%;">Price</th>
                                <th style="width: 18%;">Purchase Date</th>
                                <th style="width: 12%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_index = 1;
                            if (count($filtered_collection) > 0) {
                                foreach ($filtered_collection as $book) {
                                    ?>
                                    <tr class="collection-item-row">
                                        <td class="text-center"><?php echo htmlspecialchars($row_index++, ENT_QUOTES, 'UTF-8'); ?>.</td>
                                        <td><?php echo htmlspecialchars($book['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>RM <?php echo htmlspecialchars(number_format($book['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php echo !empty($book['purchase_date']) ? htmlspecialchars(date('d/m/Y', strtotime($book['purchase_date'])), ENT_QUOTES, 'UTF-8') : 'Pending Delivery'; ?>
                                        </td>
                                        <td class="text-center">
                                            <!--This button will triggers JavaScript to redirect user to a page to see the individual book info-->
                                            <button type="button" class="table-view-btn" onclick="goToOwnedPage(event, <?php echo intval($book['id']); ?>)" style="cursor: pointer; border: none; font-family: inherit;">VIEW</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #777;">No owned books match your current criteria filter parameters.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <!--This code is to show the books that user owns-->
                <div class="metrics-panel-side">
                    <div class="metric-display-group">
                        <span class="metric-label">Total Books Owned:</span>
                        <div class="metric-box-output"><?php echo intval($total_books_owned); ?></div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
        /*This JS code is the code that automatically updates the table based on search and sort selected*/
        const filterForm = document.getElementById('collectionFilterForm');
        const searchInput = document.getElementById('collectionSearchInput');

        if (searchInput && filterForm) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    HTMLFormElement.prototype.submit.call(filterForm);
                }
            });
        }

        /*This code creates and submits a form to view the individual dook data*/
        function goToOwnedPage(e, bookId) {
            if (e) e.preventDefault(); 

            const dynamicForm = document.createElement('form');
            dynamicForm.method = 'POST';
            dynamicForm.action = 'IndividualOwnedBookPage.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id'; 
            input.value = parseInt(bookId);

            dynamicForm.appendChild(input);
            document.body.appendChild(dynamicForm);
            HTMLFormElement.prototype.submit.call(dynamicForm);
        }
    </script>
</body>
</html>
