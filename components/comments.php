<?php
// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo '<div class="login-prompt">Please <a href="login.php">login</a> to leave a comment.</div>';
    return;
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment']) && isset($_POST['page_id'])) {
    $comment = trim($_POST['comment']);
    $page_id = trim($_POST['page_id']);
    $user_id = $_SESSION['id'];
    
    if (!empty($comment)) {
        $sql = "INSERT INTO comments (user_id, comment, page_id) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $comment, $page_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("location: " . $_SERVER['REQUEST_URI'] . "?success=1");
            exit;
        }
    }
}

// Get the current page ID from the URL
$page_id = basename($_SERVER['PHP_SELF'], '.php');

// Fetch comments for this page
$sql = "SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.page_id = ?
        ORDER BY c.created_at DESC";
$comments = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $page_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="comments-section">
    <h3>Comments</h3>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Your comment has been posted successfully!
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="comment-form">
        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars($page_id); ?>">
        <textarea name="comment" class="comment-input" placeholder="Write your comment here..." required></textarea>
        <button type="submit" class="submit-btn">Post Comment</button>
    </form>

    <div class="comment-list">
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <span class="username"><?php echo htmlspecialchars($comment['username']); ?></span>
                    <span class="date"><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></span>
                </div>
                <div class="comment-content">
                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .comments-section {
        margin: 2rem 0;
        padding: 1.5rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .login-prompt {
        text-align: center;
        padding: 1rem;
        background: #f3f4f6;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .login-prompt a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
    }

    .login-prompt a:hover {
        text-decoration: underline;
    }

    .comment-form {
        margin-bottom: 2rem;
    }

    .comment-input {
        width: 100%;
        padding: 1rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 1rem;
        margin-bottom: 1rem;
        resize: vertical;
        min-height: 100px;
    }

    .comment-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .submit-btn {
        background: #3b82f6;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .submit-btn:hover {
        background: #2563eb;
    }

    .comment-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .comment {
        padding: 1rem;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .comment-content {
        color: #1f2937;
        line-height: 1.5;
    }

    .success-message {
        background: #d1fae5;
        color: #065f46;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        text-align: center;
    }
</style> 