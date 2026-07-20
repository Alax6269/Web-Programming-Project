<?php
session_start();
include 'Component/AutoCheck.php';

if (!isset($_SESSION['logged_in_user'])) {
    header("Location: RoleSelectPage.php");
    exit();
}

/*Stores the current search and sort option into a variable*/
$search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
$sort_option = isset($_POST['sort']) ? $_POST['sort'] : 'default';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book-Flash | Home</title>
    <link rel="stylesheet" href="style/Header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/Homepage.css?v=<?php echo time(); ?>">
</head>
<body>

    <!--Shared Navigation Bar Element Component-->
    <?php include 'Component/Header.php'; ?>

    <main class="ContentBox">
        <!--Main header of the page-->
        <h1>Welcome To E-Book-Flash</h1>
        <p class="SubTxt">Type In Book Name Down Below</p>


        <!--Section for search form-->
        <div class="SearchBar">
            <form id="searchForm" method="POST" action="Homepage.php" style="width:100%;">
                 <!--This input is visually hidden from users but is still sent to the back end code-->
                <input type="hidden" id="sortInput" name="sort" value="<?php echo htmlspecialchars($sort_option, ENT_QUOTES, 'UTF-8'); ?>">
                 <!--As the user types in the search bar it will print it onto the search bar for user to see-->
                <input type="text" id="searchInput" name="search" placeholder="Search Bar" class="SearchInput" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
        </div>

       
        <div class="CatalogContainer">
             <!--Section for selecting th sorting option-->
            <div class="CatalogHeader">
                 <!--Sub Title-->
                <span class="SectionTitle">Available Books</span>
               
                <form id="sortForm" method="POST" action="Homepage.php" class="sort-form">
                     <!--This input is hidden from user but it still send the search value to the back end code-->
                    <input type="hidden" id="searchHidden" name="search" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                    
                     <!--When a user click on any 5 options for sorting the sort option will be set to what thay clicked on-->
                    <select id="sortSelect" name="sort" class="Sort">
                        <option value="default" <?php echo $sort_option === 'default' ? 'selected' : ''; ?>>Sort Items ▾</option>
                        <option value="name_asc" <?php echo $sort_option === 'name_asc' ? 'selected' : ''; ?>>Name Ascending</option>
                        <option value="name_desc" <?php echo $sort_option === 'name_desc' ? 'selected' : ''; ?>>Name Descending</option>
                        <option value="price_asc" <?php echo $sort_option === 'price_asc' ? 'selected' : ''; ?>>Price Ascending</option>
                        <option value="price_desc" <?php echo $sort_option === 'price_desc' ? 'selected' : ''; ?>>Price Descending</option>
                    </select>
                </form>
            </div>

             <!--Sectoin to show 'Loading books' if the page havent loaded in every books-->
            <div id="booksContainer">
                <p id="loadingMsg">Loading books…</p>
            </div>
        </div>
    </main>


    <script>
    /*Uses the custom built API to render all the book data in a faster way*/   
    async function loadBooks() {
        /*Gets the current value search input and sort select to send to the API later*/
        const search = encodeURIComponent(document.getElementById('searchInput').value || '');
        const sort = encodeURIComponent(document.getElementById('sortSelect').value || 'default');
        /*Gets the data from the API then assigning q = search and sort = sort, it also limit the amount of bboks data to get to 100*/
        const url = `books_api.php?q=${search}&sort=${sort}&limit=100`;

        /*To define the container that holds all the books in hame page*/
        const container = document.getElementById('booksContainer');

        /*The code will try to run this code and see if theres any error, if so it will send it to the server and wont show to users*/
        try {
            /*fetches the url data for sort and search but hold the loadBook() function untill all the data is retrived from Data Base*/
            const res = await fetch(url);
            /*Translate the server side data format to JSON readable format and hold the loadBook() function untill all data is translated*/
            const data = await res.json();

            /*If the echo json_encode in db_connect.php shows False then  throws the error and stops the try code section*/
            if (!data.success) throw new Error(data.error || 'API error');

            /*If entered book on search bar is invalid then shows an error message and also stops the loop here*/
            if (data.books.length === 0) {
                container.innerHTML = '<p class="no-results-msg">No books found.</p>';
                return;
            }


            /*--------------------------------------------------------------------------------------------------------------------------------------------*/
            /*Defines the grid for the table that uses JS to update automatically*/
            const grid = document.createElement('div');
            grid.className = 'BookGrid';


            /*Loops through every book in Data Base apply this className to it so we can style it later*/
            data.books.forEach(book => {

                /*Setting up Individual Box for each Books*/
                const card = document.createElement('div');
                card.className = 'BookCard';

                /*Setting up Individual Button inside the Box for each Books*/
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'BookLinkBtn';
                
                btn.style.width = '100%';
                btn.style.fontFamily = 'inherit';
                btn.style.cursor = 'pointer';
                btn.style.outline = 'none';

                /*Setting up Individual Book Name*/
                const title = document.createElement('span');
                title.className = 'BookTitle';
                title.textContent = book.book_name;
                btn.appendChild(title);
                

                /*Add a event listener that triggers this code function if user perform a click action */
                btn.addEventListener('click', function(e) {

                    /*Tells the browser to not refresh the page or redirect to an empty link*/
                    e.preventDefault();

                    /*Creates a form wit POST method and an action value*/
                    const dynamicForm = document.createElement('form');
                    dynamicForm.method = 'POST';
                    dynamicForm.action = 'IndividualBookPreviewPage.php';
                    
                    /*Creates a input that is hidden for users, it have an name and value data*/
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = parseInt(book.book_id);

                    /*Puts the hidden input value and puts it inside of the form before submitting*/
                    dynamicForm.appendChild(input);
                    /*Temporarily attach the form submit to the web live browser DOM structure inside of <body> section due to web browser refusing to accept any form submission that has not ran through its DOM structure*/
                    document.body.appendChild(dynamicForm);
                    
                    /*To minimize JavaScript error while submitting form the code will use the default form submitting way*/
                    HTMLFormElement.prototype.submit.call(dynamicForm);
                });
                

                /*Defines the <span></span> where the Book price shows up at*/
                const price = document.createElement('span');

                /*Defines the className so it can  be styled later*/
                price.className = 'BookPrice';
                /*The content of the span section*/
                price.textContent = 'Price: RM ' + parseFloat(book.book_price).toFixed(2);

                /*Will have a text for book price right under the Book Name card*/
                card.appendChild(btn);
                /*Will show the actual price of the book*/
                card.appendChild(price);
                /*Will show the card that each book is surrounded with*/
                grid.appendChild(card);
            });

            /*Wipes out the old HTML element items in the container*/
            container.innerHTML = '';
            
            /*The main grid that hold all of the book cards*/
            container.appendChild(grid);
        
        /*If the error accured then this code will show user an error code and stops this try section from running any longer*/    
        } catch (err) {
            container.innerHTML = '<p class="NoResult">Failed to load books.</p>';
            console.error(err);
        }
    }


    /*When the user search in the search bar the event listener will look for an input*/
    document.getElementById('searchInput').addEventListener('input', function(){
        /*Stops any search that is currently going on*/
        clearTimeout(window._searchTimeout);
        /*If user stop typing for 0.3 second then this loadBooks function will look for the book from Data base based on what the user typed*/
        window._searchTimeout = setTimeout(loadBooks, 300);
    });

   /*Auto updates the form when selecting sort type*/
    document.getElementById('sortSelect').addEventListener('change', function() {
        document.getElementById('searchHidden').value = document.getElementById('searchInput').value;
        document.getElementById('sortInput').value = this.value;
        document.getElementById('sortForm').submit();
    });

    /*First load*/
    loadBooks();
    </script>

</body>
</html>
