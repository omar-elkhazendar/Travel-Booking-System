<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['offer_id'])) {
    echo "No offer selected.";
    exit();
}

$offer_id = intval($_GET['offer_id']);
$user_id = $_SESSION['id'];
$booking_success = false;
$offer = null;
$visa_step = false;
$visa_info = ["number" => "", "expiry" => "", "name" => ""];
$visa_error = "";

// Fetch offer details
$offer_sql = "SELECT * FROM travel_offers WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $offer_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $offer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $offer = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Fetch user details
$user_sql = "SELECT username, email FROM users WHERE id = ?";
$user = null;
if ($stmt = mysqli_prepare($conn, $user_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Step 1: User clicks Confirm Booking, show visa form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $visa_step = true;
}
// Step 2: User submits visa info, process booking
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_visa'])) {
    $visa_info['number'] = trim($_POST['visa_number'] ?? '');
    $visa_info['expiry'] = trim($_POST['visa_expiry'] ?? '');
    $visa_info['name'] = trim($_POST['visa_name'] ?? '');
    // Hash the visa number for security
    $hashed_visa_number = password_hash($visa_info['number'], PASSWORD_DEFAULT);
    $insert_sql = "INSERT INTO bookings (user_id, travel_offer_id, status, visa_number, visa_expiry, visa_name) VALUES (?, ?, 'confirmed', ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        mysqli_stmt_bind_param($stmt, "iisss", $user_id, $offer_id, $hashed_visa_number, $visa_info['expiry'], $visa_info['name']);
        if (mysqli_stmt_execute($stmt)) {
            $booking_success = true;
            $booking_id = mysqli_insert_id($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: #fff;
            color: #232946;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }
        @keyframes gradientBG {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }
        /* --- NAVBAR STYLES --- */
        .navbar {
            width: 100vw;
            background: #fff;
            box-shadow: 0 2px 16px rgba(44, 62, 80, 0.10);
            padding: 0.7rem 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1200;
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
            color: #232946;
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
            color: #232946 !important;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            background: #e3eafc;
            color: #232946 !important;
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
        .container {
            margin-top: 5.5rem !important;
            max-width: 100vw;
            background: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0;
        }
        /* Dedicated booking card styling */
        .booking-card {
            background: #f8fafc;
            border-radius: 28px;
            box-shadow: 0 8px 32px 0 rgba(44,62,80,0.13), 0 1.5px 8px rgba(41,128,185,0.08);
            border: 1.5px solid #e3eafc;
            padding: 2.7rem 2.2rem;
            max-width: 500px;
            margin: 8.5rem auto 2.5rem auto;
            color: #232946;
            position: relative;
            animation: fadeIn 0.7s cubic-bezier(.4,0,.2,1);
            box-sizing: border-box;
            transition: box-shadow 0.25s cubic-bezier(.4,0,.2,1), transform 0.18s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 8px 32px 0 rgba(44,62,80,0.13), 0 1.5px 8px rgba(41,128,185,0.08), 0 0 24px 0 rgba(137,207,240,0.03) inset;
        }
        .booking-card:hover {
            box-shadow: 0 16px 56px 0 rgba(44,62,80,0.28), 0 1.5px 8px rgba(41,128,185,0.13), 0 0 32px 0 rgba(137,207,240,0.13) inset;
            transform: translateY(-4px) scale(1.018);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .booking-card h1, .booking-card h2, .booking-card h3, .booking-card label, .booking-card strong, .booking-card .ticket-label, .booking-card .ticket-value, .booking-card, .booking-card * {
            color: #232946 !important;
            text-shadow: none !important;
            font-weight: 700 !important;
        }
        .booking-card p, .booking-card li, .booking-card input, .booking-card span, .booking-card ul, .booking-card form, .booking-card .form-group {
            color: #232946 !important;
            font-weight: 600 !important;
            opacity: 1 !important;
        }
        .booking-card h1, .container h1 {
            font-size: 2.3rem;
            font-weight: 900;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
            color: #fff;
            text-shadow: 0 2px 12px #1118;
        }
        .booking-card h2, .container h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: #4dabf7;
            letter-spacing: 0.5px;
        }
        .booking-card h3, .container h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.1rem;
            color: #fff;
        }
        .booking-card ul {
            margin-bottom: 2rem;
            padding-left: 1.2rem;
        }
        .booking-card li {
            font-size: 1.13rem;
            margin-bottom: 0.5rem;
            color: #e4e6eb;
            font-weight: 500;
        }
        .booking-card strong {
            color: #4dabf7;
            font-weight: 700;
        }
        /* Styling for form groups within the booking card */
        .booking-card .form-group {
            margin-bottom: 1.5rem; /* Space below form groups */
        }
        .booking-card .form-group label {
            display: block; /* Labels on their own line by default */
            margin-bottom: 0.5rem; /* Space below label */
            font-weight: 600;
        }
        .booking-card .form-group input:not([type="checkbox"]),
        .booking-card .form-group select,
        .booking-card .form-group textarea {
            width: 100%; /* Full width by default */
            padding: 0.8rem;
            border: 1.5px solid #b0b8c9; /* Default border color */
            border-radius: 8px;
            font-size: 1rem;
            background: #fff; /* Default background */
            color: #232946; /* Default text color */
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .booking-card .form-group input:focus,
        .booking-card .form-group select:focus,
        .booking-card .form-group textarea:focus {
            border-color: #4dabf7; /* Highlight on focus */
            outline: none;
        }
        /* Styling for the visa information form */
        .booking-card .visa-form .form-group {
            margin-bottom: 1.5rem; /* Maintain space between form groups */
        }
        .booking-card .visa-form .form-group label {
            /* Uses general .booking-card .form-group label styles (display: block, margin-bottom) */
        }
        .booking-card .visa-form .form-group input[type="text"] {
            /* Uses general .booking-card .form-group input styles (width: 100%, padding, border, etc.) */
        }
        /* Dark mode styles for visa form inputs */
        body.dark-mode .booking-card .visa-form .form-group input[type="text"] {
            background: #18191c;
            color: #e4e6eb;
            border-color: #23272f;
        }
        body.dark-mode .booking-card .visa-form .form-group input[type="text"]:focus {
            border-color: #4dabf7;
            background: #23272f;
        }
        /* Styling for all buttons and button-like links within the booking card */
        .booking-card button,
        .booking-card a.btn-primary,
        .booking-card a.btn-danger {
             padding: 1rem 2rem; /* Consistent padding */
             border-radius: 8px; /* Consistent rounded corners */
             border: none; /* Remove default border */
             font-size: 1.1rem; /* Consistent font size */
             font-weight: 600; /* Consistent font weight */
             cursor: pointer; /* Indicate clickable */
             transition: background 0.3s, opacity 0.3s; /* Smooth transitions */
             text-align: center; /* Center text */
             text-decoration: none; /* Remove underline from links */
             display: inline-block; /* Ensure anchor tags behave like blocks for styling */
             flex-grow: 1; /* Allow buttons to grow */
             flex-basis: 0; /* Allow buttons to shrink equally */
             box-sizing: border-box; /* Include padding and border in total size */
        }
        
        /* Primary button specific styles */
        .booking-card .btn-primary {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%); /* Blue gradient */
            color: #fff; /* White text */
        }
        .booking-card .btn-primary:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%); /* Darker blue on hover */
            opacity: 0.9; /* Slight opacity change on hover */
        }
        
        /* Danger button specific styles */
        .booking-card .btn-danger {
            background: linear-gradient(90deg, #fa5252 0%, #e03131 100%); /* Red gradient */
            color: #fff; /* White text */
        }
        .booking-card .btn-danger:hover {
            background: linear-gradient(90deg, #e03131 0%, #c92a2a 100%); /* Darker red on hover */
            opacity: 0.9; /* Slight opacity change on hover */
        }
        .booking-card .form-group input[type="text"],
        .booking-card .form-group input[type="email"],
        .booking-card .form-group input[type="password"],
        .booking-card .form-group input[type="number"] {
            background: #fff;
            color: #232946;
            border: 1.5px solid #b0b8c9;
            font-weight: 600;
            box-shadow: none;
        }
        .booking-card .form-group input[type="text"]::placeholder,
        .booking-card .form-group input[type="email"]::placeholder,
        .booking-card .form-group input[type="password"]::placeholder,
        .booking-card .form-group input[type="number"]::placeholder {
            color: #b0b8c9;
            opacity: 1;
        }
        .booking-card .error-message, .container .error-message {
            background: #fae1e1;
            color: #c0392b;
            border-radius: 8px;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.2rem;
            text-align: center;
            font-weight: 500;
            border: 1.5px solid #e74c3c33;
            font-size: 1rem;
        }
        .booking-card .success-message, .container .success-message {
            background: #e6fcf5;
            color: #27ae60;
            border-radius: 8px;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.2rem;
            text-align: center;
            font-weight: 500;
            border: 1.5px solid #27ae6033;
            font-size: 1rem;
        }
        .booking-card .ticket-row, .container .ticket-row {
            margin-bottom: 0.5rem;
            font-size: 1.13rem;
        }
        .booking-card .ticket-label, .container .ticket-label {
            font-weight: 700;
            color: #4dabf7;
        }
        .booking-card .ticket-value, .container .ticket-value {
            color: #fff;
            margin-left: 0.5rem;
        }
        .booking-card .status-paid, .container .status-paid {
            color: #27ae60;
            font-weight: 700;
        }
        .booking-card .status-unpaid, .container .status-unpaid {
            color: #e74c3c;
            font-weight: 700;
        }
        .ticket-header {
            font-size: 1.2rem;
            font-weight: 800;
            color: #4dabf7;
            margin-bottom: 0.7rem;
            letter-spacing: 0.5px;
        }
        .ticket-body {
            margin-bottom: 1.2rem;
        }
        a {
            color: #4dabf7;
            text-decoration: underline;
            font-weight: 600;
            transition: color 0.2s;
        }
        a:hover {
            color: #217dbb;
        }
        /* Dark mode */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #23242a 100%);
            color: #e4e6eb;
        }
        body.dark-mode .booking-card, body.dark-mode .container {
            background: rgba(34, 36, 42, 0.97);
            color: #e4e6eb;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            border: 1.5px solid rgba(255,255,255,0.04);
        }
        body.dark-mode .booking-card h1,
        body.dark-mode .booking-card h2,
        body.dark-mode .booking-card h3,
        body.dark-mode .booking-card label,
        body.dark-mode .booking-card strong,
        body.dark-mode .booking-card .ticket-label,
        body.dark-mode .booking-card .ticket-value,
        body.dark-mode .booking-card, body.dark-mode .booking-card * {
            color: #e4e6eb !important;
        }
        body.dark-mode .booking-card input[type="text"],
        body.dark-mode .booking-card input[type="email"],
        body.dark-mode .booking-card input[type="password"],
        body.dark-mode .booking-card input[type="number"],
        body.dark-mode .container input[type="text"],
        body.dark-mode .container input[type="email"],
        body.dark-mode .container input[type="password"],
        body.dark-mode .container input[type="number"] {
            background: #18191c;
            color: #e4e6eb;
            border-color: #23272f;
        }
        body.dark-mode .booking-card input[type="text"]:focus,
        body.dark-mode .booking-card input[type="email"]:focus,
        body.dark-mode .booking-card input[type="password"]:focus,
        body.dark-mode .booking-card input[type="number"]:focus,
        body.dark-mode .container input[type="text"]:focus,
        body.dark-mode .container input[type="email"]:focus,
        body.dark-mode .container input[type="password"]:focus,
        body.dark-mode .container input[type="number"]:focus {
            border-color: #4dabf7;
            background: #23272f;
        }
        body.dark-mode .booking-card .ticket-label, body.dark-mode .container .ticket-label {
            color: #4dabf7;
        }
        body.dark-mode .booking-card .ticket-value, body.dark-mode .container .ticket-value {
            color: #fff;
        }
        body.dark-mode .booking-card .status-paid, body.dark-mode .container .status-paid {
            color: #27ae60;
        }
        body.dark-mode .booking-card .status-unpaid, body.dark-mode .container .status-unpaid {
            color: #fa5252;
        }
        body.dark-mode a {
            color: #4dabf7;
        }
        body.dark-mode a:hover {
            color: #74c0fc;
        }
        /* Dark mode overrides for navbar */
        body.dark-mode .navbar {
            background: #181c2f;
            box-shadow: 0 2px 8px rgba(0,0,0,0.18);
        }
        body.dark-mode .logo, body.dark-mode .nav-links a, body.dark-mode .user-welcome {
            color: #fff !important;
        }
        body.dark-mode .nav-links a:hover {
            background: #22334a;
            color: #fff !important;
        }
        body.dark-mode .toggle-label .sun {
            color: #f7c873;
        }
        body.dark-mode .toggle-label .moon {
            color: #ffe066;
        }
        body.dark-mode .nav-links a, body.dark-mode .user-welcome {
            color: #fff !important;
        }
        body.dark-mode .nav-links a:hover {
            background: #22334a;
            color: #fff !important;
        }
        .logo, .nav-links a, .user-welcome {
            color: #232946 !important;
            text-shadow: none;
        }
        .nav-links a:hover {
            background: #e3eafc;
            color: #232946 !important;
        }
        .login-btn {
            background: #fa5252 !important;
            color: #fff !important;
            border-radius: 8px;
            font-weight: 700;
            padding: 0.5rem 1.5rem;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: #e03131 !important;
        }
        /* Styling for button containers within the booking card */
        .booking-card .booking-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-flex">
            <div class="logo" style="display:flex;align-items:center;gap:0.5rem;font-size:2.2rem;font-weight:900;">
                <span style="display:inline-block;vertical-align:middle;line-height:0;">
                  <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right:0.2rem;">
                    <g transform="rotate(-25 18 18)">
                      <rect x="15" y="6" width="6" height="18" rx="3" fill="#d1cfe2"/>
                      <rect x="10" y="18" width="16" height="4" rx="2" fill="#2196f3"/>
                      <rect x="17" y="2" width="2" height="8" rx="1" fill="#2196f3"/>
                      <rect x="17" y="26" width="2" height="8" rx="1" fill="#2196f3"/>
                      <circle cx="18" cy="6" r="2" fill="#d1cfe2"/>
                    </g>
                  </svg>
                </span>✈️ Egypto Airlines
            </div>
            <ul class="nav-links" style="margin-left:auto;display:flex;align-items:center;gap:3rem;">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span></li>
                <li><a href="logout.php" class="login-btn">Logout</a></li>
                <li>
                    <div class="mode-switch">
                        <input type="checkbox" id="mode-toggle" />
                        <label for="mode-toggle" class="toggle-label">
                            <span class="sun">☀️</span>
                            <span class="moon">🌙</span>
                        </label>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <div class="booking-card">
            <h1>Booking Page</h1>
            <?php if ($booking_success): ?>
                <h2>Booking Confirmed!</h2>
                <div class="ticket">
                    <div class="ticket-header">Virtual Flight Ticket</div>
                    <div class="ticket-body">
                        <div class="ticket-row"><span class="ticket-label">Booking Ref:</span> <span class="ticket-value"><?php echo isset($booking_id) ? str_pad($booking_id, 6, '0', STR_PAD_LEFT) : 'N/A'; ?></span></div>
                        <div class="ticket-row"><span class="ticket-label">Passenger:</span> <span class="ticket-value"><?php echo htmlspecialchars($user['username']); ?></span></div>
                        <div class="ticket-row"><span class="ticket-label">Email:</span> <span class="ticket-value"><?php echo htmlspecialchars($user['email']); ?></span></div>
                        <div class="ticket-row"><span class="ticket-label">Destination:</span> <span class="ticket-value"><?php echo htmlspecialchars($offer['destination']); ?></span></div>
                        <div class="ticket-row"><span class="ticket-label">Departure:</span> <span class="ticket-value"><?php echo htmlspecialchars($offer['departure_date']); ?></span></div>
                        <?php if ($offer['trip_type'] === 'Round-trip'): ?>
                        <div class="ticket-row"><span class="ticket-label">Return:</span> <span class="ticket-value"><?php echo htmlspecialchars($offer['return_date']); ?></span></div>
                        <?php endif; ?>
                        <div class="ticket-row"><span class="ticket-label">Price:</span> <span class="ticket-value">$<?php echo number_format($offer['price'], 2); ?></span></div>
                        <div class="ticket-row"><span class="ticket-label">Status:</span> <span class="ticket-value" style="color:#27ae60;">Paid (Simulated)</span></div>
                    </div>
                </div>
                <a href="destination_dashboard.php">&larr; Back to Dashboard</a>
            <?php elseif ($visa_step): ?>
                <h2>Enter Visa Information</h2>
                <form method="post" class="visa-form" style="margin-bottom:1.5rem;">
                    <div class="form-group">
                        <label for="visa_number">Visa Number</label>
                        <input type="text" name="visa_number" id="visa_number" required value="<?php echo htmlspecialchars($visa_info['number']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visa_expiry">Expiry Date</label>
                        <input type="text" name="visa_expiry" id="visa_expiry" placeholder="MM/YY" required value="<?php echo htmlspecialchars($visa_info['expiry']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visa_name">Name on Card</label>
                        <input type="text" name="visa_name" id="visa_name" required value="<?php echo htmlspecialchars($visa_info['name']); ?>">
                    </div>
                    <div class="booking-actions" style="margin-top: 2rem;">
                        <button type="submit" name="submit_visa" class="btn-primary">Submit & Confirm Booking</button>
                        <a href="destination_dashboard.php" class="btn-danger">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <h2>Confirm Your Booking</h2>
                <h3>Trip Details</h3>
                <ul>
                    <li><strong>Destination:</strong> <?php echo htmlspecialchars($offer['destination']); ?></li>
                    <li><strong>Departure:</strong> <?php echo htmlspecialchars($offer['departure_date']); ?></li>
                    <?php if ($offer['trip_type'] === 'Round-trip'): ?>
                    <li><strong>Return:</strong> <?php echo htmlspecialchars($offer['return_date']); ?></li>
                    <?php endif; ?>
                    <li><strong>Price:</strong> $<?php echo number_format($offer['price'], 2); ?></li>
                </ul>
                <form method="post" class="booking-actions">
                    <button type="submit" name="confirm_booking" class="btn-primary">Confirm Booking</button>
                    <a href="destination_dashboard.php" class="btn-danger">Cancel</a>
                </form>
            <?php endif; ?>
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
        
        // On load, set mode from localStorage
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