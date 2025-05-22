<?php
// Temporary debug code - Remove after testing
error_log("Current redirect URI: " . GOOGLE_REDIRECT_URI);
error_log("Full URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

session_start();
require_once '../config/database.php';
require_once '../config/google_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to log errors
function logError($message) {
    error_log("Google OAuth Error: " . $message);
}

try {
    if (!isset($_GET['code'])) {
        throw new Exception("No authorization code received from Google");
    }

    // Exchange authorization code for access token
    $token_data = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    // Initialize cURL session for token request
    $ch = curl_init(GOOGLE_TOKEN_URL);
    if ($ch === false) {
        throw new Exception("Failed to initialize cURL for token request");
    }

    // Set cURL options for token request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    // Execute token request
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception("cURL error during token request: " . curl_error($ch));
    }
    curl_close($ch);

    // Decode token response
    $token = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode token response: " . json_last_error_msg());
    }

    if (!isset($token['access_token'])) {
        throw new Exception("No access token in response: " . print_r($token, true));
    }

    // Get user info using access token
    $ch = curl_init(GOOGLE_USERINFO_URL);
    if ($ch === false) {
        throw new Exception("Failed to initialize cURL for user info request");
    }

    // Set cURL options for user info request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token['access_token']
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Execute user info request
    $user_info_response = curl_exec($ch);
    if ($user_info_response === false) {
        throw new Exception("cURL error during user info request: " . curl_error($ch));
    }
    curl_close($ch);

    // Decode user info response
    $user_info = json_decode($user_info_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode user info response: " . json_last_error_msg());
    }

    if (!isset($user_info['email'])) {
        throw new Exception("No email in user info response: " . print_r($user_info, true));
    }

    // Check if user exists
    $email = $user_info['email'];
    $sql = "SELECT id, username, is_admin FROM users WHERE email = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // User exists, log them in
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user['id'];
            $_SESSION["username"] = $user['username'];
            $_SESSION["is_admin"] = $user['is_admin'];
            
            if ($user['is_admin']) {
                header("Location: ../admin.php");
            } else {
                header("Location: ../destination_dashboard.php");
            }
            exit();
        } else {
            // Create new user
            $username = explode('@', $email)[0]; // Use part before @ as username
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // Generate random password
            
            $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);
                if (mysqli_stmt_execute($stmt)) {
                    $user_id = mysqli_insert_id($conn);
                    
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user_id;
                    $_SESSION["username"] = $username;
                    $_SESSION["is_admin"] = 0;
                    
                    header("Location: ../destination_dashboard.php");
                    exit();
                } else {
                    throw new Exception("Failed to create new user: " . mysqli_error($conn));
                }
            } else {
                throw new Exception("Failed to prepare insert statement: " . mysqli_error($conn));
            }
        }
    } else {
        throw new Exception("Failed to prepare select statement: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    logError($e->getMessage());
    $_SESSION['error'] = "An error occurred during Google authentication. Please try again.";
    header("Location: ../login.php");
    exit();
}

// If we get here, something went wrong
$_SESSION['error'] = "An unexpected error occurred. Please try again.";
header("Location: ../login.php");
exit();
?> 