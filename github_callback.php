<?php
require_once 'config.php';
require_once 'config/github_config.php';
session_start();

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for access token
    $ch = curl_init(GITHUB_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => GITHUB_REDIRECT_URI
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        // Get user data
        $ch = curl_init(GITHUB_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $data['access_token'],
            'User-Agent: PHP GitHub OAuth'
        ]);
        
        $user_data = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        // Get user email
        $ch = curl_init(GITHUB_EMAIL_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $data['access_token'],
            'User-Agent: PHP GitHub OAuth'
        ]);
        
        $emails = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $email = '';
        foreach ($emails as $email_data) {
            if ($email_data['primary']) {
                $email = $email_data['email'];
                break;
            }
        }
        
        // Check if user exists
        $sql = "SELECT * FROM users WHERE github_id = ? OR email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_data['id'], $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // User exists, update GitHub data
                $sql = "UPDATE users SET github_id = ?, github_username = ?, github_avatar = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issi", $user_data['id'], $user_data['login'], $user_data['avatar_url'], $row['id']);
                    mysqli_stmt_execute($stmt);
                }
            } else {
                // Create new user
                $sql = "INSERT INTO users (username, email, github_id, github_username, github_avatar, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssiss", $user_data['login'], $email, $user_data['id'], $user_data['login'], $user_data['avatar_url']);
                    mysqli_stmt_execute($stmt);
                    $row['id'] = mysqli_insert_id($conn);
                }
            }
            
            // Set session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $row['id'];
            $_SESSION["username"] = $user_data['login'];
            $_SESSION["email"] = $email;
            $_SESSION["github_avatar"] = $user_data['avatar_url'];
            
            header("location: destination_dashboard.php");
            exit;
        }
    }
}

// If we get here, something went wrong
header("location: login.php?error=github");
exit;
?> 