<?php
include 'connect.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_POST['username']) && isset($_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']);
    
    // Special case for admin login
    if($username == 'admin' && $password == md5('admin123')) {
        // Create session for admin
        session_start();
        $_SESSION['username'] = 'admin';
        $_SESSION['user_type'] = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Check if it's a regular user
    $sql = "SELECT * FROM users WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result && $result->num_rows > 0){
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['username'] = $row['username'];
        $_SESSION['idNo'] = $row['idNo'];
        $_SESSION['user_type'] = 'user';
        header("Location: dashboard.php");
        exit();
    } else {
        // Login failed
        header("Location: index.php?error=1");
        exit();
    }
} else {
    // Missing username or password
    header("Location: index.php?error=2");
    exit();
}
?>
