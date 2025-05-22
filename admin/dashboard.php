<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 1) {
    header("Location: ../login.php");
    exit();
}

// Handle form submission for adding new flight
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_flight"])) {
    $destination = trim($_POST["destination"]);
    $departure_date = trim($_POST["departure_date"]);
    $return_date = trim($_POST["return_date"]);
    $price = trim($_POST["price"]);
    $description = trim($_POST["description"]);
    $trip_type = trim($_POST["trip_type"]);

    $sql = "INSERT INTO travel_offers (destination, departure_date, return_date, price, description, trip_type) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssds s", $destination, $departure_date, $return_date, $price, $description, $trip_type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch all travel offers
$sql = "SELECT * FROM travel_offers ORDER BY departure_date ASC";
$result = mysqli_query($conn, $sql);
$offers = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Travel Booking</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .admin-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .admin-form .form-group {
            margin-bottom: 1rem;
        }
        .admin-form label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .admin-form input, .admin-form textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .admin-form textarea {
            height: 100px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>Admin Dashboard</h1>
                <ul>
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <section class="dashboard-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
            
            <div class="admin-form">
                <h3>Add New Flight</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Destination</label>
                        <input type="text" name="destination" required>
                    </div>
                    <div class="form-group">
                        <label>Departure Date</label>
                        <input type="date" name="departure_date" required>
                    </div>
                    <div class="form-group">
                        <label>Return Date</label>
                        <input type="date" name="return_date" required>
                    </div>
                    <div class="form-group">
                        <label>Trip Type</label>
                        <select name="trip_type" required>
                            <option value="Round-trip">Round-trip</option>
                            <option value="One-way">One-way</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <button type="submit" name="add_flight" class="btn btn-primary">Add Flight</button>
                </form>
            </div>

            <h3>Manage Existing Flights</h3>
            <div class="flights-grid">
                <?php foreach ($offers as $offer): ?>
                    <div class="flight-card">
                        <div class="flight-header">
                            <h3><?php echo htmlspecialchars($offer['destination']); ?></h3>
                            <span class="price">$<?php echo number_format($offer['price'], 2); ?></span>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail">
                                <span class="label">Departure:</span>
                                <span class="value"><?php echo date('M d, Y', strtotime($offer['departure_date'])); ?></span>
                            </div>
                            <div class="detail">
                                <span class="label">Return:</span>
                                <span class="value"><?php echo date('M d, Y', strtotime($offer['return_date'])); ?></span>
                            </div>
                        </div>

                        <div class="flight-description">
                            <?php echo htmlspecialchars($offer['description']); ?>
                        </div>

                        <div class="flight-actions">
                            <a href="edit_flight.php?id=<?php echo $offer['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="delete_flight.php?id=<?php echo $offer['id']; ?>" class="btn btn-danger">Delete</a>
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
</body>
</html> 