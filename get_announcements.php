<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include 'connect.php';

// Get all announcements
$query = "SELECT * FROM announcements ORDER BY date DESC";
$result = $conn->query($query);

$announcements = [];
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format the date
        $row['date'] = date('M j, Y', strtotime($row['date']));
        
        // Sanitize data for JSON output
        $row['title'] = htmlspecialchars($row['title']);
        $row['content'] = htmlspecialchars($row['content']);
        $row['created_by'] = htmlspecialchars($row['created_by']);
        
        $announcements[] = $row;
    }
}

// Return as JSON
header('Content-Type: application/json');
echo json_encode($announcements);
?>