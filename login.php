<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/google_config.php';
require_once 'config/security.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    secure_session();
    session_start();
}

// Set security headers
set_security_headers();

$login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        log_security_event('CSRF_ATTEMPT', 'Invalid CSRF token');
        $login_err = "Invalid request. Please try again.";
    } else {
        // Check if email and password are set
        if (isset($_POST['email']) && isset($_POST['password'])) {
            $email = sanitize_input(trim($_POST['email']));
            $password = trim($_POST['password']); // Don't sanitize password as it will be hashed
            
            // Validate email format
            if (!validate_email($email)) {
                log_security_event('INVALID_EMAIL', $email);
                $login_err = "Invalid email format.";
            } else {
                // Ensure database connection is active
                $conn = ensureConnection();
                
                $sql = "SELECT id, username, password, is_admin FROM users WHERE email = ?";
                
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_store_result($stmt);
                        
                        if (mysqli_stmt_num_rows($stmt) == 1) {
                            mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $is_admin);
                            if (mysqli_stmt_fetch($stmt)) {
                                if (password_verify($password, $hashed_password)) {
                                    // Regenerate session ID to prevent session fixation
                                    session_regenerate_id(true);
                                    
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $username;
                                    $_SESSION["is_admin"] = (bool)$is_admin;
                                    
                                    if ($is_admin) {
                                        $_SESSION["admin_loggedin"] = true;
                                        log_security_event('ADMIN_LOGIN_SUCCESS', $email);
                                        header("Location: admin.php");
                                    } else {
                                        log_security_event('USER_LOGIN_SUCCESS', $email);
                                        header("Location: destination_dashboard.php");
                                    }
                                    exit();
                                } else {
                                    log_security_event('LOGIN_FAILED', 'Invalid password for ' . $email);
                                    $login_err = "Invalid email or password.";
                                }
                            }
                        } else {
                            // If not found in users, try admins table
                            mysqli_stmt_close($stmt);
                            
                            // Ensure connection is still active before second query
                            $conn = ensureConnection();
                            
                            $sql_admin = "SELECT id, username, password FROM admins WHERE email = ?";
                            if ($stmt_admin = mysqli_prepare($conn, $sql_admin)) {
                                mysqli_stmt_bind_param($stmt_admin, "s", $email);
                                
                                if (mysqli_stmt_execute($stmt_admin)) {
                                    mysqli_stmt_store_result($stmt_admin);
                                    
                                    if (mysqli_stmt_num_rows($stmt_admin) == 1) {
                                        mysqli_stmt_bind_result($stmt_admin, $id, $username, $hashed_password);
                                        if (mysqli_stmt_fetch($stmt_admin)) {
                                            if (password_verify($password, $hashed_password)) {
                                                // Regenerate session ID to prevent session fixation
                                                session_regenerate_id(true);
                                                
                                                $_SESSION["loggedin"] = true;
                                                $_SESSION["id"] = $id;
                                                $_SESSION["username"] = $username;
                                                $_SESSION["is_admin"] = true;
                                                $_SESSION["admin_loggedin"] = true;
                                                
                                                log_security_event('ADMIN_LOGIN_SUCCESS', $email);
                                                header("Location: admin.php");
                                                exit();
                                            } else {
                                                log_security_event('LOGIN_FAILED', 'Invalid admin password for ' . $email);
                                                $login_err = "Invalid email or password.";
                                            }
                                        }
                                    } else {
                                        log_security_event('LOGIN_FAILED', 'User not found: ' . $email);
                                        $login_err = "Invalid email or password.";
                                    }
                                } else {
                                    log_security_event('DB_ERROR', mysqli_error($conn));
                                    $login_err = "Oops! Something went wrong. Please try again later.";
                                }
                                mysqli_stmt_close($stmt_admin);
                            } else {
                                log_security_event('DB_ERROR', mysqli_error($conn));
                                $login_err = "Oops! Something went wrong. Please try again later.";
                            }
                        }
                    } else {
                        log_security_event('DB_ERROR', mysqli_error($conn));
                        $login_err = "Oops! Something went wrong. Please try again later.";
                    }
                } else {
                    log_security_event('DB_ERROR', mysqli_error($conn));
                    $login_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        } else {
            $login_err = "Please enter both email and password.";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Booking System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100vw;
            min-height: 100vh;
        }
        .form-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .form-logo .logo-text {
            font-family: 'Montserrat', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #3498db;
            letter-spacing: 1px;
            margin-bottom: 0.2rem;
        }
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
            font-size: 1.7rem;
            font-family: 'Montserrat', Arial, sans-serif;
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
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 1.5px solid #d0d7e2;
            border-radius: 8px;
            font-size: 1.08rem;
            transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
            background: #f8fafc;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            background: #e3eafc;
            box-shadow: 0 0 0 2px #74ebd5;
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, #3498db 0%, #217dbb 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.10);
        }
        .btn:hover {
            background: linear-gradient(90deg, #217dbb 0%, #3498db 100%);
            box-shadow: 0 4px 16px rgba(44, 62, 80, 0.13);
        }
        .error-message {
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
        .links, .register-link {
            margin-top: 1.5rem;
            text-align: center;
        }
        .links a, .register-link a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
        }
        .links a:hover, .register-link a:hover {
            color: #217dbb;
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
        .admin-link {
            margin-top: 1rem;
            display: inline-block;
            background: #f8fafc;
            color: #2c3e50;
            border: 1px solid #2c3e50;
            border-radius: 6px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .admin-link:hover {
            background: #3498db;
            color: #fff;
            border-color: #3498db;
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
        /* Dark mode styles */
        body.dark-mode {
            background: linear-gradient(120deg, #1a1b1e 0%, #2d2f34 100%);
            color: #e4e6eb;
        }
        body.dark-mode .navbar {
            background: #23272f;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.25);
        }
        body.dark-mode .logo {
            color: #4dabf7;
            text-shadow: 0 2px 8px #1118;
        }
        body.dark-mode .nav-link {
            color: #e4e6eb;
            text-shadow: 0 1px 4px #1118;
        }
        body.dark-mode .nav-link:hover {
            color: #4dabf7;
            background: #23272f;
        }
        .dark-mode .container {
            background: #23242a;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.35);
        }
        .dark-mode h2 {
            color: #e4e6eb;
        }
        .dark-mode .form-group label {
            color: #e4e6eb;
        }
        .dark-mode .form-group input {
            background: #1a1b1e;
            border-color: #3d3f44;
            color: #e4e6eb;
        }
        .dark-mode .form-group input:focus {
            background: #23272f;
            border-color: #4dabf7;
            box-shadow: 0 0 0 2px #4dabf7;
        }
        .dark-mode .btn {
            background: linear-gradient(90deg, #4dabf7 0%, #339af0 100%);
        }
        .dark-mode .btn:hover {
            background: linear-gradient(90deg, #339af0 0%, #228be6 100%);
        }
        .dark-mode .error-message {
            background: #3d3f44;
            color: #fa5252;
            border: 1.5px solid #fa5252;
        }
        .dark-mode .links a, .dark-mode .register-link a {
            color: #4dabf7;
        }
        .dark-mode .links a:hover, .dark-mode .register-link a:hover {
            color: #74c0fc;
        }
        .dark-mode .admin-link {
            background: #23272f;
            color: #4dabf7;
            border-color: #4dabf7;
        }
        .dark-mode .admin-link:hover {
            background: #4dabf7;
            color: #23272f;
            border-color: #4dabf7;
        }
        @media (max-width: 600px) {
            .container {
                padding: 1.2rem 0.5rem;
                margin: 6.5rem 0.5rem 1rem 0.5rem;
                max-width: 98vw;
            }
            .navbar-content {
                padding: 0 0.5rem;
            }
        }
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
        body.dark-mode .navbar {
            background: #23272f;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.25);
        }
        body.dark-mode .logo, body.dark-mode .nav-link {
            color: #fff;
        }
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
        body.dark-mode .container {
            background: #23242a;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.35);
        }
        @media (max-width: 600px) {
            .container {
                padding: 1.2rem 0.5rem;
                margin: 6.5rem 0.5rem 1rem 0.5rem;
                max-width: 98vw;
            }
            .navbar-content {
                padding: 0 0.5rem;
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
            <div class="logo">✈️ Egypto Airlines</div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.html" class="nav-link">About</a>
                <a href="contact.html" class="nav-link">Contact</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h2>Login to Your Account</h2>
            
            <?php if(!empty($login_err)): ?>
                <div class="error-message"><?php echo htmlspecialchars($login_err); ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required 
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required
                           minlength="8"
                           title="Password must be at least 8 characters long">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>
            </form>
            
            <div class="oauth-buttons">
                <a href="oauth/github.php" class="oauth-btn github-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" style="margin-right: 10px; vertical-align: middle;">
                        <path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Login with GitHub
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
                    Login with Google
                </a>
            </div>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
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