<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Process announcement operations FIRST (before any output)
// Process new announcement submission
if(isset($_POST['add_announcement'])) {
    $title = trim($_POST['announcement_title']);
    $content = trim($_POST['announcement_content']);
    $date = date('Y-m-d');
    $created_by = $_SESSION['username'];
    
    if(!empty($title) && !empty($content)) {
        $insertQuery = "INSERT INTO announcements (title, content, date, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssss", $title, $content, $date, $created_by);
        
        if($stmt->execute()) {
            $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-green-100/80 text-green-800 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i> Announcement posted successfully!
            </div>';
        } else {
            $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-red-100/80 text-red-800 rounded-lg">
                <i class="fas fa-times-circle mr-2"></i> Error posting announcement: ' . $conn->error . '
            </div>';
        }
    } else {
        $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-yellow-100/80 text-yellow-800 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> Please fill in all fields.
        </div>';
    }
    
    // Redirect back to the same page to prevent form resubmission
    header("Location: admin_dashboard.php");
    exit();
}

// Process announcement deletion
if(isset($_POST['delete_announcement'])) {
    $announcementId = $_POST['announcement_id'];
    
    $deleteQuery = "DELETE FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $announcementId);
    
    if($stmt->execute()) {
        $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-green-100/80 text-green-800 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i> Announcement deleted successfully!
        </div>';
    } else {
        $_SESSION['announcement_message'] = '<div class="p-3 mb-4 bg-red-100/80 text-red-800 rounded-lg">
            <i class="fas fa-times-circle mr-2"></i> Error deleting announcement: ' . $conn->error . '
        </div>';
    }
    
    // Redirect back to the same page to prevent form resubmission
    header("Location: admin_dashboard.php");
    exit();
}

// Get users count
$userQuery = "SELECT COUNT(*) as user_count FROM users";
$userResult = $conn->query($userQuery);
$userCount = $userResult->fetch_assoc()['user_count'];

// Get total announcements count
$announcementQuery = "SELECT COUNT(*) as announcement_count FROM announcements";
$announcementResult = $conn->query($announcementQuery);
$announcementCount = 0;
if ($announcementResult) {
    $announcementCount = $announcementResult->fetch_assoc()['announcement_count'];
}

// Get total feedback count
$feedbackQuery = "SELECT COUNT(*) as feedback_count FROM feedback";
$feedbackResult = $conn->query($feedbackQuery);
$feedbackCount = 0;
if ($feedbackResult) {
    $feedbackCount = $feedbackResult->fetch_assoc()['feedback_count'];
}

// Get today's active sit-ins count
$activeSitinQuery = "SELECT COUNT(*) as active_count FROM new_sitin WHERE status = 'active' AND time_out IS NULL";
$activeSitinResult = $conn->query($activeSitinQuery);
$activeSitinCount = 0;
if ($activeSitinResult) {
    $activeSitinCount = $activeSitinResult->fetch_assoc()['active_count'];
}

// Get total sit-ins for today
$todaySitinQuery = "SELECT COUNT(*) as today_count FROM new_sitin WHERE date = CURRENT_DATE()";
$todaySitinResult = $conn->query($todaySitinQuery);
$todaySitinCount = 0;
if ($todaySitinResult) {
    $todaySitinCount = $todaySitinResult->fetch_assoc()['today_count'];
}

// Get recent announcements
$recentAnnouncementsQuery = "SELECT * FROM announcements ORDER BY date DESC LIMIT 3";
$announcementsResult = $conn->query($recentAnnouncementsQuery);

// Get laboratory usage data
$labUsageQuery = "SELECT laboratory, COUNT(*) as count FROM new_sitin GROUP BY laboratory ORDER BY count DESC";
$labUsageResult = $conn->query($labUsageQuery);

// Get purpose distribution data
$purposeQuery = "SELECT purpose, COUNT(*) as count FROM new_sitin GROUP BY purpose ORDER BY count DESC";
$purposeResult = $conn->query($purposeQuery);

// Get daily usage data for the last 7 days
$dailyQuery = "SELECT DATE(date) as day, COUNT(*) as count 
            FROM new_sitin 
            WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(date) 
            ORDER BY day DESC";
$dailyResult = $conn->query($dailyQuery);

// Process data for charts
$labLabels = [];
$labCounts = [];
$labColors = ["#B8977E", "#C9B8A7", "#D6C7BA", "#E3D6CD", "#F0E5E0"];

