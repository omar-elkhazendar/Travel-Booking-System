<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["id"];

// Fetch user info
$user_sql = "SELECT username, email, created_at FROM users WHERE id = ?";
$user = null;
if ($stmt = mysqli_prepare($conn, $user_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Fetch booking history
$bookings = [];
$sql = "SELECT b.*, t.destination, t.departure_date, t.return_date, t.price FROM bookings b JOIN travel_offers t ON b.travel_offer_id = t.id WHERE b.user_id = ? ORDER BY b.booking_date DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get user's comments
$sql = "SELECT c.*, u.username, c.page_id 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC";
$comments = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Egypto Airlines</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e3eafc 100%);
            color: #222;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }
        /* --- NAVBAR STYLES --- */
        .navbar {
            width: 100vw;
            background: #23294a;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
            padding: 0.7rem 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .nav-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem;
        }
        .logo {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: 1px;
        }
        .logo span {
            font-size: 2.1rem;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 3rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-links li {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .nav-links a, .user-welcome {
            color: #fff;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: #4dabf7;
        }
        .user-welcome {
            font-weight: 700;
        }
        .mode-switch {
            display: flex;
            align-items: center;
            margin-left: 2rem;
        }
        .toggle-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }
        .toggle-label .sun {
            color: #f7c873;
        }
        .toggle-label .moon {
            color: #ffe066;
        }
        #mode-toggle {
            display: none;
        }
        /* Adjust main container for fixed navbar */
        .profile-container {
            margin-top: 5.5rem !important;
        }
        /* --- END NAVBAR STYLES --- */
        .profile-container {
            max-width: 700px;
            margin: 7rem auto 2rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.10);
            padding: 2.5rem 2rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .profile-header h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .profile-info {
            margin-bottom: 2.5rem;
            text-align: center;
        }
        .profile-info p {
            font-size: 1.1rem;
            color: #444;
            margin: 0.3rem 0;
        }
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .bookings-table th, .bookings-table td {
            padding: 0.9rem 0.7rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .bookings-table th {
            background: #f8fafc;
            color: #2c3e50;
        }
        .bookings-table tr:last-child td {
            border-bottom: none;
        }
        .no-bookings {
            text-align: center;
            color: #888;
            margin-top: 2rem;
        }
        @media (max-width: 800px) {
            .profile-container { padding: 1rem; }
            .bookings-table th, .bookings-table td { padding: 0.5rem 0.2rem; }
        }
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        body.dark-mode .navbar {
            background: #181c2f;
            box-shadow: 0 2px 8px rgba(0,0,0,0.18);
        }
        body.dark-mode .logo, body.dark-mode .nav-links a, body.dark-mode .user-welcome {
            color: #fff;
        }
        body.dark-mode .nav-links a:hover {
            color: #4dabf7;
        }
        body.dark-mode .toggle-label .sun {
            color: #f7c873;
        }
        body.dark-mode .toggle-label .moon {
            color: #ffe066;
        }
        body.dark-mode .profile-container {
            background: #23242a;
            color: #e4e6eb;
        }
        body.dark-mode .profile-header h2 {
            color: #e4e6eb;
        }
        body.dark-mode .profile-info p {
            color: #e4e6eb;
        }
        body.dark-mode .bookings-table th {
            background: #23272f;
            color: #e4e6eb;
        }
        body.dark-mode .bookings-table td {
            color: #e4e6eb;
        }
        body.dark-mode .no-bookings {
            color: #adb5bd;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #2980b9 0%, #217dbb 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(41,128,185,0.25) !important;
        }
        
        body.dark-mode .btn-primary {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%) !important;
            box-shadow: 0 2px 8px rgba(77,171,247,0.15) !important;
        }
        
        body.dark-mode .btn-primary:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%) !important;
            box-shadow: 0 4px 12px rgba(77,171,247,0.25) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-flex">
            <div class="logo">✈️ Egypto Airlines</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span></li>
                <li><a href="logout.php" class="login-btn">Logout</a></li>
            </ul>
            <div class="mode-switch">
                <input type="checkbox" id="mode-toggle" />
                <label for="mode-toggle" class="toggle-label">
                    <span class="sun">☀️</span>
                    <span class="moon">🌙</span>
                </label>
            </div>
        </div>
    </nav>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Profile</h2>
            <a href="my_bookings.php" class="btn btn-primary" style="display: inline-block; margin-top: 1rem; padding: 0.8rem 1.5rem; background: linear-gradient(90deg, #3498db 0%, #2980b9 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(41,128,185,0.15); border: none; cursor: pointer; font-size: 1.05rem;">View My Bookings</a>
        </div>
        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
        </div>
        <h3 style="margin-bottom:1rem;">Your Booking History</h3>
        <?php if (count($bookings) > 0): ?>
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Destination</th>
                    <th>Departure</th>
                    <th>Return</th>
                    <th>Price</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['destination']); ?></td>
                    <td><?php echo htmlspecialchars($b['departure_date']); ?></td>
                    <td><?php echo htmlspecialchars($b['return_date']); ?></td>
                    <td>$<?php echo number_format($b['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                    <td><?php echo htmlspecialchars($b['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-bookings">You have no bookings yet.</div>
        <?php endif; ?>
        <h3 style="margin-top:2rem; margin-bottom:1rem;">Your Comments</h3>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                Your comment has been posted successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                There was an error posting your comment. Please try again.
            </div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <div class="no-bookings">You haven't made any comments yet.</div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span>Posted on: <?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></span>
                        <span>Page: <a href="<?php echo htmlspecialchars($comment['page_id']); ?>.php" class="page-link"><?php echo htmlspecialchars($comment['page_id']); ?></a></span>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add Comment Form -->
        <div class="add-comment-section" style="margin-top: 2rem;">
            <h3>Add a Comment</h3>
            <form action="process_comment.php" method="POST" class="comment-form">
                <input type="hidden" name="page_id" value="profile">
                <textarea name="comment" class="comment-input" placeholder="Write your comment here..." required style="width: 100%; min-height: 100px; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                <button type="submit" class="submit-btn" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Post Comment</button>
            </form>
        </div>
    </div>
    <script>
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
        window.addEventListener('DOMContentLoaded', function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                modeToggle.checked = true;
                setMode(true);
            }
        });
    </script>
</body>
</html> 