<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

// Get user type for conditional display
$userType = $_SESSION['user_type'];

include 'connect.php';

// Create feedback table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'read', 'responded') DEFAULT 'pending',
    date_submitted DATETIME NOT NULL,
    admin_response TEXT,
    date_responded DATETIME
)";

if($conn->query($createTable) !== TRUE) {
    $error = "Error creating feedback table: " . $conn->error;
}

// Handle form submission
if(isset($_POST['submit_feedback'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);
    $date = date('Y-m-d H:i:s');
    $username = $_SESSION['username'];
    $userId = isset($_SESSION['idNo']) ? $_SESSION['idNo'] : '';
    
    // Insert the feedback
    $sql = "INSERT INTO feedback (user_id, username, subject, message, date_submitted) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $userId, $username, $subject, $message, $date);
    
    if($stmt->execute()) {
        $success = "Your feedback has been submitted successfully!";
    } else {
        $error = "Error submitting feedback: " . $conn->error;
    }
}

// Get user's previous feedback
$username = $_SESSION['username'];
$sql = "SELECT * FROM feedback WHERE username = ? ORDER BY date_submitted DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            background-image: url('img/l.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 120vh;
            width: 100vw;
            color: #333;
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
            background: linear-gradient(to bottom, rgba(217, 230, 255, 0.7), rgba(178, 166, 204, 0.7));
            width: 250px;
            padding: 15px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 70px;
            box-sizing: border-box;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.18);
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
            padding: 80px 20px 20px 20px;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }
        
        .feedback-form {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .feedback-form h2 {
            margin-top: 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(178, 166, 204, 0.5);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
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
        
        .feedback-list {
            margin-top: 30px;
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
        
        .feedback-item h3 {
            margin-top: 0;
            color: #333;
        }
        
        .feedback-date {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .feedback-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
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
        
        .admin-response {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 5px;
            border-left: 3px solid rgba(178, 166, 204, 0.8);
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
        <h1>Feedback</h1>
    </div>
    <div class="sidebar">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
            <li><a href="sit_in_rules.php"><i class="fas fa-book"></i> <span>Sit-In Rules</span></a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> <span>History</span></a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-alt"></i> <span>Reservation</span></a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> <span>Feedback</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>
    <div class="content">
        <h2>Submit Feedback</h2>
        
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
        
        <div class="feedback-form">
            <form method="post" action="">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn">Submit Feedback</button>
            </form>
        </div>
        
        <div class="feedback-list">
            <h2>Your Previous Feedback</h2>
            
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="feedback-item">
                        <h3>
                            <?php echo htmlspecialchars($row['subject']); ?>
                            <span class="feedback-status status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </h3>
                        <div class="feedback-date">Submitted on: <?php echo date('F j, Y, g:i a', strtotime($row['date_submitted'])); ?></div>
                        <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        
                        <?php if($row['status'] == 'responded' && !empty($row['admin_response'])): ?>
                            <div class="admin-response">
                                <strong>Admin Response:</strong>
                                <p><?php echo nl2br(htmlspecialchars($row['admin_response'])); ?></p>
                                <div class="feedback-date">Responded on: <?php echo date('F j, Y, g:i a', strtotime($row['date_responded'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You haven't submitted any feedback yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
