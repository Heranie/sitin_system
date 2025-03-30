<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Get filter values
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$labFilter = isset($_GET['lab_filter']) ? $_GET['lab_filter'] : '';
$purposeFilter = isset($_GET['purpose_filter']) ? $_GET['purpose_filter'] : '';
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'completed';

// Base query
$query = "SELECT ns.*, u.firstName, u.lastName, u.idNo 
          FROM new_sitin ns
          JOIN users u ON ns.user_id = u.id
          WHERE 1=1";

// Apply filters
if ($dateFilter) {
    $query .= " AND DATE(ns.date) = '$dateFilter'";
}

if ($labFilter) {
    $query .= " AND ns.laboratory = '$labFilter'";
}

if ($purposeFilter) {
    $query .= " AND ns.purpose LIKE '%$purposeFilter%'";
}

if ($statusFilter) {
    $query .= " AND ns.status = '$statusFilter'";
} else {
    $query .= " AND ns.status = 'completed'";
}

// Add order by
$query .= " ORDER BY ns.date DESC, ns.time_in DESC";

// Execute query
$result = $conn->query($query);

// Get unique laboratories for filter
$labQuery = "SELECT DISTINCT laboratory FROM new_sitin WHERE laboratory IS NOT NULL AND laboratory != ''";
$labResult = $conn->query($labQuery);
$labs = [];
if($labResult && $labResult->num_rows > 0) {
    while($row = $labResult->fetch_assoc()) {
        $labs[] = $row['laboratory'];
    }
}

// Get unique purposes for filter
$purposeQuery = "SELECT DISTINCT purpose FROM new_sitin WHERE purpose IS NOT NULL AND purpose != ''";
$purposeResult = $conn->query($purposeQuery);
$purposes = [];
if($purposeResult && $purposeResult->num_rows > 0) {
    while($row = $purposeResult->fetch_assoc()) {
        $purposes[] = $row['purpose'];
    }
}

// Process date range
$dateRangeStart = isset($_GET['date_range_start']) ? $_GET['date_range_start'] : '';
$dateRangeEnd = isset($_GET['date_range_end']) ? $_GET['date_range_end'] : '';

if ($dateRangeStart && $dateRangeEnd) {
    $query = "SELECT ns.*, u.firstName, u.lastName, u.idNo 
              FROM new_sitin ns
              JOIN users u ON ns.user_id = u.id
              WHERE ns.status = 'completed' 
              AND DATE(ns.date) >= '$dateRangeStart' 
              AND DATE(ns.date) <= '$dateRangeEnd'";
    
    if ($labFilter) {
        $query .= " AND ns.laboratory = '$labFilter'";
    }
    
    if ($purposeFilter) {
        $query .= " AND ns.purpose LIKE '%$purposeFilter%'";
    }
    
    $query .= " ORDER BY ns.date DESC, ns.time_in DESC";
    $result = $conn->query($query);
}

// Get statistics (count data only)
$statsQuery = "SELECT 
    COUNT(*) as total_sessions,
    COUNT(DISTINCT user_id) as unique_users
    FROM new_sitin 
    WHERE status = 'completed'";
    
if ($dateFilter) {
    $statsQuery .= " AND DATE(date) = '$dateFilter'";
} elseif ($dateRangeStart && $dateRangeEnd) {
    $statsQuery .= " AND DATE(date) >= '$dateRangeStart' AND DATE(date) <= '$dateRangeEnd'";
}

$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Calculate average duration properly
$avgDurationQuery = "SELECT time_in, time_out, date FROM new_sitin WHERE status = 'completed'";
if ($dateFilter) {
    $avgDurationQuery .= " AND DATE(date) = '$dateFilter'";
} elseif ($dateRangeStart && $dateRangeEnd) {
    $avgDurationQuery .= " AND DATE(date) >= '$dateRangeStart' AND DATE(date) <= '$dateRangeEnd'";
}

$avgDurationResult = $conn->query($avgDurationQuery);
$totalDuration = 0;
$sessionCount = 0;

while ($row = $avgDurationResult->fetch_assoc()) {
    $timeIn = strtotime($row['date'] . ' ' . $row['time_in']);
    $timeOut = strtotime($row['date'] . ' ' . $row['time_out']);
    
    // If timeOut is earlier than timeIn, assume it's the next day
    if ($timeOut < $timeIn) {
        $timeOut = strtotime('+1 day', strtotime($row['date'] . ' ' . $row['time_out']));
    }
    
    $duration = $timeOut - $timeIn;
    $totalDuration += $duration;
    $sessionCount++;
}

