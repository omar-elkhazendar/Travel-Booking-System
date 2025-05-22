<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Get username from session
$username = $_SESSION["username"];

// Get trip type from GET or default to 'Round-trip'
$selected_trip_type = isset($_GET['trip_type']) ? $_GET['trip_type'] : 'Round-trip';

// Build dynamic WHERE clause for all filters
$where = [];
if ($selected_trip_type !== 'All') {
    $where[] = "trip_type = '" . mysqli_real_escape_string($conn, $selected_trip_type) . "'";
}
if (!empty($_GET['destination'])) {
    $destination = mysqli_real_escape_string($conn, $_GET['destination']);
    $where[] = "destination LIKE '%$destination%'";
}
if (!empty($_GET['departure_date'])) {
    $departure_date = mysqli_real_escape_string($conn, $_GET['departure_date']);
    $where[] = "departure_date = '$departure_date'";
}
$sql = "SELECT * FROM travel_offers";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY departure_date ASC";
$result = mysqli_query($conn, $sql);
$offers = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count bookings for each offer
foreach ($offers as &$offer) {
    $offer_id = $offer['id'];
    $count_sql = "SELECT COUNT(*) as booked FROM bookings WHERE travel_offer_id = $offer_id AND status = 'confirmed'";
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    $offer['booked'] = $count_row['booked'];
}
unset($offer);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destination Dashboard - Travel Booking</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e3eafc 100%);
            color: #222;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1rem 2rem 1rem;
        }
      
        .login-btn {
            background: #3498db;
            color: #fff !important;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #217dbb !important;
        }
        .mode-switch {
            display: flex;
            align-items: center;
            margin-left: 1.5rem;
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
        @media (max-width: 700px) {
            .container {
                padding: 1.2rem 0.2rem;
            }
        }
        .dashboard-section h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #222;
            font-weight: 700;
        }
        .dashboard-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #2980b9;
            font-weight: 600;
        }
        .search-filters {
            margin-bottom: 2.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .search-filters input[type="text"],
        .search-filters input[type="date"] {
            padding: 0.9rem;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px;
            font-size: 1.05rem;
            width: 100%;
            background: #fafdff;
            transition: border 0.2s;
        }
        .search-filters input:focus {
            border-color: #2980b9;
        }
        .search-filters button {
            padding: 0.9rem 2.2rem;
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(41,128,185,0.07);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .search-filters button:hover {
            background: linear-gradient(90deg, #217dbb 0%, #145374 100%);
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
        }
        .flights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 2.2rem;
        }
        .flight-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(41,128,185,0.10);
            padding: 1.7rem 1.3rem 1.3rem 1.3rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: box-shadow 0.22s, transform 0.22s;
            position: relative;
        }
        .flight-card:hover {
            box-shadow: 0 12px 40px rgba(41,128,185,0.18);
            transform: translateY(-4px) scale(1.02);
        }
        .flight-card img {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1.1rem;
            box-shadow: 0 2px 12px rgba(41,128,185,0.07);
        }
        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .flight-header h3 {
            font-size: 1.28rem;
            font-weight: 700;
            margin: 0;
            color: #222;
        }
        .flight-header .price {
            color: #007bff;
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .flight-status-row {
            margin: 0.5rem 0 0.7rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .flight-status-row .circle {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid #1e824c;
            background: #27ae60;
        }
        .flight-status-row .circle.red {
            background: #e74c3c;
            border-color: #c0392b;
        }
        .flight-status-row .status-text {
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.2px;
        }
        .flight-status-row .status-text.green {
            color: #27ae60;
        }
        .flight-status-row .status-text.red {
            color: #e74c3c;
        }
        .flight-details {
            margin-bottom: 0.7rem;
            font-size: 1.04rem;
        }
        .flight-details .label {
            color: #888;
            font-size: 0.99rem;
        }
        .flight-details .value {
            font-weight: 500;
            margin-left: 0.3rem;
        }
        .flight-description {
            margin-bottom: 1.1rem;
            color: #444;
            font-size: 1.08rem;
            font-style: italic;
        }
        .flight-actions {
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }
        .flight-actions .btn {
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.7rem;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(41,128,185,0.07);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .flight-actions .btn:hover {
            background: linear-gradient(90deg, #217dbb 0%, #145374 100%);
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
        }
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        .dark-mode .container {
            background: transparent;
        }
        .dark-mode .dashboard-section h2 {
            color: #e4e6eb;
        }
        .dark-mode .dashboard-section h3 {
            color: #4dabf7;
        }
        .dark-mode .search-filters input[type="text"],
        .dark-mode .search-filters input[type="date"] {
            background: #2d2f34;
            border-color: #3d3f44;
            color: #e4e6eb;
        }
        .dark-mode .search-filters input:focus {
            border-color: #4dabf7;
        }
        .dark-mode .search-filters button {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%);
        }
        .dark-mode .search-filters button:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%);
        }
        .dark-mode .flight-card {
            background: #2d2f34;
            box-shadow: 0 6px 32px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .flight-card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        .dark-mode .flight-header h3 {
            color: #e4e6eb;
        }
        .dark-mode .flight-header .price {
            color: #4dabf7;
        }
        .dark-mode .flight-description {
            color: #adb5bd;
        }
        .dark-mode .flight-details .label {
            color: #adb5bd;
        }
        .dark-mode .flight-details .value {
            color: #e4e6eb;
        }
        .dark-mode .flight-actions .btn {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%);
        }
        .dark-mode .flight-actions .btn:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%);
        }
        .dark-mode footer {
            background: #1a1b1e;
            color: #adb5bd;
        }
        .dark-mode .mode-switch {
            color: #e4e6eb;
        }
        .navbar.dark-mode, .navbar.dark-mode * {
            background: #232946 !important;
            color: #f4f4f4 !important;
        }
        .flight-card.dark-mode {
            background: #232946;
            color: #f4f4f4;
            box-shadow: 0 6px 32px rgba(41,128,185,0.18);
        }
        .flight-card.dark-mode .flight-header h3,
        .flight-card.dark-mode .flight-header .price,
        .flight-card.dark-mode .flight-description {
            color: #f4f4f4;
        }
        .flight-card.dark-mode .flight-actions .btn {
            background: linear-gradient(90deg, #3a86ff 0%, #232946 100%);
            color: #fff;
        }
        .flight-card.dark-mode .flight-actions .btn:hover {
            background: linear-gradient(90deg, #232946 0%, #3a86ff 100%);
        }
        .mode-switch {
            display: flex;
            align-items: center;
            margin-left: 1.5rem;
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
        .user-welcome {
            color: #2c3e50;
            font-weight: 500;
            margin: 0 1rem;
        }
        .dark-mode .user-welcome {
            color: #e4e6eb;
        }
        .login-btn {
            background: var(--primary);
            color: #fff !important;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: var(--primary-dark) !important;
        }
        .dark-mode .login-btn {
            background: #4dabf7;
        }
        .dark-mode .login-btn:hover {
            background: #339af0 !important;
        }
        /* Adjust main container for fixed navbar */
        .dashboard-container, .container {
            margin-top: 5.5rem !important;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-flex">
            <div class="logo">✈️ Egypto Airlines</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
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
    <div class="dashboard-container">
        <main class="container">
            <section class="dashboard-section">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
                <h3>Available Flights</h3>
                
                <div class="search-filters">
                    <form method="GET" action="">
                        <input type="text" name="destination" placeholder="Search destination..." value="<?php echo isset($_GET['destination']) ? htmlspecialchars($_GET['destination']) : ''; ?>">
                        <input type="date" name="departure_date" placeholder="Departure date" value="<?php echo isset($_GET['departure_date']) ? htmlspecialchars($_GET['departure_date']) : ''; ?>">
                        <input type="hidden" name="trip_type" id="trip_type_hidden" value="<?php echo htmlspecialchars($selected_trip_type); ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <div class="trip-type-bar" style="display:flex;justify-content:center;align-items:center;margin-bottom:2rem;gap:1rem;">
                    <label for="global_trip_type" style="font-weight:600;font-size:1.1rem;">Trip Type:</label>
                    <form id="tripTypeForm" method="GET" action="" style="display:inline-flex;align-items:center;gap:0.5rem;">
                        <input type="hidden" name="destination" value="<?php echo isset($_GET['destination']) ? htmlspecialchars($_GET['destination']) : ''; ?>">
                        <input type="hidden" name="departure_date" value="<?php echo isset($_GET['departure_date']) ? htmlspecialchars($_GET['departure_date']) : ''; ?>">
                        <select id="global_trip_type" name="trip_type" style="padding:0.5rem 1.2rem;border-radius:7px;font-size:1.1rem;">
                            <option value="Round-trip" <?php if($selected_trip_type==='Round-trip') echo 'selected'; ?>>Round-trip</option>
                            <option value="One-way" <?php if($selected_trip_type==='One-way') echo 'selected'; ?>>One-way</option>
                            <option value="All" <?php if($selected_trip_type==='All') echo 'selected'; ?>>All</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding:0.5rem 1.2rem;font-size:1.1rem;">Confirm</button>
                    </form>
                </div>

                <div class="flights-grid">
                    <?php foreach ($offers as $offer): ?>
                        <div class="flight-card">
                            <?php if (!empty($offer['image_path'])): ?>
                                <div class="flight-image">
                                    <img src="<?php echo htmlspecialchars($offer['image_path']); ?>" alt="Travel Image" style="max-width:100%;max-height:120px;display:block;margin:0 auto 10px;">
                                </div>
                            <?php endif; ?>
                            <div class="flight-header">
                                <h3><?php echo htmlspecialchars($offer['destination']); ?></h3>
                                <span class="price">$<?php echo number_format($offer['price'], 2); ?></span>
                            </div>
                            <div class="flight-status-row">
                                <?php $is_full = isset($offer['max_passengers']) && $offer['booked'] >= $offer['max_passengers']; ?>
                                <?php if ($is_full): ?>
                                    <span class="lock-icon" style="color:#e74c3c;font-size:1.5rem;vertical-align:middle;">&#128274;</span>
                                    <span class="status-text red" style="font-weight:700;">Full</span>
                                <?php else: ?>
                                    <span class="lock-icon" style="color:#27ae60;font-size:1.5rem;vertical-align:middle;">&#128275;</span>
                                    <span class="status-text green" style="font-weight:700;">Available Now</span>
                                <?php endif; ?>
                                <span style="margin-left:10px;font-size:1rem;color:#2980b9;font-weight:600;">(<?php echo $offer['booked'] ?? 0; ?>/<?php echo $offer['max_passengers'] ?? 100; ?> passengers)</span>
                            </div>
                            <div class="flight-details">
                                <div class="detail">
                                    <span class="label">Departure:</span>
                                    <span class="value"><?php echo date('M d, Y', strtotime($offer['departure_date'])); ?></span>
                                </div>
                                <div class="detail">
                                    <span class="label">Outbound Time:</span>
                                    <span class="value"><?php echo isset($offer['outbound_time']) ? htmlspecialchars($offer['outbound_time']) : '--:--'; ?></span>
                                </div>
                                <?php if ($offer['trip_type'] === 'Round-trip'): ?>
                                <div class="detail">
                                    <span class="label">Return:</span>
                                    <span class="value"><?php echo date('M d, Y', strtotime($offer['return_date'])); ?></span>
                                </div>
                                <div class="detail">
                                    <span class="label">Return Time:</span>
                                    <span class="value"><?php echo isset($offer['return_time']) ? htmlspecialchars($offer['return_time']) : '--:--'; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flight-description">
                                <?php echo htmlspecialchars($offer['description']); ?>
                            </div>
                            <div class="flight-actions">
                                <form method="get" action="booking.php" style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                    <input type="hidden" name="trip_type" value="<?php echo htmlspecialchars($offer['trip_type']); ?>">
                                    <?php if ($is_full): ?>
                                        <button class="btn btn-secondary" disabled>Full</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-primary">Book Now</button>
                                    <?php endif; ?>
                                </form>
                               
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <footer>
            <div class="container">
                <p>&copy; 2024 Travel Booking. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script src="js/dashboard.js"></script>
    <script>
    // Dark mode toggle logic
    const modeToggle = document.getElementById('mode-toggle');
    const body = document.body;
    function setMode(dark) {
        if (dark) {
            body.classList.add('dark-mode');
            document.querySelector('.navbar').classList.add('dark-mode');
            document.querySelectorAll('.flight-card').forEach(card => card.classList.add('dark-mode'));
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.remove('dark-mode');
            document.querySelector('.navbar').classList.remove('dark-mode');
            document.querySelectorAll('.flight-card').forEach(card => card.classList.remove('dark-mode'));
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