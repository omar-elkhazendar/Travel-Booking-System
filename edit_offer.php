<?php
require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin_login.php");
    exit;
}

// Get offer ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid offer ID.";
    exit;
}
$offer_id = intval($_GET['id']);

// Fetch offer data
$sql = "SELECT * FROM travel_offers WHERE id = ?";
$offer = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $offer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $offer = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
if (!$offer) {
    echo "Offer not found.";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $departure_date = trim($_POST['departure_date']);
    $return_date = trim($_POST['return_date']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $outbound_time = isset($_POST['outbound_time']) ? $_POST['outbound_time'] : null;
    $return_time = isset($_POST['return_time']) ? $_POST['return_time'] : null;
    $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'Round-trip';
    $image_path = $offer['image_path'];

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

    $sql = "UPDATE travel_offers SET destination=?, departure_date=?, return_date=?, price=?, description=?, image_path=?, outbound_time=?, return_time=?, trip_type=? WHERE id=?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssssssssi", $destination, $departure_date, $return_date, $price, $description, $image_path, $outbound_time, $return_time, $trip_type, $offer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: admin.php");
        exit;
    } else {
        echo '<div style="color:red;">Error: ' . mysqli_error($conn) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Travel Offer</title>
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
        .container {
            max-width: 500px;
            margin: 6rem auto 4rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.10);
            padding: 2rem;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 2rem;
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px;
            font-size: 1.08rem;
            background: #f8fafc;
            transition: border-color 0.3s ease, background 0.3s ease;
        }
        .form-group textarea { min-height: 80px; }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #2980b9;
            outline: none;
            background: #e3eafc;
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #217dbb 0%, #145374 100%);
        }
        .current-image { margin-bottom: 1rem; text-align: center; }
        .current-image img { max-width: 120px; max-height: 80px; border-radius: 8px; }
        /* Dark mode styles */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        .dark-mode .container {
            background: #2d2f34;
            box-shadow: 0 4px 16px rgba(0,0,0,0.20);
        }
        .dark-mode h2 {
            color: #e4e6eb;
        }
        .dark-mode .form-group label {
            color: #e4e6eb;
        }
        .dark-mode .form-group input, .dark-mode .form-group textarea {
            background: #1a1b1e;
            border-color: #3d3f44;
            color: #e4e6eb;
        }
        .dark-mode .form-group input:focus, .dark-mode .form-group textarea:focus {
            border-color: #4dabf7;
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
        @media (max-width: 800px) {
            .container, .navbar-content {
                padding: 1rem;
            }
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
    <div class="container" style="margin-top:6rem;">
        <h2>Edit Travel Offer</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="destination">Destination</label>
                <input type="text" name="destination" id="destination" value="<?php echo htmlspecialchars($offer['destination']); ?>" required>
            </div>
            <div class="form-group">
                <label for="departure_date">Departure Date</label>
                <input type="date" name="departure_date" id="departure_date" value="<?php echo htmlspecialchars($offer['departure_date']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" name="price" id="price" step="0.01" value="<?php echo htmlspecialchars($offer['price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" required><?php echo htmlspecialchars($offer['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="outbound_time">Outbound Time</label>
                <input type="time" name="outbound_time" id="outbound_time" value="<?php echo htmlspecialchars($offer['outbound_time'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="trip_type">Trip Type</label>
                <select name="trip_type" id="trip_type" required onchange="toggleReturnFields()">
                    <option value="Round-trip" <?php if(($offer['trip_type'] ?? '') === 'Round-trip') echo 'selected'; ?>>Round-trip</option>
                    <option value="One-way" <?php if(($offer['trip_type'] ?? '') === 'One-way') echo 'selected'; ?>>One-way</option>
                </select>
            </div>
            <div class="form-group" id="return_date_group">
                <label for="return_date">Return Date</label>
                <input type="date" name="return_date" id="return_date" value="<?php echo htmlspecialchars($offer['return_date'] ?? ''); ?>">
            </div>
            <div class="form-group" id="return_time_group">
                <label for="return_time">Return Time</label>
                <input type="time" name="return_time" id="return_time" value="<?php echo htmlspecialchars($offer['return_time'] ?? ''); ?>">
            </div>
            <div class="form-group current-image">
                <?php if (!empty($offer['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($offer['image_path']); ?>" alt="Current Image">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image">Change Image</label>
                <input type="file" name="image" id="image" accept="image/*">
            </div>
            <button type="submit" class="btn">Update Offer</button>
        </form>
    </div>
</body>
</html>
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
    function toggleReturnFields() {
        var tripType = document.getElementById('trip_type').value;
        var returnDateGroup = document.getElementById('return_date_group');
        var returnTimeGroup = document.getElementById('return_time_group');
        if (tripType === 'One-way') {
            returnDateGroup.style.display = 'none';
            returnTimeGroup.style.display = 'none';
        } else {
            returnDateGroup.style.display = '';
            returnTimeGroup.style.display = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        toggleReturnFields();
    });
</script> 