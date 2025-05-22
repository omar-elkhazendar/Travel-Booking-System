<?php
require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session data
error_log("Admin.php - Session data:");
error_log("loggedin: " . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "not set"));
error_log("is_admin: " . (isset($_SESSION["is_admin"]) ? $_SESSION["is_admin"] : "not set"));

// Check admin access
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
    error_log("Admin access denied - Session check failed");
    error_log("loggedin check: " . (!isset($_SESSION["loggedin"]) ? "not set" : ($_SESSION["loggedin"] !== true ? "false" : "true")));
    error_log("is_admin check: " . (!isset($_SESSION["is_admin"]) ? "not set" : ($_SESSION["is_admin"] != 1 ? "not 1" : "1")));
    header("location: login.php");
    exit;
}

error_log("Admin access granted");

// Handle travel offer deletion
if (isset($_POST['delete_offer'])) {
    $offer_id = $_POST['offer_id'];
    
    // First, get the image path to delete the file if it exists
    $sql = "SELECT image_path FROM travel_offers WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $offer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            // Delete the image file if it exists
            if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First delete all associated bookings
        $sql = "DELETE FROM bookings WHERE travel_offer_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $offer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Now delete the travel offer
        $sql = "DELETE FROM travel_offers WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $offer_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Travel offer and associated bookings successfully deleted.";
            } else {
                throw new Exception("Error deleting travel offer: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error preparing delete statement: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Redirect back to admin page
    header("Location: admin.php");
    exit;
}

// Handle new travel offer addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_offer'])) {
    $destination = trim($_POST['destination']);
    $departure_date = trim($_POST['departure_date']);
    $return_date = trim($_POST['return_date']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $max_passengers = isset($_POST['max_passengers']) ? intval($_POST['max_passengers']) : 100;
    $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'Round-trip';
    $outbound_time = isset($_POST['outbound_time']) ? $_POST['outbound_time'] : null;
    $return_time = isset($_POST['return_time']) ? $_POST['return_time'] : null;
    $image_path = NULL;
    $available = 1;

    // For one-way trips, set return date to departure date
    if ($trip_type === 'One-way') {
        $return_date = $departure_date;
        $return_time = null;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploads_dir = __DIR__ . '/uploads';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        $tmp_name = $_FILES['image']['tmp_name'];
        $basename = basename($_FILES['image']['name']);
        $target_file = $uploads_dir . '/' . uniqid() . '_' . $basename;
        if (move_uploaded_file($tmp_name, $target_file)) {
            $image_path = 'uploads/' . basename($target_file);
        }
    }

    $sql = "INSERT INTO travel_offers (destination, departure_date, return_date, price, description, image_path, max_passengers, trip_type, outbound_time, return_time, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssssisssi", $destination, $departure_date, $return_date, $price, $description, $image_path, $max_passengers, $trip_type, $outbound_time, $return_time, $available);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Travel offer successfully added.";
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error_message'] = "Error adding travel offer: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . mysqli_error($conn);
    }
}

// Handle user management operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $hashed_password, $is_admin);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User successfully added.";
            } else {
                $_SESSION['error_message'] = "Error adding user: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Update user
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        $sql = "UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $username, $email, $is_admin, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User successfully updated.";
            } else {
                $_SESSION['error_message'] = "Error updating user: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        
        // If password is provided, update it
        if (!empty($_POST['password'])) {
            $password = trim($_POST['password']);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "User password successfully updated.";
                } else {
                    $_SESSION['error_message'] = "Error updating user password: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete user's bookings
            $sql = "DELETE FROM bookings WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Delete user's comments
            $sql = "DELETE FROM comments WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Delete the user
            $sql = "DELETE FROM users WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_commit($conn);
                    $_SESSION['success_message'] = "User and associated data successfully deleted.";
                } else {
                    throw new Exception("Error deleting user: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

// Fetch all travel offers
$offers = [];
$sql = "SELECT * FROM travel_offers ORDER BY departure_date";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $offers[] = $row;
    }
    mysqli_free_result($result);
}

// Fetch all bookings
$bookings = [];
$sql = "SELECT b.*, u.email, t.destination, t.price 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN travel_offers t ON b.travel_offer_id = t.id 
        ORDER BY b.booking_date DESC";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    mysqli_free_result($result);
}

