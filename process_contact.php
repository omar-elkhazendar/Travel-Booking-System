<?php
require_once 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    
    // Validate input
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("location: contact.html?error=empty_fields");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("location: contact.html?error=invalid_email");
        exit();
    }
    
    // Store message in database
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            header("location: contact.html?success=1");
        } else {
            header("location: contact.html?error=db_error");
        }
        
        mysqli_stmt_close($stmt);
    } else {
        header("location: contact.html?error=db_error");
    }
    
    mysqli_close($conn);
} else {
    header("location: contact.html");
    exit();
}
?> 