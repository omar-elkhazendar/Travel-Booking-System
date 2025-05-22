<?php
require_once 'config/database.php';

// Admin credentials
$admin_email = 'admin@gmail.com';
$admin_username = 'admin';
$admin_password = 'admin123';
$is_admin = 1;

// Ensure database connection
$conn = ensureConnection();

// Check if admin user already exists
$sql = "SELECT id FROM users WHERE email = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $admin_email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        // Admin user doesn't exist, create it
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $admin_username, $admin_email, $hashed_password, $is_admin);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Admin user created successfully!<br>";
                echo "Email: " . $admin_email . "<br>";
                echo "Password: " . $admin_password . "<br>";
            } else {
                echo "Error creating admin user: " . mysqli_error($conn);
            }
        }
    } else {
        // Admin user exists, update password and admin status
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ?, is_admin = ? WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sis", $hashed_password, $is_admin, $admin_email);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Admin user updated successfully!<br>";
                echo "Email: " . $admin_email . "<br>";
                echo "Password: " . $admin_password . "<br>";
            } else {
                echo "Error updating admin user: " . mysqli_error($conn);
            }
        }
    }
}

mysqli_close($conn);
?> 