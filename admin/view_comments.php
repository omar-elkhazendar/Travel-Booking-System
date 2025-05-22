<?php
require_once '../config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 1) {
    header("location: ../login.php");
    exit;
}

// Handle comment deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $sql = "DELETE FROM comments WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $comment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location: view_comments.php?success=1");
        exit;
    }
}

// Fetch all comments with detailed user information
$sql = "SELECT c.*, u.username, u.email, u.created_at as user_joined 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC";
$comments = [];
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Comments - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .comments-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .comment {
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        .user-info {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .user-info p {
            margin: 0.5rem 0;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .comment-content {
            color: #1f2937;
            line-height: 1.5;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .comment-actions {
            display: flex;
            gap: 1rem;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .page-link {
            color: #3b82f6;
            text-decoration: none;
        }

        .page-link:hover {
            text-decoration: underline;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .no-comments {
            text-align: center;
            color: #6b7280;
            padding: 2rem;
        }

        .stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-box {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 6px;
            flex: 1;
            min-width: 200px;
            text-align: center;
        }

        .stat-box h3 {
            margin: 0;
            color: #4b5563;
            font-size: 0.875rem;
        }

        .stat-box p {
            margin: 0.5rem 0 0;
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <div class="container">
        <div class="comments-container">
            <h1>User Comments Management</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    Comment has been deleted successfully!
                </div>
            <?php endif; ?>

            <div class="stats">
                <div class="stat-box">
                    <h3>Total Comments</h3>
                    <p><?php echo count($comments); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Unique Users</h3>
                    <p><?php echo count(array_unique(array_column($comments, 'user_id'))); ?></p>
                </div>
            </div>

            <?php if (empty($comments)): ?>
                <div class="no-comments">
                    No comments have been made yet.
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="user-info">
                            <p><strong>User:</strong> <?php echo htmlspecialchars($comment['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($comment['email']); ?></p>
                            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($comment['user_joined'])); ?></p>
                        </div>
                        <div class="comment-header">
                            <div>
                                <strong>Page:</strong> <a href="../<?php echo htmlspecialchars($comment['page_id']); ?>.php" class="page-link"><?php echo htmlspecialchars($comment['page_id']); ?></a>
                            </div>
                            <div>
                                <strong>Posted:</strong> <?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                        <div class="comment-actions">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <button type="submit" name="delete_comment" class="delete-btn">Delete Comment</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html> 