<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...<br>";

try {
    require_once 'config/database.php';
    $db = new Database();
    echo "Database connection successful!<br>";
    
    // Test a simple query
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM users");
    echo "User count: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?>