$purposeLabels = [];
$purposeCounts = [];
$purposeColors = ["#B8977E", "#C9B8A7", "#D6C7BA", "#E3D6CD", "#F0E5E0", "#F7F2EF"];

if($labUsageResult && $labUsageResult->num_rows > 0) {
    $i = 0;
    while($row = $labUsageResult->fetch_assoc()) {
        $labLabels[] = 'Lab ' . $row['laboratory'];
        $labCounts[] = $row['count'];
        $i++;
    }
}

if($purposeResult && $purposeResult->num_rows > 0) {
    $i = 0;
    while($row = $purposeResult->fetch_assoc()) {
        $purposeLabels[] = $row['purpose'];
        $purposeCounts[] = $row['count'];
        $i++;
    }
}

$dailyLabels = [];
$dailyCounts = [];

if($dailyResult && $dailyResult->num_rows > 0) {
    while($row = $dailyResult->fetch_assoc()) {
        $dailyLabels[] = date('D', strtotime($row['day']));
        $dailyCounts[] = $row['count'];
    }
}

// Calculate some key metrics
$avgSessionQuery = "SELECT AVG(TIMESTAMPDIFF(MINUTE, CONCAT(date, ' ', time_in), CONCAT(date, ' ', time_out))) as avg_duration 
                  FROM new_sitin 
                  WHERE status = 'inactive' AND time_out IS NOT NULL";
$avgSessionResult = $conn->query($avgSessionQuery);
$avgSessionMinutes = 0;

if($avgSessionResult && $avgSessionResult->num_rows > 0) {
    $avgSessionMinutes = round($avgSessionResult->fetch_assoc()['avg_duration']);
}

