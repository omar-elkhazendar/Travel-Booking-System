<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["id"];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$provider = isset($_GET['provider']) ? $_GET['provider'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" || $action == 'disconnect') {
    if ($action == 'disconnect' && !empty($provider)) {
        // Disconnect OAuth provider
        $sql = "UPDATE users SET {$provider}_id = NULL WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Log the activity
                $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_date) VALUES (?, ?, NOW())";
                if ($activity_stmt = mysqli_prepare($conn, $activity_sql)) {
                    $activity_type = ucfirst($provider) . " Disconnected";
                    mysqli_stmt_bind_param($activity_stmt, "is", $user_id, $activity_type);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }
                
                header("location: profile.php?success=3");
            } else {
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

// Function to check if user has OAuth connected
function hasOAuthConnected($conn, $user_id, $provider) {
    $sql = "SELECT {$provider}_id FROM users WHERE id = ? AND {$provider}_id IS NOT NULL";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            $has_connected = mysqli_stmt_num_rows($stmt) == 1;
            mysqli_stmt_close($stmt);
            return $has_connected;
        }
    }
    
    return false;
}

// Get OAuth connection status
$github_connected = hasOAuthConnected($conn, $user_id, 'github');
$google_connected = hasOAuthConnected($conn, $user_id, 'google');

mysqli_close($conn);
?> 