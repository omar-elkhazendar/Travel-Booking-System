<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
require_once 'config/google_config.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            $param_username = trim($_POST["username"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            $param_email = trim($_POST["email"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)";
         
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_password);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
            } else {
                echo "Something went wrong. Please try again later.<br>";
                echo "MySQL error: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Travel Booking System</title>
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
            background: #fff;
            box-shadow: 0 2px 16px rgba(44, 62, 80, 0.10);
            padding: 0.7rem 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1200;
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
            gap: 2.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-link {
            color: #232946;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.2s;
        }
        .nav-link:hover {
            color: #3498db;
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
        .register-container, .container {
            margin-top: 5.5rem !important;
        }
        /* --- END NAVBAR STYLES --- */
        .container {
            max-width: 480px;
            margin: 7.5rem auto 2rem auto;
            padding: 3rem 2.2rem 2.2rem 2.2rem;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.13);
            transition: background 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.6rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #232946;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px;
            font-size: 1.08rem;
            transition: border-color 0.3s ease, background 0.3s ease;
            background: #f8fafc;
            color: #232946;
        }
        .form-group input:focus {
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
            transition: background 0.3s ease, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
        }
        .btn:hover {
            background: linear-gradient(90deg, #217dbb 0%, #145374 100%);
        }
        .error-message {
            background: #fae1e1;
            color: #c0392b;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1.2rem;
            text-align: center;
            font-weight: 500;
            border: 1px solid #e74c3c33;
        }
        .login-link {
            margin-top: 1.5rem;
            text-align: center;
        }
        .login-link a {
            color: #2980b9;
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        .login-link a:hover {
            color: #145374;
        }
        @media (max-width: 600px) {
            .container {
                padding: 1.2rem 0.5rem;
                margin: 5rem 0.5rem 1rem 0.5rem;
            }
        }
        /* Dark mode overrides for navbar */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        body.dark-mode .navbar {
            background: #23272f;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.25);
        }
        body.dark-mode .logo, body.dark-mode .nav-link {
            color: #fff;
        }
        body.dark-mode .nav-link:hover {
            color: #4dabf7;
        }
        body.dark-mode .toggle-label .sun {
            color: #f7c873;
        }
        body.dark-mode .toggle-label .moon {
            color: #ffe066;
        }
        body.dark-mode .container {
            background: #23242a;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.35);
        }
        body.dark-mode h2 {
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
        body.dark-mode .form-group input:focus {
            background: #23272f;
            border-color: #4dabf7;
        }
        body.dark-mode .btn {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%);
        }
        body.dark-mode .btn:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%);
        }
        body.dark-mode .error-message {
            background: #3d3f44;
            color: #fa5252;
            border: 1.5px solid #fa5252;
        }
        body.dark-mode .login-link a {
            color: #4dabf7;
        }
        body.dark-mode .login-link a:hover {
            color: #74c0fc;
        }
        .oauth-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-top: 1.2rem;
        }
        .oauth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            margin-bottom: 0.2rem;
            transition: background 0.2s, color 0.2s;
        }
        .github-btn {
            background: #24292e;
            color: #fff;
        }
        .github-btn:hover {
            background: #444d56;
        }
        .google-btn {
            background: #fff;
            color: #4285f4;
            border: 1.5px solid #4285f4;
        }
        .google-btn:hover {
            background: #e3eafc;
        }
        .oauth-btn svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        body.dark-mode .google-btn {
            background: #23272f;
            color: #4dabf7;
            border-color: #4dabf7;
        }
        body.dark-mode .google-btn:hover {
            background: #2d3748;
        }
        body.dark-mode .github-btn {
            background: #1a1b1e;
        }
        body.dark-mode .github-btn:hover {
            background: #2d3748;
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
            <div class="logo">✈️ Egypto Airlines</div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.html" class="nav-link">About</a>
                <a href="contact.html" class="nav-link">Contact</a>
            </div>
            <div class="mode-switch">
                <input type="checkbox" id="mode-toggle" />
                <label for="mode-toggle" class="toggle-label">
                    <span class="sun">☀️</span>
                    <span class="moon">🌙</span>
                </label>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Create Your Account</h2>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo $username; ?>" required>
                <?php if(!empty($username_err)): ?>
                    <span class="error-message"><?php echo $username_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo $email; ?>" required>
                <?php if(!empty($email_err)): ?>
                    <span class="error-message"><?php echo $email_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <?php if(!empty($password_err)): ?>
                    <span class="error-message"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <?php if(!empty($confirm_password_err)): ?>
                    <span class="error-message"><?php echo $confirm_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </div>
            <div class="oauth-buttons">
                <a href="oauth/github.php" class="oauth-btn github-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" style="margin-right: 10px; vertical-align: middle;">
                        <path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Register with GitHub
                </a>
                <a href="<?php echo GOOGLE_AUTH_URL . '?' . http_build_query([
                    'client_id' => GOOGLE_CLIENT_ID,
                    'redirect_uri' => GOOGLE_REDIRECT_URI,
                    'response_type' => 'code',
                    'scope' => 'email profile',
                    'access_type' => 'online'
                ]); ?>" class="oauth-btn google-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" style="margin-right: 10px; vertical-align: middle;">
                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                        <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                        <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                        <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                    </svg>
                    Register with Google
                </a>
            </div>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
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