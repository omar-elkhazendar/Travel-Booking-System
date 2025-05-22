<?php
require_once '../config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 1) {
    header("location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Travel Booking</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        .admin-card h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .admin-btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .admin-btn:hover {
            background: #2563eb;
        }

        .admin-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>
        </div>

        <div class="admin-grid">
            <div class="admin-card">
                <div class="admin-icon">👥</div>
                <h3>Manage Users</h3>
                <a href="manage_users.php" class="admin-btn">View Users</a>
            </div>

            <div class="admin-card">
                <div class="admin-icon">✈️</div>
                <h3>Travel Offers</h3>
                <a href="manage_offers.php" class="admin-btn">Manage Offers</a>
            </div>

            <div class="admin-card">
                <div class="admin-icon">📅</div>
                <h3>Bookings</h3>
                <a href="view_bookings.php" class="admin-btn">View Bookings</a>
            </div>

            <div class="admin-card">
                <div class="admin-icon">📧</div>
                <h3>Messages</h3>
                <a href="view_messages.php" class="admin-btn">View Messages</a>
            </div>

            <div class="admin-card">
                <div class="admin-icon">💬</div>
                <h3>Comments</h3>
                <a href="view_comments.php" class="admin-btn">View Comments</a>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html> 