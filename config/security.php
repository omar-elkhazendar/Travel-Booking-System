<?php
/**
 * Security helper functions to prevent SQL injection and XSS attacks
 */

/**
 * Sanitize input data to prevent XSS attacks
 * @param string $data The input data to sanitize
 * @return string The sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 * @param string $email The email to validate
 * @return bool True if email is valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password The password to validate
 * @return bool True if password meets requirements, false otherwise
 */
function validate_password_strength($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[^A-Za-z0-9]/', $password);
}

/**
 * Generate CSRF token
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token The token to verify
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set secure session parameters
 */
function secure_session() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

/**
 * Set security headers
 */
function set_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' data:;');
}

/**
 * Log security events
 * @param string $event The security event to log
 * @param string $details Additional details about the event
 */
function log_security_event($event, $details = '') {
    $log_file = __DIR__ . '/../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'guest';
    
    $log_entry = sprintf(
        "[%s] [%s] [%s] [%s] %s\n",
        $timestamp,
        $ip,
        $user,
        $event,
        $details
    );
    
    error_log($log_entry, 3, $log_file);
} 