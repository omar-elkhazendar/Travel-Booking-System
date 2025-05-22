<?php
require_once '../config/github_config.php';

$githubAuthUrl = GITHUB_AUTH_URL . '?client_id=' . GITHUB_CLIENT_ID . '&redirect_uri=' . urlencode(GITHUB_REDIRECT_URI) . '&scope=user:email';
header('Location: ' . $githubAuthUrl);
exit; 