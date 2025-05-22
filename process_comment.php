<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment']) && isset($_POST['page_id'])) {
    $comment = trim($_POST['comment']);
    $page_id = trim($_POST['page_id']);
    $user_id = $_SESSION['id'];
    
    if (!empty($comment)) {
        $sql = "INSERT INTO comments (user_id, page_id, comment) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $page_id, $comment);
            if (mysqli_stmt_execute($stmt)) {
                header("location: profile.php?success=1");
                exit;
            } else {
                header("location: profile.php?error=1");
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// If we get here, something went wrong
header("location: profile.php?error=1");
exit;
?> 