// Fetch all comments with user information
$messages = [];
$sql = "SELECT c.*, u.username, u.email 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    mysqli_free_result($result);
}

// Fetch all users
$users = [];
$sql = "SELECT * FROM users ORDER BY username";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the booking
        $sql = "DELETE FROM bookings WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $booking_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Booking successfully deleted.";
            } else {
                throw new Exception("Error deleting booking: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error preparing delete statement: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: admin.php");
    exit;
}

// Handle booking deletion request approval
if (isset($_POST['approve_delete_request'])) {
    $booking_id = $_POST['booking_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the booking
        $sql = "DELETE FROM bookings WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $booking_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Booking deletion request approved and booking deleted.";
            } else {
                throw new Exception("Error deleting booking: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error preparing delete statement: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: admin.php");
    exit;
}

// Handle booking deletion request rejection
if (isset($_POST['reject_delete_request'])) {
    $booking_id = $_POST['booking_id'];
    
    $sql = "UPDATE bookings SET delete_request = FALSE, delete_request_date = NULL WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Booking deletion request rejected.";
        } else {
            $_SESSION['error_message'] = "Error rejecting deletion request: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: admin.php");
    exit;
}

// Handle comment deletion
if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    
    $sql = "DELETE FROM comments WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $message_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Comment successfully deleted.";
        } else {
            $_SESSION['error_message'] = "Error deleting comment: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: admin.php");
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Travel Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e3eafc 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background 0.3s ease;
        }
        .navbar {
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
            padding: 0.7rem 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2980b9;
            letter-spacing: 1px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .nav-link {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .nav-link:hover {
            color: #2980b9;
        }
        .logout-btn {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: linear-gradient(90deg, #c0392b 0%, #a93226 100%);
        }
        .container {
            max-width: 1200px;
            margin: 5.5rem auto 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        .admin-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .admin-tab {
            padding: 0.8rem 1.5rem;
            background: #f8fafc;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            color: #2c3e50;
            transition: background 0.2s, color 0.2s, border 0.2s;
            outline: none;
        }
        .admin-tab.active {
            background: #fff;
            color: #2980b9;
            border-bottom: 2.5px solid #2980b9;
            font-weight: 700;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .admin-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .admin-section h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, background 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2980b9;
            outline: none;
        }
        .btn {
            padding: 0.9rem 1.5rem;
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: linear-gradient(90deg, #217dbb 0%, #145374 100%);
        }
        .offers-list {
            margin-top: 2rem;
        }
        .offer-item {
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        .offer-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .offer-title {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .offer-price {
            color: #2980b9;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .offer-details {
            color: #64748b;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .offer-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        .btn-edit {
            background: linear-gradient(90deg, #2ecc71 0%, #27ae60 100%);
        }
        .btn-edit:hover {
            background: linear-gradient(90deg, #27ae60 0%, #219a52 100%);
        }
        .btn-delete {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
        }
        .btn-delete:hover {
            background: linear-gradient(90deg, #c0392b 0%, #a93226 100%);
        }
        .mode-switch {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            align-items: center;
            z-index: 2000;
        }
        .toggle-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.3rem;
            user-select: none;
        }
        #mode-toggle {
            display: none;
        }
        .sun, .moon {
            transition: opacity 0.2s;
        }
        body.dark-mode .sun {
            opacity: 0.3;
        }
        body:not(.dark-mode) .moon {
            opacity: 0.3;
        }
        /* Dark mode styles */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        .dark-mode .container {
            background: #2d2f34;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .admin-header h1 {
            color: #e4e6eb;
        }
        .dark-mode .admin-section {
            background: #1a1b1e;
        }
        .dark-mode .admin-section h2 {
            color: #e4e6eb;
        }
        .dark-mode .form-group label {
            color: #e4e6eb;
        }
        .dark-mode .form-group input,
        .dark-mode .form-group textarea {
            background: #1a1b1e;
            border-color: #3d3f44;
            color: #e4e6eb;
        }
        .dark-mode .form-group input:focus,
        .dark-mode .form-group textarea:focus {
            border-color: #4dabf7;
        }
        .dark-mode .offer-item {
            background: #2d2f34;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .offer-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .dark-mode .offer-title {
            color: #e4e6eb;
        }
        .dark-mode .offer-price {
            color: #4dabf7;
        }
        .dark-mode .offer-details {
            color: #adb5bd;
        }
        .dark-mode .btn {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%);
        }
        .dark-mode .btn:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%);
        }
        .dark-mode .btn-edit {
            background: linear-gradient(90deg, #40c057 0%, #37b24d 100%);
        }
        .dark-mode .btn-edit:hover {
            background: linear-gradient(90deg, #37b24d 0%, #2f9e44 100%);
        }
        .dark-mode .btn-delete {
            background: linear-gradient(90deg, #fa5252 0%, #e03131 100%);
        }
        .dark-mode .btn-delete:hover {
            background: linear-gradient(90deg, #e03131 0%, #c92a2a 100%);
        }
        body.dark-mode .navbar {
            background: #23272f;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.15);
        }
        body.dark-mode .logo {
            color: #4dabf7;
        }
        body.dark-mode .nav-link {
            color: #e4e6eb;
        }
        body.dark-mode .nav-link:hover {
            color: #4dabf7;
        }
        body.dark-mode .logout-btn {
            background: linear-gradient(90deg, #fa5252 0%, #e03131 100%);
        }
        body.dark-mode .logout-btn:hover {
            background: linear-gradient(90deg, #e03131 0%, #c92a2a 100%);
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.07);
            overflow-x: auto;
            margin-top: 2rem;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 1rem;
            background: transparent;
        }
        th, td {
            padding: 1rem 0.7rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        th {
            background: #f8fafc;
            font-weight: 700;
            color: #2c3e50;
        }
        tr:nth-child(even) {
            background: #f4f7fb;
        }
        tr:last-child td {
            border-bottom: none;
        }
        td img {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            max-width: 90px;
            max-height: 60px;
            object-fit: cover;
        }
        .offer-actions {
            display: flex;
            gap: 0.5rem;
        }
        .delete-btn {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1.1rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(231,76,60,0.08);
        }
        .delete-btn:hover {
            background: linear-gradient(90deg, #c0392b 0%, #a93226 100%);
        }
        .edit-btn {
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1.1rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(241,196,15,0.08);
            text-decoration: none;
            display: inline-block;
        }
        .edit-btn:hover {
            background: linear-gradient(90deg, #f39c12 0%, #e1b000 100%);
        }
        @media (max-width: 800px) {
            .container, .navbar-content {
                padding: 1rem;
            }
            th, td {
                padding: 0.7rem 0.3rem;
                font-size: 0.95rem;
            }
            .edit-btn, .delete-btn {
                padding: 0.4rem 0.7rem;
                font-size: 0.95rem;
            }
        }
        /* Dark mode for table */
        body.dark-mode .table-container {
            background: #23272f;
            box-shadow: 0 0 20px rgba(44,62,80,0.18);
        }
        body.dark-mode table {
            background: transparent;
        }
        body.dark-mode th {
            background: #23272f;
            color: #e4e6eb;
        }
        body.dark-mode tr:nth-child(even) {
            background: #23272f;
        }
        body.dark-mode tr:nth-child(odd) {
            background: #2d2f34;
        }
        body.dark-mode td {
            border-bottom: 1px solid #3d3f44;
        }
        body.dark-mode .edit-btn {
            background: linear-gradient(90deg, #ffe066 0%, #fab005 100%);
            color: #23272f;
        }
        body.dark-mode .edit-btn:hover {
            background: linear-gradient(90deg, #fab005 0%, #ffe066 100%);
        }
        body.dark-mode .delete-btn {
            background: linear-gradient(90deg, #fa5252 0%, #e03131 100%);
        }
        body.dark-mode .delete-btn:hover {
            background: linear-gradient(90deg, #e03131 0%, #c92a2a 100%);
        }
        .btn-approve {
            background: linear-gradient(90deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 0.5rem;
        }
        .btn-approve:hover {
            background: linear-gradient(90deg, #27ae60 0%, #219a52 100%);
            transform: translateY(-1px);
        }
        .btn-reject {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-reject:hover {
            background: linear-gradient(90deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-1px);
        }
        .message-preview {
            cursor: pointer;
            color: #3b82f6;
        }
        
        .message-full {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f3f4f6;
            border-radius: 4px;
            white-space: pre-wrap;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 0.5rem;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-view:hover {
            background: #2563eb;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-size: 0.85em;
        }
        .btn-group {
            gap: 0.5rem;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #007bff;
            border-color: #007bff;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .table td {
            vertical-align: middle;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-primary {
            background-color: #007bff;
        }
        .badge-secondary {
            background-color: #6c757d;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        .btn-outline-primary {
            color: #007bff;
            border-color: #007bff;
        }
        .btn-outline-primary:hover {
            color: #fff;
            background-color: #007bff;
        }
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-outline-danger:hover {
            color: #fff;
            background-color: #dc3545;
        }
        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        .form-control {
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
            padding: 0.5rem 0.75rem;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .custom-switch {
            padding-left: 2.5rem;
        }
        .custom-control-label {
            padding-top: 0.25rem;
        }
        .container-fluid {
            max-width: 1400px;
        }
        .card {
            border: none;
            border-radius: 0.5rem;
        }
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.05);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-primary {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .badge-secondary {
            background-color: #f5f5f5;
            color: #616161;
        }
        .btn-light {
            background-color: #f8f9fa;
            border-color: #f8f9fa;
        }
        .btn-light:hover {
            background-color: #e9ecef;
            border-color: #e9ecef;
        }
        .gap-2 {
            gap: 0.5rem;
        }
        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }
        .modal-header {
            padding: 1.5rem 1.5rem 0;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            padding: 0 1.5rem 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15);
        }
        .custom-switch {
            padding-left: 2.5rem;
        }
        .custom-control-label {
            padding-top: 0.25rem;
            font-size: 0.875rem;
        }
        .text-primary {
            color: #1976d2 !important;
        }
        .btn-primary {
            background-color: #1976d2;
            border-color: #1976d2;
        }
        .btn-primary:hover {
            background-color: #1565c0;
            border-color: #1565c0;
        }
        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        }
        .admin-toggle {
            position: relative;
            display: inline-block;
            margin: 10px 0;
        }

        .admin-toggle-input {
            display: none;
        }

        .admin-toggle-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
        }

        .admin-toggle-label:hover {
            background: #e9ecef;
        }

        .admin-toggle-text {
            font-weight: 500;
            color: #495057;
            margin-right: 15px;
        }

        .admin-toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            background: #ced4da;
            border-radius: 13px;
            transition: all 0.3s ease;
        }

        .admin-toggle-switch:before {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            background: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-toggle-input:checked + .admin-toggle-label {
            background: #e3f2fd;
            border-color: #90caf9;
        }

        .admin-toggle-input:checked + .admin-toggle-label .admin-toggle-text {
            color: #1976d2;
        }

        .admin-toggle-input:checked + .admin-toggle-label .admin-toggle-switch {
            background: #1976d2;
        }

        .admin-toggle-input:checked + .admin-toggle-label .admin-toggle-switch:before {
            transform: translateX(24px);
        }

        /* Dark mode support */
        body.dark-mode .admin-toggle-label {
            background: #2d2f34;
            border-color: #3d3f44;
        }

        body.dark-mode .admin-toggle-label:hover {
            background: #3d3f44;
        }

        body.dark-mode .admin-toggle-text {
            color: #e4e6eb;
        }

        body.dark-mode .admin-toggle-switch {
            background: #4d4f55;
        }

        body.dark-mode .admin-toggle-input:checked + .admin-toggle-label {
            background: #1a1b1e;
            border-color: #4dabf7;
        }

        body.dark-mode .admin-toggle-input:checked + .admin-toggle-label .admin-toggle-text {
            color: #4dabf7;
        }

        body.dark-mode .admin-toggle-input:checked + .admin-toggle-label .admin-toggle-switch {
            background: #4dabf7;
        }
    </style>
</head>
<body>
    <div class="mode-switch">
        <input type="checkbox" id="mode-toggle" />
        <label for="mode-toggle" class="toggle-label">
            <span class="sun">☀️</span>
            <span class="moon">🌙</span>
        </label>
    </div>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">✈️ Egypto Airlines Admin</div>
            <div class="nav-links">
                <a href="admin.php" class="nav-link">Dashboard</a>
                <form action="admin_logout.php" method="post" style="display:inline; margin:0;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #c3e6cb;">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #f5c6cb;">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="showTab('offers')">Travel Offers</button>
            <button class="admin-tab" onclick="showTab('bookings')">Bookings</button>
        </div>
        
        <div id="offers" class="tab-content active">
            <div class="add-offer-form">
                <h2>Add New Travel Offer</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <input type="text" name="destination" id="destination" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="departure_date">Departure Date</label>
                        <input type="date" name="departure_date" id="departure_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="trip_type">Trip Type</label>
                        <select name="trip_type" id="trip_type" required onchange="toggleReturnFields()">
                            <option value="Round-trip" <?php if(isset($_POST['trip_type']) && $_POST['trip_type']==='Round-trip') echo 'selected'; ?>>Round-trip</option>
                            <option value="One-way" <?php if(isset($_POST['trip_type']) && $_POST['trip_type']==='One-way') echo 'selected'; ?>>One-way</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="return_date_group" style="display: block;">
                        <label for="return_date">Return Date</label>
                        <input type="date" name="return_date" id="return_date" value="<?php echo isset($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group" id="return_time_group" style="display: block;">
                        <label for="return_time">Return Time</label>
                        <input type="time" name="return_time" id="return_time" value="<?php echo isset($_POST['return_time']) ? htmlspecialchars($_POST['return_time']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" name="price" id="price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_passengers">Max Passengers</label>
                        <input type="number" name="max_passengers" id="max_passengers" min="1" value="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="outbound_time">Outbound Time</label>
                        <input type="time" name="outbound_time" id="outbound_time">
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" required></textarea>
                    </div>
                    
                    <button type="submit" name="add_offer" class="btn btn-primary" onclick="return submitOffer();">Add Offer</button>
                </form>
            </div>
            
            <div class="table-container">
                <h2>Current Travel Offers</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Destination</th>
                            <th>Departure Date</th>
                            <th>Return Date</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Trip Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td>
                                <?php if (!empty($offer['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($offer['image_path']); ?>" alt="Travel Image" style="max-width:100px;max-height:70px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($offer['destination']); ?></td>
                            <td><?php echo htmlspecialchars($offer['departure_date']); ?></td>
                            <td><?php echo htmlspecialchars($offer['return_date']); ?></td>
                            <td>$<?php echo number_format($offer['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($offer['description']); ?></td>
                            <td><?php echo htmlspecialchars($offer['trip_type'] ?? ''); ?></td>
                            <td>
                                <form action="admin.php" method="post" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                    <button type="submit" name="delete_offer" class="delete-btn">Delete</button>
                                </form>
                                <a href="edit_offer.php?id=<?php echo $offer['id']; ?>" class="edit-btn" style="background:#f1c40f;color:#fff;padding:0.5rem 1rem;border-radius:5px;text-decoration:none;margin-left:0.5rem;">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="bookings" class="tab-content">
            <div class="table-container">
                <h2>All Bookings</h2>
                <table>
                    <thead>
                        <tr>
                            <th>User Email</th>
                            <th>Destination</th>
                            <th>Price</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['email']); ?></td>
                            <td><?php echo htmlspecialchars($booking['destination']); ?></td>
                            <td>$<?php echo number_format($booking['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                            <td>
                                <?php if ($booking['delete_request']): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">Deletion Requested</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($booking['delete_request']): ?>
                                    <form action="admin.php" method="post" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="approve_delete_request" class="btn-approve">Approve Delete</button>
                                        <button type="submit" name="reject_delete_request" class="btn-reject">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <form action="admin.php" method="post" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="delete_booking" class="delete-btn" onclick="return confirm('Are you sure you want to delete this booking?')">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>Contact Messages</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($message['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($message['username']); ?></td>
                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                            <td><?php echo htmlspecialchars($message['page_id']); ?></td>
                            <td>
                                <div class="message-preview">
                                    <?php 
                                    $commentText = $message['comment'];
                                    echo htmlspecialchars(substr($commentText, 0, 100)) . (strlen($commentText) > 100 ? '...' : ''); 
                                    ?>
                                </div>
                                <div class="message-full" style="display: none;">
                                    <?php echo nl2br(htmlspecialchars($commentText)); ?>
                                </div>
                            </td>
                            <td>
                                <button class="btn-view" onclick="toggleMessage(this)">View</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" name="delete_message" class="btn-delete" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="container-fluid px-4 py-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">User Management</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Username</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0">Role</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['is_admin'] ? 'badge-primary' : 'badge-secondary'; ?>">
                                            <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Create New User Form -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Create New User</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <div class="admin-toggle">
                                <input type="checkbox" class="admin-toggle-input" id="is_admin" name="is_admin">
                                <label class="admin-toggle-label" for="is_admin">
                                    <span class="admin-toggle-text">Admin User</span>
                                    <span class="admin-toggle-switch"></span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Update tab buttons
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Dark mode toggle logic
        const modeToggle = document.getElementById('mode-toggle');
        const body = document.body;
        
        function setMode(dark) {
            if (dark) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        }
        
        modeToggle.addEventListener('change', function() {
            setMode(this.checked);
        });
        
        // On load, set mode from localStorage
        window.addEventListener('DOMContentLoaded', function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                modeToggle.checked = true;
                setMode(true);
            }
        });

        function toggleMessage(button) {
            const row = button.closest('tr');
            const preview = row.querySelector('.message-preview');
            const full = row.querySelector('.message-full');
            
            if (preview.style.display !== 'none') {
                preview.style.display = 'none';
                full.style.display = 'block';
                button.textContent = 'Hide';
            } else {
                preview.style.display = 'block';
                full.style.display = 'none';
                button.textContent = 'View';
            }
        }

        function toggleReturnFields() {
            var tripType = document.getElementById('trip_type').value;
            var returnDateGroup = document.getElementById('return_date_group');
            var returnTimeGroup = document.getElementById('return_time_group');
            var returnDateInput = document.getElementById('return_date');
            var returnTimeInput = document.getElementById('return_time');
            var departureDateInput = document.getElementById('departure_date');
            if (tripType === 'One-way') {
                returnDateGroup.style.display = 'none';
                returnTimeGroup.style.display = 'none';
                // Clear return date and time (so that the SQL insert does not fail) and set return date to departure date
                if (returnDateInput && departureDateInput) {
                    returnDateInput.value = departureDateInput.value;
                }
                if (returnTimeInput) {
                    returnTimeInput.value = '';
                }
            } else {
                returnDateGroup.style.display = 'block';
                 returnTimeGroup.style.display = 'block';
            }
        }

        function submitOffer() {
            var tripType = document.getElementById('trip_type').value;
            var returnDateInput = document.getElementById('return_date');
            var departureDateInput = document.getElementById('departure_date');
            if (tripType === 'One-way' && returnDateInput && departureDateInput) {
                // (If "One-way" is selected, set the return date (and clear return time) so that the SQL insert does not fail.)
                returnDateInput.value = departureDateInput.value;
                var returnTimeInput = document.getElementById('return_time');
                if (returnTimeInput) { returnTimeInput.value = ''; }
            }
            return true; // (allow the form to submit)
        }

        window.addEventListener('DOMContentLoaded', function() {
            toggleReturnFields();
        });
    </script>
</body>
</html> 