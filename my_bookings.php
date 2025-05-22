<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Handle deletion request
if (isset($_POST['request_delete'])) {
    $booking_id = $_POST['booking_id'];
    
    $sql = "UPDATE bookings SET delete_request = TRUE, delete_request_date = NOW() WHERE id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION["id"]);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Deletion request submitted. Waiting for admin approval.";
        } else {
            $_SESSION['error_message'] = "Error submitting deletion request: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: my_bookings.php");
    exit;
}

// Fetch user's bookings
$bookings = [];
$sql = "SELECT b.*, t.destination, t.price, t.departure_date, t.return_date 
        FROM bookings b 
        JOIN travel_offers t ON b.travel_offer_id = t.id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
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
    <title>My Bookings - Travel Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="my_bookings.css">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e3eafc 100%);
            color: #222;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }

        .container {
            max-width: 1200px;
            margin: 7rem auto 2rem auto;
            padding: 2rem;
        }
        
        .bookings-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .bookings-header h1 {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .table-container {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.10);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1.2rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f8fafc;
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.05rem;
        }
        
        td {
            color: #444;
            font-size: 1.05rem;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status.active {
            background: #e3fcef;
            color: #059669;
        }
        
        .status.pending {
            background: #fff7ed;
            color: #c2410c;
        }
        
        .status.cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-request-delete {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(239,68,68,0.15);
        }
        
        .btn-request-delete:hover {
            background: linear-gradient(90deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.25);
        }
        
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Dark mode styles */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }

        body.dark-mode .table-container {
            background: #23242a;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }

        body.dark-mode th {
            background: #2d2f34;
            color: #e4e6eb;
        }

        body.dark-mode td {
            color: #e4e6eb;
            border-bottom-color: #3d3f44;
        }

        body.dark-mode tr:hover {
            background: #2d2f34;
        }

        body.dark-mode .bookings-header h1 {
            color: #e4e6eb;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                margin-top: 5rem;
            }
            
            th, td {
                padding: 0.8rem;
                font-size: 0.95rem;
            }
            
            .table-container {
                overflow-x: auto;
            }

            .btn-request-delete {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="bookings-header">
            <h1>My Bookings</h1>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Departure Date</th>
                        <th>Return Date</th>
                        <th>Price</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['destination']); ?></td>
                        <td><?php echo htmlspecialchars($booking['departure_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['return_date']); ?></td>
                        <td>$<?php echo number_format($booking['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                        <td>
                            <?php if ($booking['delete_request']): ?>
                                <span class="status pending">Deletion Requested</span>
                            <?php else: ?>
                                <span class="status active"><?php echo htmlspecialchars($booking['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$booking['delete_request']): ?>
                                <form action="my_bookings.php" method="post" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="request_delete" class="btn-request-delete" onclick="return confirm('Are you sure you want to request deletion of this booking?')">
                                        Request Deletion
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 