<?php
include 'connect.php';

// Read the SQL file
$sql = file_get_contents('admin_table.sql');

// Execute the SQL
if ($conn->multi_query($sql)) {
    echo "Admin table created successfully with default admin account!";
    echo "<br><br>Default admin credentials:";
    echo "<br>Username: admin";
    echo "<br>Password: admin123";
    echo "<br><br><a href='index.php'>Go to login</a>";
} else {
    echo "Error creating admin table: " . $conn->error;
}
?>
