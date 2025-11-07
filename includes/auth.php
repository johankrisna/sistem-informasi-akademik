<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Session timeout (30 menit)
const SESSION_TIMEOUT = 1800;

function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['login_time'] = time();
    
    return true;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMahasiswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotMahasiswa() {
    redirectIfNotLoggedIn();
    if (!isMahasiswa()) {
        header("Location: ../login.php");
        exit();
    }
}

// CSRF token functions (untuk form yang sensitive)
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>