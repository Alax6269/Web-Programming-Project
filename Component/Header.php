<?php
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$path_prefix = ($current_dir === 'Administrator') ? '../' : '';

$header_raw_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$header_role = strtolower(trim($header_raw_role));
?>
<header class="hdr-global-navbar">
    <div class="hdr-nav-left-zone">
        <a href="<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>LogOutPage.php" class="hdr-nav-action-btn hdr-logout-spec">LOG-OUT</a>
    </div>

    <div class="hdr-nav-right-zone">
        <?php if ($header_role === 'administrator' || $header_role === 'admin'): ?>
            <a href="<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>Administrator/EditDataBasePage.php" class="hdr-nav-action-btn hdr-active-db">Edit Data Base</a>
        <?php endif; ?>
        
        <a href="<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>Homepage.php" class="hdr-nav-action-btn">HOME</a>
        <a href="<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>CartPage.php" class="hdr-nav-action-btn">CART</a>
        <a href="<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>UserDashboardPage.php" class="hdr-nav-action-btn">COLLECTIONS</a>
    </div>
</header>

<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    });

   
    const timeoutDuration = 30000; 

    /*Function to redirect*/
    function forceRedirect() {
        /*Using replace() prevents the user from using the back button to return here*/
        window.location.replace("<?php echo htmlspecialchars($path_prefix, ENT_QUOTES, 'UTF-8'); ?>ErrorPage.php?msg=timeout");
    }

    /*Set the timer*/
    let idleTimer = setTimeout(forceRedirect, timeoutDuration);

   
    /*This Function resets the timeout timer to 30s if user interacted with the page*/
    function resetTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(forceRedirect, timeoutDuration);
    }

    window.onload = resetTimer;
    window.onmousemove = resetTimer;
    window.onmousedown = resetTimer; 
    window.ontouchstart = resetTimer;
    window.onclick = resetTimer;
    window.onkeydown = resetTimer;
</script>
