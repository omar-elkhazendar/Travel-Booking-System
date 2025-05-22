<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar">
    <div class="navbar-content">
        <div class="logo">✈️ Egypto Airlines</div>
        <div class="nav-center">
            <a href="index.php" class="nav-link">Home</a>
            <a href="about.html" class="nav-link">About</a>
            <a href="contact.html" class="nav-link">Contact</a>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="comments.php" class="nav-link">Comments</a>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <?php
            if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
                echo '<span class="user-welcome">Welcome, ' . htmlspecialchars($_SESSION["username"]) . '</span>';
                echo '<a href="logout.php" class="nav-link login-btn">Logout</a>';
            } else {
                echo '<a href="login.php" class="nav-link login-btn">Login</a>';
            }
            ?>
            <div class="mode-switch">
                <input type="checkbox" id="mode-toggle" />
                <label for="mode-toggle" class="toggle-label">
                    <span class="sun">☀️</span>
                    <span class="moon">🌙</span>
                </label>
            </div>
        </div>
    </div>
</nav> 