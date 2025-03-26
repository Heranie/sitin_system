<?php
include 'connect.php';

// Check if admin table exists, if not create it
$tableExists = false;
$checkTable = "SHOW TABLES LIKE 'admins'";
$tableResult = $conn->query($checkTable);
if($tableResult && $tableResult->num_rows > 0) {
    $tableExists = true;
} else {
    // Create admin table
    $createTable = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(32) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if($conn->query($createTable) === TRUE) {
        $tableExists = true;
        echo "Admin table created successfully.<br>";
    } else {
        die("Error creating admin table: " . $conn->error);
    }
}

// Check if admin already exists
$checkAdmin = "SELECT COUNT(*) as count FROM admins WHERE email = 'admin@example.com'";
$adminResult = $conn->query($checkAdmin);
$adminCount = $adminResult->fetch_assoc();

if($adminCount['count'] == 0) {
    // Insert default admin
    $adminPassword = md5('admin123');
    $insertAdmin = "INSERT INTO admins (email, password) VALUES ('admin@example.com', '$adminPassword')";
    
    if($conn->query($insertAdmin) === TRUE) {
        echo "Default admin account created successfully.<br>";
        echo "Email: admin@example.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin account: " . $conn->error;
    }
} else {
    echo "Admin account already exists.<br>";
}

echo "<br><a href='index.php'>Go to login page</a>";
?>
