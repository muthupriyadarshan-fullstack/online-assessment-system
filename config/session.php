<?php
// Session management
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect to admin dashboard if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: user_dashboard.php');
        exit();
    }
}

// Redirect to user dashboard if admin tries to access user pages
function requireUser() {
    requireLogin();
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $db;
    return $db->fetchOne(
        "SELECT * FROM users WHERE id = ?", 
        [$_SESSION['user_id']]
    );
}

// Logout function
function logout() {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
