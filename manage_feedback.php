<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin'){
    header("Location: index.php");
    exit();
}

include 'connect.php';

if($conn->query($createTable) !== TRUE) {
    $error = "Error creating feedback table: " . $conn->error;
}

// Handle form submission for responding to feedback
if(isset($_POST['respond_feedback'])) {
    $feedbackId = (int)$_POST['feedback_id'];
    $response = $conn->real_escape_string($_POST['response']);
    $date = date('Y-m-d H:i:s');
    
    // Update the feedback with admin response
    $sql = "UPDATE feedback SET admin_response = ?, date_responded = ?, status = 'responded' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $response, $date, $feedbackId);
    
    if($stmt->execute()) {
        $success = "Response submitted successfully!";
    } else {
        $error = "Error submitting response: " . $conn->error;
    }
}

// Mark feedback as read
if(isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $feedbackId = (int)$_GET['mark_read'];
    $sql = "UPDATE feedback SET status = 'read' WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $feedbackId);
    $stmt->execute();
}

// Get all feedback
$sql = "SELECT * FROM feedback ORDER BY date_submitted DESC";
$result = $conn->query($sql);

// Count unread feedback
$unreadSql = "SELECT COUNT(*) as unread_count FROM feedback WHERE status = 'pending'";
$unreadResult = $conn->query($unreadSql);
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Feedback</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            background-attachment: fixed;
            min-height: 100vh;
            width: 100%;
            color: #333;
            overflow-x: hidden;
        }
        
        /* Glassmorphism styles */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .header {
            background: linear-gradient(to right, rgba(178, 166, 204, 0.7), rgba(217, 230, 255, 0.7));
            color: #000;
            padding: 10px;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            font-family: 'Roboto', sans-serif;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 70px;
            transition: all 0.3s;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        /* Admin profile in sidebar */
        .admin-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .admin-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
        }
        
        .admin-profile .admin-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }
        
        .admin-profile .admin-title {
            font-size: 0.9em;
            color: #555;
            margin-top: 5px;
        }
        
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        
        .sidebar li {
            margin-bottom: 15px;
        }
        
        .sidebar a {
            color: #333;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateX(5px);
        }
        
        .sidebar i {
            margin-right: 10px;
        }
        
        .content {
            margin-left: 250px;
            padding: 90px 30px 30px;
            width: calc(100% - 250px);
            min-height: 100vh;
            box-sizing: border-box;
        }
        
        .feedback-item {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .feedback-item.unread {
            border-left: 4px solid #ff9800;
        }
        
        .feedback-item h3 {
            margin-top: 0;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .feedback-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }
        
        .feedback-message {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 5px;
        }
        
        .feedback-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.3);
            color: #ff8f00;
        }
        
        .status-read {
            background-color: rgba(33, 150, 243, 0.3);
            color: #1565c0;
        }
        
        .status-responded {
            background-color: rgba(76, 175, 80, 0.3);
            color: #2e7d32;
        }
        
        .response-form {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 5px;
        }
        
        .response-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(178, 166, 204, 0.5);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            height: 100px;
            resize: vertical;
            margin-bottom: 10px;
        }
        
        .admin-response {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 5px;
            border-left: 3px solid rgba(178, 166, 204, 0.8);
        }
        
        .btn {
            background: linear-gradient(to right, rgba(178, 166, 204, 0.8), rgba(217, 230, 255, 0.8));
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(to right, rgba(178, 166, 204, 1), rgba(217, 230, 255, 1));
            transform: translateY(-2px);
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.8em;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.3);
            color: #2e7d32;
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.3);
            color: #c62828;
            border: 1px solid rgba(244, 67, 54, 0.5);
        }
        
        .badge {
            display: inline-block;
            background-color: #ff5722;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        
        .filter-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 8px 15px;
            margin-right: 10px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: rgba(178, 166, 204, 0.4);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 15px 5px;
            }
            
            .sidebar span {
                display: none;
            }
            
            .content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manage Feedback</h1>
    </div>
    <div class="sidebar">
        <div class="admin-profile">
            <img src="images/admin_icon.jpg" alt="Admin Profile">
            <div class="admin-name"><?php echo $_SESSION['username']; ?></div>
            <div class="admin-title">Administrator</div>
        </div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="manage_announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
            <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
            <li><a href="manage_feedback.php"><i class="fas fa-comment"></i> <span>Feedback</span>
                <?php if($unreadCount > 0): ?>
                <span class="badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>
    <div class="content">
        <h2>Student Feedback</h2>
        
        <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-container">
            <div class="filter-btn active" data-filter="all">All</div>
            <div class="filter-btn" data-filter="pending">Pending</div>
            <div class="filter-btn" data-filter="read">Read</div>
            <div class="filter-btn" data-filter="responded">Responded</div>
        </div>
        
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="feedback-item <?php echo $row['status'] == 'pending' ? 'unread' : ''; ?>" data-status="<?php echo $row['status']; ?>">
                    <h3>
                        <?php echo htmlspecialchars($row['subject']); ?>
                        <span class="feedback-status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </h3>
                    <div class="feedback-meta">
                        <div>
                            <strong>From:</strong> <?php echo htmlspecialchars($row['username']); ?> 
                            (ID: <?php echo htmlspecialchars($row['user_id']); ?>)
                        </div>
                        <div>
                            <strong>Submitted:</strong> <?php echo date('F j, Y, g:i a', strtotime($row['date_submitted'])); ?>
                        </div>
                    </div>
                    <div class="feedback-message">
                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                    </div>
                    
                    <?php if($row['status'] == 'pending'): ?>
                        <a href="?mark_read=<?php echo $row['id']; ?>" class="btn btn-small">Mark as Read</a>
                    <?php endif; ?>
                    
                    <?php if($row['status'] != 'responded'): ?>
                        <div class="response-form">
                            <form method="post" action="">
                                <input type="hidden" name="feedback_id" value="<?php echo $row['id']; ?>">
                                <textarea name="response" placeholder="Type your response here..." required></textarea>
                                <button type="submit" name="respond_feedback" class="btn">Send Response</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="admin-response">
                            <strong>Your Response:</strong>
                            <p><?php echo nl2br(htmlspecialchars($row['admin_response'])); ?></p>
                            <div class="feedback-date">Responded on: <?php echo date('F j, Y, g:i a', strtotime($row['date_responded'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No feedback received yet.</p>
        <?php endif; ?>
    </div>
    
    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const feedbackItems = document.querySelectorAll('.feedback-item');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
                    feedbackItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-status') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