$avgDuration = $sessionCount > 0 ? round($totalDuration / $sessionCount) : 0;
$avgHours = floor($avgDuration / 3600);
$avgMinutes = floor(($avgDuration % 3600) / 60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History</title>
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
                            light: '#e0c3fc',
                            DEFAULT: '#b2a6cc',
                            dark: '#8ec5fc',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: #000000;
            margin: 0;
            min-height: 100vh;
            display: flex;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
        }

        /* Header style */
        .header {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Sidebar style */
        .sidebar {
            background: rgba(0, 0, 0, 0.8);
            border-right: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Update Sit-in History text color */
        .sidebar ul li a.active span {
            color: #ffffff !important;
        }

        .sidebar ul li a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Card and container styles */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
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

        /* Text colors */
        .text-gray-600 {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        .text-gray-800 {
            color: #ffffff !important;
        }

        /* Button and action styles */
        .action-btn {
            background: linear-gradient(135deg, #1D3B2A, #3C2E26) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, #3C2E26, #1D3B2A) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
            transform: translateY(-1px);
        }

        /* Table styles */
        table thead tr {
            background: rgba(166, 124, 82, 0.1);
        }

        table tbody tr {
            border-bottom: 1px solid rgba(166, 124, 82, 0.2);
        }

        table tbody tr:hover {
            background: rgba(166, 124, 82, 0.05);
        }

        /* Stats card styles */
        .stats-card {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            border: 1px solid rgba(166, 124, 82, 0.2);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
        }

        .stats-card:hover {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.6), rgba(60, 46, 38, 0.6));
            border: 1px solid rgba(151, 100, 50, 0.3);
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 header z-50 py-2 px-6 no-print">
        <h1 class="text-xl md:text-2xl font-bold text-center">Sit-in Records</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 sidebar pt-16 z-40 overflow-y-auto no-print">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
            <img src="images/admin_icon.jpg" alt="Admin Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
            <div class="font-semibold text-center"><?php echo $_SESSION['username']; ?></div>
            <div class="text-sm opacity-75">Administrator</div>
        </div>
        
        <ul class="px-4">
            <li class="mb-1"><a href="admin_dashboard.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-tachometer-alt w-6"></i> <span>Dashboard</span>
            </a></li>
            <li class="mb-1"><a href="manage_search.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-search w-6"></i> <span>Search</span>
            </a></li>
            <li class="mb-1"><a href="manage_currsitin.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-chair w-6"></i> <span>Current Sit-In</span>
            </a></li>
            <li class="mb-1"><a href="manage_history.php" class="flex items-center py-2 px-4 rounded bg-white/30 text-primary-dark font-semibold active">
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
    <div class="ml-64 pt-16 p-6 page-container">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 no-print">
            <!-- Total Sessions Card -->
            <div class="stats-card p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-chart-line icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo number_format($stats['total_sessions']); ?></p>
                        <p class="text-sm text-gray-600">Total Completed Sessions</p>
                    </div>
                </div>
            </div>
            
            <!-- Unique Users Card -->
            <div class="stats-card p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-users icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo number_format($stats['unique_users']); ?></p>
                        <p class="text-sm text-gray-600">Unique Students</p>
                    </div>
                </div>
            </div>
            
            <!-- Average Duration Card -->
            <div class="stats-card p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-clock icon-primary text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo $avgHours; ?>h <?php echo $avgMinutes; ?>m</p>
                        <p class="text-sm text-gray-600">Average Duration</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- History Table -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Sit-in Records</h2>
        </div>
        
        <div class="glass p-5 rounded-lg" id="history-table-container">
            <div class="overflow-x-auto">
                <table class="w-full" id="history-table">
                    <thead>
                        <tr class="bg-white/20 text-left">
                            <th class="p-3 rounded-tl-lg">ID Number</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Purpose</th>
                            <th class="p-3">Laboratory</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Time In</th>
                            <th class="p-3">Time Out</th>
                            <th class="p-3">Duration</th>
                            <th class="p-3 rounded-tr-lg">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                // Format date for display
                                $formattedDate = date('M d, Y', strtotime($row['date']));
                                // Format time_in and time_out to 12-hour format
                                $formattedTimeIn = date('h:i A', strtotime($row['time_in']));
                                $formattedTimeOut = date('h:i A', strtotime($row['time_out']));
                                
                                // Calculate duration
                                $timeIn = strtotime($row['date'] . ' ' . $row['time_in']);
                                $timeOut = strtotime($row['date'] . ' ' . $row['time_out']);
                                
                                // If timeOut is earlier than timeIn, assume it's the next day
                                if ($timeOut < $timeIn) {
                                    $timeOut = strtotime('+1 day', strtotime($row['date'] . ' ' . $row['time_out']));
                                }
                                
                                $duration = $timeOut - $timeIn;
                                $hours = floor($duration / 3600);
                                $minutes = floor(($duration % 3600) / 60);
                        ?>
                            <tr class="border-b border-white/20 hover:bg-white/10">
                                <td class="p-3"><?php echo htmlspecialchars($row['idNo']); ?></td>
                                <td class="p-3 font-medium">
                                    <?php echo htmlspecialchars($row['lastName'] . ', ' . $row['firstName']); ?>
                                </td>
                                <td class="p-3"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['laboratory']); ?></td>
                                <td class="p-3"><?php echo $formattedDate; ?></td>
                                <td class="p-3"><?php echo $formattedTimeIn; ?></td>
                                <td class="p-3"><?php echo $formattedTimeOut; ?></td>
                                <td class="p-3"><?php echo $hours; ?>h <?php echo $minutes; ?>m</td>
                                <td class="p-3">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-green-100/50 text-green-800">
                                        <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="9" class="p-6 text-center text-gray-500">
                                    <i class="fas fa-history text-4xl mb-3 block"></i>
                                    <p>No completed sit-in records found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>