$avgHours = floor($avgSessionMinutes / 60);
$avgMinutes = $avgSessionMinutes % 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/particles.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#ff9a9e',
                            DEFAULT: '#fad0c4',
                            dark: '#fbc2eb',
                        }
                    },
                    fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        /* Update body background with gradient */
        body {
            background: linear-gradient(135deg, #000000, #1a1a1a);
            margin: 0;
            min-height: 100vh;
            display: flex;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
            position: relative;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
        }

        .dash-card {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            border: 1px solid rgba(166, 124, 82, 0.2);
        }

        .dash-card:hover {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.6), rgba(60, 46, 38, 0.6));
            border: 1px solid rgba(166, 124, 82, 0.3);
        }

        .header {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(166, 124, 82, 0.2);
        }

        .sidebar {
            background: rgba(0, 0, 0, 0.8);
            border-right: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Update text colors */
        .text-gray-600 {
            color: rgba(247, 241, 236, 0.8) !important;
        }

        .text-gray-800 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Custom scrollbar for dark theme */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(166, 124, 82, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(166, 124, 82, 0.7);
        }

        /* Updated announcement button styles */
        #toggleAnnouncementForm {
            background: linear-gradient(135deg, #1D3B2A, #3C2E26) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
        }

        #toggleAnnouncementForm:hover {
            background: linear-gradient(135deg, #3C2E26, #1D3B2A) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
            transform: translateY(-1px);
        }

        /* Announcement form buttons */
        .announcement-btn {
            background: linear-gradient(135deg, #1D3B2A, #3C2E26) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
            transition: all 0.3s ease;
        }

        .announcement-btn:hover {
            background: linear-gradient(135deg, #3C2E26, #1D3B2A) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
            transform: translateY(-1px);
        }

        /* Cancel button style */
        .announcement-btn-cancel {
            background: rgba(60, 46, 38, 0.8) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
        }

        .announcement-btn-cancel:hover {
            background: rgba(60, 46, 38, 1) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
        }

        /* Icon container styles */
        .icon-container {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            color: #A67C52;
            border: 1px solid rgba(166, 124, 82, 0.2);
            transition: all 0.3s ease;
        }

        .icon-container:hover {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.6), rgba(60, 46, 38, 0.6));
            border-color: rgba(166, 124, 82, 0.4);
        }

        /* Icon colors */
        .icon-primary {
            color: #A67C52 !important;
        }

        /* Sidebar icon colors */
        .sidebar i {
            color: #A67C52;
            transition: all 0.3s ease;
        }

        .sidebar a:hover i {
            color: #5A6B4D;
        }

        /* Stats text colors */
        .stat-number {
            color: #ffffff !important;
            font-weight: 600;
        }

        .stat-label {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        /* Update announcement stat numbers and labels */
        .stats-value {
            color: #ffffff !important;
            font-weight: 600;
        }

        .stats-label {
            color: rgba(166, 124, 82, 0.8) !important;
            font-size: 0.875rem;
        }
        /* Add this style for detail links */
        .detail-link {
            color: rgb(180, 176, 213) !important;
            transition: all 0.3s ease;
        }

        .detail-link:hover {
            opacity: 0.8;
            transform: translateX(2px);
        }


        /* Update specific h4 classes */
        .glass h4,
        .dash-card h4,
        .modal h4 {
            color: rgb(180, 176, 213) !important;
        }

        /* Sidebar link colors */
        .sidebar ul li a span {
            color: #FFFFFF !important;
        }
        
        .sidebar ul li a.active span {
            color: #ffffff !important;
        }

        .sidebar .text-sm.opacity-75 {
            color: #FFFFFF !important;
        }

        .sidebar .font-semibold {
            color: #FFFFFF !important;
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 header z-50 border-b border-white/20 py-2 px-6">
        <h1 class="text-xl md:text-2xl font-bold text-center">Administrator Dashboard</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 sidebar border-r border-white/20 pt-16 z-40 overflow-y-auto no-print">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
            <img src="images/admin_icon.jpg" alt="Admin Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
            <div class="font-semibold text-center"><?php echo $_SESSION['username']; ?></div>
            <div class="text-sm opacity-75">Administrator</div>
        </div>
        
        <ul class="px-4">
            <li class="mb-1"><a href="admin_dashboard.php" class="flex items-center py-2 px-4 rounded bg-white/30 text-primary-dark font-semibold">
                <i class="fas fa-tachometer-alt w-6"></i> <span>Dashboard</span>
            </a></li>
            <li class="mb-1"><a href="manage_search.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-search w-6"></i> <span>Search</span>
            </a></li>
            <li class="mb-1"><a href="manage_currsitin.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-chair w-6"></i> <span>Current Sit-In</span>
            </a></li>
            <li class="mb-1"><a href="manage_history.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-history w-6"></i> <span>Sit-In Records</span>
            </a></li>
            <li class="mb-1"><a href="reports.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-chart-bar w-6"></i> <span>Reports</span>
            </a></li>
            <li class="mb-1"><a href="manage_users.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-users w-6"></i> <span>Manage Users</span>
            </a></li>
            <li class="mb-1"><a href="logout.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-sign-out-alt w-6"></i> <span>Log Out</span>
            </a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="ml-64 p-6 pt-20">

        <div class="glass p-5 rounded-lg dash-card mb-6">
            <div class="flex items-center">
                <div class="rounded-full icon-container p-4 mr-4">
                    <i class="fas fa-user-shield icon-primary text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Welcome back, <?php echo $_SESSION['username']; ?>!</h2>
                    <p class="text-sm text-gray-600">
                        <?php echo date('l, F j, Y'); ?> | Manage your sit-in lab system from this dashboard
                    </p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Active Sit-ins Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-4 mr-4">
                        <i class="fas fa-chair icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold stat-number"><?php echo $activeSitinCount; ?></p>
                        <p class="text-sm stat-label">Active Sit-ins</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/30">
                    <a href="manage_currsitin.php" class="detail-link flex items-center">
                        <span>View Details</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <!-- Today's Sit-ins Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-4 mr-4">
                        <i class="fas fa-calendar-day icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold stat-number"><?php echo $todaySitinCount; ?></p>
                        <p class="text-sm stat-label">Today's Sit-ins</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/30">
                    <a href="manage_historysitin.php" class="detail-link flex items-center">
                        <span>View Details</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <!-- Total Users Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-4 mr-4">
                        <i class="fas fa-users icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold stat-number"><?php echo $userCount; ?></p>
                        <p class="text-sm stat-label">Total Users</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/30">
                    <a href="manage_users.php" class="detail-link flex items-center">
                        <span>Manage Users</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <!-- Announcements Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-4 mr-4">
                        <i class="fas fa-bullhorn icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold stat-number"><?php echo $announcementCount; ?></p>
                        <p class="text-sm stat-label">Announcements</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/30">
                    <a href="manage_announcements.php" class="detail-link flex items-center">
                        <span>Manage Announcements</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions and Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Replace the Latest Activity section with this Analytics section -->
            <div class="glass p-6 rounded-lg">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-primary-dark mr-2"></i> Sit-in Analytics
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <!-- Laboratory Usage Distribution -->
                    <div class="bg-white/20 p-4 rounded-lg">
                        <h4 class="text-center font-semibold mb-3">Laboratory Usage</h4>
                        <div class="flex justify-center">
                            <div>
                                <canvas id="labChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                        
                        <?php if(count($labLabels) > 0): ?>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                <?php for($i = 0; $i < count($labLabels); $i++): ?>
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 inline-block mr-2 rounded-full" style="background-color: <?php echo $labColors[$i % count($labColors)]; ?>"></span>
                                        <span><?php echo $labLabels[$i]; ?> (<?php echo $labCounts[$i]; ?>)</span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-sm mt-3">No laboratory data available</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Purpose Distribution -->
                    <div class="bg-white/20 p-4 rounded-lg">
                        <h4 class="text-center font-semibold mb-3">Purpose Distribution</h4>
                        <div class="flex justify-center">
                            <div>
                                <canvas id="purposeChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                        
                        <?php if(count($purposeLabels) > 0): ?>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                <?php for($i = 0; $i < count($purposeLabels); $i++): ?>
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 inline-block mr-2 rounded-full" style="background-color: <?php echo $purposeColors[$i % count($purposeColors)]; ?>"></span>
                                        <span><?php echo $purposeLabels[$i]; ?> (<?php echo $purposeCounts[$i]; ?>)</span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-sm mt-3">No purpose data available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <!-- Weekly Trend -->
                    <div class="bg-white/20 p-4 rounded-lg col-span-2">
                        <h4 class="text-center font-semibold mb-3">Weekly Trend</h4>
                        <div class="flex justify-center h-[150px]">
                            <canvas id="weeklyChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Average Session Duration -->
                    <div class="bg-white/20 p-4 rounded-lg flex flex-col items-center justify-center">
                        <h4 class="text-center font-semibold mb-3">Average Session</h4>
                        <div class="relative w-32 h-32">
                            <canvas id="durationChart" width="150" height="150"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-2xl font-bold"><?php echo $avgHours; ?>h <?php echo $avgMinutes; ?>m</span>
                                <span class="text-xs text-gray-600">per session</span>
                            </div>
                        </div>
                        
                        <p class="text-sm mt-3 text-center">
                            Average time students spend in sit-in sessions
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="manage_historysitin.php" class="detail-link flex items-center justify-center">
                        <span>View Detailed Reports</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="glass p-6 rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold flex items-center">
                        <i class="fas fa-bullhorn text-primary-dark mr-2"></i> Announcements
                    </h3>
                    
                    <button id="toggleAnnouncementForm" class="py-1 px-3 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i> New
                    </button>
                </div>
                
                <!-- New Announcement Form (hidden by default) -->
                <div id="announcementForm" class="mb-6 p-4 bg-white/30 rounded-lg hidden">
                    <form method="POST" action="">
                        <h4 class="font-semibold mb-3 text-primary-dark">Post New Announcement</h4>
                        
                        <div class="mb-3">
                            <label for="announcement_title" class="block text-sm font-medium mb-1">Title</label>
                            <input type="text" id="announcement_title" name="announcement_title" 
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light"
                                required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="announcement_content" class="block text-sm font-medium mb-1">Content</label>
                            <textarea id="announcement_content" name="announcement_content" rows="4"
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light"
                                required></textarea>
                        </div>
                        
                        <div class="flex justify-end gap-2">
                            <button type="button" id="cancelAnnouncementBtn" 
                                    class="py-1 px-3 rounded-lg transition-colors announcement-btn-cancel">
                                Cancel
                            </button>
                            <button type="submit" name="add_announcement" 
                                    class="py-1 px-3 rounded-lg transition-colors announcement-btn">
                                Post Announcement
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Announcements List -->
                <?php
                // Get recent announcements
                if(!isset($announcementsResult)) {
                    $recentAnnouncementsQuery = "SELECT * FROM announcements ORDER BY date DESC LIMIT 3";
                    $announcementsResult = $conn->query($recentAnnouncementsQuery);
                }
                
                if($announcementsResult && $announcementsResult->num_rows > 0): 
                ?>
                    <div class="space-y-4">
                        <?php while($announcement = $announcementsResult->fetch_assoc()): ?>
                            <div class="p-4 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-primary-dark"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-600"><?php echo date('M j, Y', strtotime($announcement['date'])); ?></span>
                                        
                                        <!-- Edit button -->
                                        <button onclick="openEditModal(<?php echo $announcement['id']; ?>, '<?php echo addslashes($announcement['title']); ?>', '<?php echo addslashes($announcement['content']); ?>')" 
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Delete announcement button -->
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                            <button type="submit" name="delete_announcement" class="text-red-500 hover:text-red-700 transition-colors">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-sm mb-2 line-clamp-2">
                                    <?php 
                                    // Limit content to 120 characters with ellipsis
                                    $content = htmlspecialchars($announcement['content']);
                                    echo (strlen($content) > 120) ? substr($content, 0, 120) . '...' : $content; 
                                    ?>
                                </p>
                                <?php if(strlen($announcement['content']) > 120): ?>
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-primary-dark hover:text-primary transition-colors">Read more</summary>
                                        <p class="mt-2 text-gray-700"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    </details>
                                <?php endif; ?>
                                <div class="mt-2 text-xs text-gray-600">
                                    Posted by: <?php echo htmlspecialchars($announcement['created_by']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php 
                    // Get total announcement count to see if there are more than shown
                    $totalQuery = "SELECT COUNT(*) as total FROM announcements";
                    $totalResult = $conn->query($totalQuery);
                    $totalAnnouncements = $totalResult->fetch_assoc()['total'];
                    
                    if($totalAnnouncements > 3): 
                    ?>
                        <div class="mt-4 text-center">
                            <a href="#" id="viewMoreAnnouncements" class="detail-link flex items-center justify-center">
                                <span>View All Announcements</span>
                                <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center p-6">
                        <i class="fas fa-bullhorn text-gray-400 text-4xl mb-2"></i>
                        <p>No announcements found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div id="editModal" class="fixed inset-0 hidden items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative glass p-6 rounded-lg w-full max-w-md">
            <h3 class="text-xl font-bold mb-4 text-white/90">Edit Announcement</h3>
            <form action="edit_announcement.php" method="POST" id="editForm">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-white/90 mb-1">Title</label>
                    <input type="text" id="edit_title" name="title" 
                        class="w-full px-3 py-2 rounded-lg bg-white/20 border border-white/30 focus:outline-none focus:border-white/50"
                        required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-white/90 mb-1">Content</label>
                    <textarea id="edit_content" name="content" rows="4" 
                        class="w-full px-3 py-2 rounded-lg bg-white/20 border border-white/30 focus:outline-none focus:border-white/50"
                        required></textarea>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditModal()" 
                        class="px-4 py-2 rounded-lg bg-gray-500/50 hover:bg-gray-600/50 text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-blue-500/50 hover:bg-blue-600/50 text-white transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add this script at the end of your file, before </body> -->
    <script>
        // Toggle announcement form visibility
        document.getElementById('toggleAnnouncementForm').addEventListener('click', function() {
            const form = document.getElementById('announcementForm');
            form.classList.toggle('hidden');
        });
        
        // Handle cancel button
        document.getElementById('cancelAnnouncementBtn').addEventListener('click', function() {
            document.getElementById('announcementForm').classList.add('hidden');
            document.getElementById('announcement_title').value = '';
            document.getElementById('announcement_content').value = '';
        });
        
        // View all announcements functionality - modal popup
        document.getElementById('viewMoreAnnouncements')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create modal container
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" id="modalOverlay"></div>
                <div class="glass max-w-3xl w-full mx-4 p-6 rounded-lg relative z-10 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">All Announcements</h3>
                        <button id="closeModal" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    <div id="allAnnouncementsContainer" class="space-y-4">
                        <div class="text-center py-8">
                            <i class="fas fa-circle-notch fa-spin text-primary-dark text-2xl"></i>
                            <p class="mt-2">Loading announcements...</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close modal when clicking overlay or close button
            document.getElementById('modalOverlay').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
            document.getElementById('closeModal').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
            // Fetch all announcements
            fetch('get_announcements.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('allAnnouncementsContainer');
                    
                    if (data.length === 0) {
                        container.innerHTML = `
                            <div class="text-center p-6">
                                <i class="fas fa-bullhorn text-gray-400 text-4xl mb-2"></i>
                                <p>No announcements found</p>
                            </div>
                        `;
                        return;
                    }
                    
                    let html = '';
                    data.forEach(announcement => {
                        const content = announcement.content;
                        const isLong = content.length > 120;
                        
                        html += `
                            <div class="p-4 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-primary-dark">${announcement.title}</h4>
                                    <span class="text-xs text-gray-600">${announcement.date}</span>
                                </div>
                                <p class="text-sm mb-2 ${isLong ? 'line-clamp-2' : ''}">
                                    ${isLong ? content.substring(0, 120) + '...' : content}
                                </p>
                                ${isLong ? `
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-primary-dark hover:text-primary transition-colors">Read more</summary>
                                        <p class="mt-2 text-gray-700">${content.replace(/\n/g, '<br>')}</p>
                                    </details>
                                ` : ''}
                                <div class="mt-2 text-xs text-gray-600">
                                    Posted by: ${announcement.created_by}
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('allAnnouncementsContainer').innerHTML = `
                        <div class="text-center p-6">
                            <i class="fas fa-exclamation-circle text-red-500 text-4xl mb-2"></i>
                            <p>Error loading announcements</p>
                            <p class="text-sm text-gray-600 mt-1">${error.message}</p>
                        </div>
                    `;
                });
        });

        function openEditModal(id, title, content) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
            document.getElementById('edit_announcement_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>

    <!-- Add Chart.js library before your closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lab Usage Chart
            const labCtx = document.getElementById('labChart').getContext('2d');
            new Chart(labCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($labLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($labCounts); ?>,
                        backgroundColor: <?php echo json_encode($labColors); ?>,
                        borderWidth: 2,
                        borderColor: 'rgba(166, 124, 82, 0.3)',
                        hoverBorderColor: 'rgba(166, 124, 82, 0.5)',
                        hoverOffset: 6
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#B8977E',
                            bodyColor: 'rgba(184, 151, 126, 0.8)',
                            borderColor: '#A67C52',
                            borderWidth: 1
                        }
                    },
                    cutout: '70%',
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
            
            // Purpose Chart with updated colors
            const purposeCtx = document.getElementById('purposeChart').getContext('2d');
            new Chart(purposeCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($purposeLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($purposeCounts); ?>,
                        backgroundColor: <?php echo json_encode($purposeColors); ?>,
                        borderWidth: 2,
                        borderColor: 'rgba(166, 124, 82, 0.3)',
                        hoverBorderColor: 'rgba(166, 124, 82, 0.5)',
                        hoverOffset: 6
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#B8977E',
                            bodyColor: 'rgba(184, 151, 126, 0.8)',
                            borderColor: '#A67C52',
                            borderWidth: 1
                        }
                    },
                    cutout: '70%',
                    responsive: true,
                    maintainAspectRatio: true
                }
            });

            // Weekly Trend Chart with updated colors
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dailyLabels); ?>,
                    datasets: [{
                        label: 'Daily Sit-ins',
                        data: <?php echo json_encode($dailyCounts); ?>,
                        backgroundColor: 'rgba(166, 124, 82, 0.4)',
                        borderColor: '#A67C52',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(166, 124, 82, 0.6)'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(166, 124, 82, 0.1)',
                                borderColor: 'rgba(166, 124, 82, 0.2)'
                            },
                            ticks: {
                                color: 'rgba(166, 124, 82, 0.8)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: 'rgba(166, 124, 82, 0.8)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#B8977E',
                            bodyColor: 'rgba(184, 151, 126, 0.8)',
                            borderColor: '#A67C52',
                            borderWidth: 1
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Average Duration Chart with updated colors
            const durationCtx = document.getElementById('durationChart').getContext('2d');
            new Chart(durationCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Average Duration'],
                    datasets: [{
                        data: [<?php echo $avgSessionMinutes; ?>, 180 - <?php echo $avgSessionMinutes; ?>],
                        backgroundColor: [
                            '#A67C52',
                            'rgba(60, 46, 38, 0.2)'
                        ],
                        borderWidth: 0,
                        hoverOffset: 0,
                        circumference: 270,
                        rotation: 225
                    }]
                },
                options: {
                    cutout: '80%',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>