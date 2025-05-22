<?php
require_once '../config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 1) {
    header("location: ../login.php");
    exit;
}

// Handle message deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_message"])) {
    $message_id = $_POST["message_id"];
    $sql = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Fetch all messages
$sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Messages - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .messages-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .message-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .message-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .message-info div {
            display: flex;
            flex-direction: column;
        }

        .message-info label {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        .message-content {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .message-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .no-messages {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="messages-container">
        <div class="messages-header">
            <h1>Contact Messages</h1>
            <p>View and manage messages from users</p>
        </div>

        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <h2>No messages yet</h2>
                <p>When users contact you through the contact form, their messages will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <div class="message-card">
                    <div class="message-header">
                        <h3><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></h3>
                        <span><?php echo date('M d, Y H:i', strtotime($message['created_at'] ?? 'now')); ?></span>
                    </div>

                    <div class="message-info">
                        <div>
                            <label>From:</label>
                            <span><?php echo htmlspecialchars($message['name'] ?? 'Anonymous'); ?></span>
                        </div>
                        <div>
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($message['email'] ?? 'No Email'); ?></span>
                        </div>
                    </div>

                    <div class="message-content">
                        <?php 
                        $messageText = $message['message'] ?? '';
                        echo nl2br(htmlspecialchars($messageText)); 
                        ?>
                    </div>

                    <div class="message-actions">
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this message?');">
                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                            <button type="submit" name="delete_message" class="delete-btn">Delete Message</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html> 