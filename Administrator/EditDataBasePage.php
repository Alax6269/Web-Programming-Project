<?php
include '../Component/AutoCheck.php';
require_once '../db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in_user'])) {
    header("Location: ../RoleSelectPage.php");
    exit();
}

$raw_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'user';
$normalized_role = strtolower(trim($raw_role));

if ($normalized_role !== 'administrator' && $normalized_role !== 'admin') {
    header("Location: ../UserDashboardPage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected_action'])) {
    $selected_users = isset($_POST['selected_users']) && is_array($_POST['selected_users'])
        ? array_map('trim', $_POST['selected_users'])
        : [];

    if (!empty($selected_users)) {
        try {
            $pdo->beginTransaction();
            $delete_collection_stmt = $pdo->prepare("DELETE FROM UserCollection WHERE user_name = :user_name");
            $delete_user_stmt = $pdo->prepare("DELETE FROM UserData WHERE user_name = :user_name");

            foreach ($selected_users as $user_name) {
                if ($user_name === '') {
                    continue;
                }
                $delete_collection_stmt->execute(['user_name' => $user_name]);
                $delete_user_stmt->execute(['user_name' => $user_name]);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Delete error: " . $e->getMessage());
        }
    }

    header("Location: EditDataBasePage.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT user_name, account_created_date, book_owned, total_spending FROM UserData ORDER BY user_name");
    $user_data_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user_data_db = [];
    error_log("Database error: " . $e->getMessage());
}

$search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
$sort_order = isset($_POST['sort']) ? $_POST['sort'] : 'default';

$filtered_users = [];
foreach ($user_data_db as $row) {
    if ($search_query !== '') {
        $formatted_price = "RM " . number_format((float)$row['total_spending'], 2);
        $formatted_date = !empty($row['account_created_date'])
            ? date('d/m/Y', strtotime($row['account_created_date']))
            : '';

        if (stripos($row['user_name'], $search_query) === false &&
            ($formatted_date === '' || stripos($formatted_date, $search_query) === false) &&
            stripos((string)$row['book_owned'], $search_query) === false &&
            stripos($formatted_price, $search_query) === false) {
            continue;
        }
    }
    $filtered_users[] = $row;
}

if ($sort_order === 'name_asc') {
    usort($filtered_users, function($a, $b) { return strcasecmp($a['user_name'], $b['user_name']); });
} elseif ($sort_order === 'name_desc') {
    usort($filtered_users, function($a, $b) { return strcasecmp($b['user_name'], $a['user_name']); });
} elseif ($sort_order === 'books_desc') {
    usort($filtered_users, function($a, $b) { return (int)$b['book_owned'] <=> (int)$a['book_owned']; });
} elseif ($sort_order === 'spending_desc') {
    usort($filtered_users, function($a, $b) { return (float)$b['total_spending'] <=> (float)$a['total_spending']; });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Database Admin Panel</title>
    <link rel="stylesheet" href="../style/Header.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../style/EditDB.css?v=<?php echo htmlspecialchars(time(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>

    <?php include '../Component/Header.php'; ?>

    <main class="db-workspace-container">
        <h1 class="db-main-title">Data Base</h1>

        <div class="db-inner-card-wrapper">
            <div class="db-top-utility-actions">
                <a href="BookStocksPage.php" class="db-wireframe-link-btn">Book Stocks</a>
            </div>

           
            <form method="POST" action="EditDataBasePage.php" id="dbFilterForm">
                <div class="db-search-panel-row">
                    <input type="text" name="search" id="dbSearchInput" placeholder="Search Bar" autocomplete="off"
                           value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" class="db-oval-search-bar">
                </div>
                <input type="hidden" id="currentSortVal" name="sort" value="<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">
            </form>

            <form method="POST" action="EditDataBasePage.php" id="dbActionForm">
                <input type="hidden" name="delete_selected_action" value="1">

                <div class="db-split-layout-grid">
                    <div class="db-table-data-column">
                        <div class="db-sort-wrapper-row">
                            <select id="sortDropdownMenu" class="db-interactive-selector-dropdown">
                                <option value="default" <?php echo htmlspecialchars($sort_order === 'default' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Sort Items</option>
                                <option value="name_asc" <?php echo htmlspecialchars($sort_order === 'name_asc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>User Name: A-Z</option>
                                <option value="name_desc" <?php echo htmlspecialchars($sort_order === 'name_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>User Name: Z-A</option>
                                <option value="books_desc" <?php echo htmlspecialchars($sort_order === 'books_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Books Owned: Highest</option>
                                <option value="spending_desc" <?php echo htmlspecialchars($sort_order === 'spending_desc' ? 'selected' : '', ENT_QUOTES, 'UTF-8'); ?>>Total Spending: Highest</option>
                            </select>
                        </div>

                        <table class="db-mysql-render-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;"><input type="checkbox" id="selectAllRowsMaster"></th>
                                    <th style="width: 8%;">No.</th>
                                    <th style="width: 25%;">User Name</th>
                                    <th style="width: 24%;">Account Created Date</th>
                                    <th style="width: 15%;">Books Owned</th>
                                    <th style="width: 15%;">Total Spending(RM)</th>
                                    <th style="width: 8%;">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $row_counter = 1;
                                if (count($filtered_users) > 0):
                                    foreach ($filtered_users as $user): 
                                        ?>
                                        <tr class="db-row-item-tr">
                                            <td class="text-center">
                                                <input type="checkbox" name="selected_users[]" value="<?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?>" class="row-record-checkbox">
                                            </td>
                                            <td><?php echo htmlspecialchars($row_counter++, ENT_QUOTES, 'UTF-8'); ?>.</td>
                                            <td><?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(!empty($user['account_created_date']) ? date('d/m/Y', strtotime($user['account_created_date'])) : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((int)$user['book_owned'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>RM <?php echo htmlspecialchars(number_format((float)$user['total_spending'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                               
                                                <a href="#" class="db-row-view-link" onclick="submitUserDashboardRedirect(event, '<?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?>')">VIEW</a>
                                            </td>
                                        </tr>
                                        <?php 
                                    endforeach; 
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="db-empty-placeholder-cell">No matching UserData records were found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="db-metrics-action-sidebar">
                        <div class="indicator-group-block">
                            <span class="indicator-field-title">User Selected:</span>
                            <div id="userSelectedBox" class="indicator-display-box-value">0</div>
                        </div>

                        <div class="indicator-group-block">
                            <span class="indicator-field-title">Total Selected:</span>
                            <div id="totalSelectedBox" class="indicator-display-box-value">0</div>
                        </div>

                        <div class="action-trigger-wrapper">
                            <button type="submit" class="db-delete-action-btn" onclick="return confirm('Are you sure you want to delete the selected user records from the system database?')">Delete Data</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        const sortDropdownMenu = document.getElementById('sortDropdownMenu');
        const dbFilterForm = document.getElementById('dbFilterForm');
        const currentSortVal = document.getElementById('currentSortVal');
        const selectAllRowsMaster = document.getElementById('selectAllRowsMaster');
        const rowRecordCheckboxes = document.querySelectorAll('.row-record-checkbox');
        const userSelectedBox = document.getElementById('userSelectedBox');
        const totalSelectedBox = document.getElementById('totalSelectedBox');

        sortDropdownMenu.addEventListener('change', function() {
            currentSortVal.value = this.value;
            dbFilterForm.submit();
        });

        
        function submitUserDashboardRedirect(e, userName) {
            if (e) e.preventDefault();
            
            const portalForm = document.createElement('form');
            portalForm.method = 'POST';
            portalForm.action = '../UserDashboardPage.php';

            const payloadInput = document.createElement('input');
            payloadInput.type = 'hidden';
            payloadInput.name = 'user';
            payloadInput.value = userName;

            portalForm.appendChild(payloadInput);
            document.body.appendChild(portalForm);
            portalForm.submit();
        }

        function recalculateSelectionMetrics() {
            let checkedCount = 0;
            rowRecordCheckboxes.forEach(box => {
                if(box.checked) checkedCount++;
            });
            userSelectedBox.textContent = checkedCount;
            totalSelectedBox.textContent = checkedCount;
        }

        if(selectAllRowsMaster) {
            selectAllRowsMaster.addEventListener('change', function() {
                rowRecordCheckboxes.forEach(box => {
                    box.checked = this.checked;
                });
                recalculateSelectionMetrics();
            });
        }

        rowRecordCheckboxes.forEach(box => {
            box.addEventListener('change', recalculateSelectionMetrics);
        });
    </script>
</body>
</html>

