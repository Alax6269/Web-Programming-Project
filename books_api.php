<?php
/*Tell the browser to expect a JSON response instead of standard HTML*/
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';


/*Capture and format URL parameters in a secure way*/
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit; /*If the book data shown is 50 then tells the SQL to skip to Book data 51 once the first 50 Book data is loaded*/

$limit = ($limit < 1 || $limit > 200) ? 50 : $limit;

/*Set the sort SQL variable to be empty before getting any data*/
$sort_sql = '';
/*USes the  front end values that was defined for sorting options to tell the SQL what data to specifically to get*/
switch ($sort) {
    case 'name_asc': $sort_sql = 'ORDER BY book_name ASC'; break;
    case 'name_desc': $sort_sql = 'ORDER BY book_name DESC'; break;
    case 'price_asc': $sort_sql = 'ORDER BY book_price ASC'; break;
    case 'price_desc': $sort_sql = 'ORDER BY book_price DESC'; break;
    default: $sort_sql = 'ORDER BY book_name ASC';
}

/*the code will try to run this code and see if there is any error, if so then the user wont see it but the back end server will recieve it*/
try {
    /*If the search query is empty then run this code*/
    if ($q === '') {
        /*Sort based on $sort option choosen by user*/
        $stmt = $pdo->prepare("SELECT book_id, book_name, book_price FROM BookData $sort_sql LIMIT :limit OFFSET :offset");
        /*Limits the sort to be up to 50 books*/
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        /*Show the sorted Books in 1 page*/
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        /*This send the command to SQL to retrive the asked data just now*/
        $stmt->execute();

    /*If the serch query is not empty runs this code*/
    } else {
        /*If user types "The" then this code will look for all books with that 3 letters*/
        $term = '%' . $q . '%';
        /*The code prepares the book data from BookData table from Date Base*/
        $stmt = $pdo->prepare("SELECT book_id, book_name, book_price FROM BookData WHERE book_name LIKE :term $sort_sql LIMIT :limit OFFSET :offset");

        /*In order to prevent MySQL injection and data integrity the term, limit and offset is defined before showing the book that the user searched for*/
        $stmt->bindValue(':term', $term, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        /*This send the command to SQL to retrive the asked data just now*/
        $stmt->execute();
    }

    /*Stores all the book data that was sorted by the upper search code inside of $rows*/
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    /*Encodes the data retrieved for JSON so that JavaScript can use this data on its display code for user to see*/
    echo json_encode(['success' => true, 'books' => $rows]);

/*If the code throws an error then catches the error and encode the error msg to JSON so JavaScript can read and output it for user to see*/
} catch (PDOException $e) {
    http_response_code(500);/*Error 500*/
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
