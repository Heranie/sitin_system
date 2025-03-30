<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['announcement_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if(!empty($title) && !empty($content)) {
        $currentDate = date('Y-m-d');
        $updateQuery = "UPDATE announcements SET title = ?, content = ?, date = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssi", $title, $content, $currentDate, $id);
        
        if($stmt->execute()) {
            $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-green-100/80 text-green-800 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i> Announcement updated successfully!
            </div>';
        } else {
            $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-red-100/80 text-red-800 rounded-lg">
                <i class="fas fa-times-circle mr-2"></i> Error updating announcement: ' . $conn->error . '
            </div>';
        }
    } else {
        $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-yellow-100/80 text-yellow-800 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> Title and content are required.
        </div>';
    }
}

header("Location: admin_dashboard.php");
exit();
