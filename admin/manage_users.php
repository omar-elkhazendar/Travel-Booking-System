<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow admin to delete themselves
    if ($user_id == $_SESSION['id']) {
        $error = "You cannot delete your own account.";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "User deleted successfully.";
            } else {
                $error = "Error deleting user.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle user creation/update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $password, $is_admin);
                if (mysqli_stmt_execute($stmt)) {
                    $message = "User created successfully.";
                } else {
                    $error = "Error creating user.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($_POST['action'] == 'update') {
            $user_id = $_POST['user_id'];
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Don't allow admin to remove their own admin status
            if ($user_id == $_SESSION['id'] && !$is_admin) {
                $error = "You cannot remove your own admin status.";
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssii", $username, $email, $is_admin, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $message = "User updated successfully.";
                    } else {
                        $error = "Error updating user.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Fetch all users
$users = [];
$sql = "SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }
        .btn-add {
            background: #3498db;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-add:hover {
            background: #2980b9;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .users-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #2c3e50;
        }
        .users-table tr:hover {
            background: #f8fafc;
        }
        .btn-edit,
        .btn-delete {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 0.5rem;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .form-group input[type="checkbox"] {
            width: auto;
        }
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }
        .btn-submit:hover {
            background: #2980b9;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        body.dark-mode {
            background: #1a1b1e;
            color: #e4e6eb;
        }
        body.dark-mode .admin-title {
            color: #e4e6eb;
        }
        body.dark-mode .users-table {
            background: #23242a;
            color: #e4e6eb;
        }
        body.dark-mode .users-table th {
            background: #1a1b1e;
            color: #e4e6eb;
        }
        body.dark-mode .users-table tr:hover {
            background: #1a1b1e;
        }
        body.dark-mode .modal-content {
            background: #23242a;
            color: #e4e6eb;
        }
        body.dark-mode .modal-title {
            color: #e4e6eb;
        }
        body.dark-mode .form-group label {
            color: #e4e6eb;
        }
        body.dark-mode .form-group input {
            background: #1a1b1e;
            border-color: #3d3f44;
            color: #e4e6eb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Manage Users</h1>
            <button class="btn-add" onclick="openModal('create')">Add New User</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Admin</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="#" class="btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</a>
                        <?php if ($user['id'] != $_SESSION['id']): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Create/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New User</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" id="isAdmin">
                        Admin User
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">Save User</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, user = null) {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('modalTitle');
            const passwordGroup = document.getElementById('passwordGroup');
            
            if (action === 'create') {
                title.textContent = 'Add New User';
                form.reset();
                document.getElementById('formAction').value = 'create';
                passwordGroup.style.display = 'block';
                document.getElementById('password').required = true;
            } else {
                title.textContent = 'Edit User';
                document.getElementById('formAction').value = 'update';
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('isAdmin').checked = user.is_admin == 1;
                passwordGroup.style.display = 'none';
                document.getElementById('password').required = false;
            }
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 