<?php
require_once 'config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Travel Booking</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1>About Us</h1>
        <div class="about-content">
            <p>Welcome to our travel booking platform! We are dedicated to providing the best travel experiences for our customers.</p>
            <!-- Add your about page content here -->
        </div>

        <?php include 'components/comments.php'; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 