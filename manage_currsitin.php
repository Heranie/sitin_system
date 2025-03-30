<?php
session_start();
date_default_timezone_set('Asia/Manila');
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Get current active sit-ins
$sitinQuery = "SELECT s.*, u.firstName, u.lastName, u.idNo, u.username 
               FROM new_sitin s 
               JOIN users u ON s.user_id = u.id 
               WHERE s.status = 'active' AND s.time_out IS NULL
               ORDER BY s.date DESC, s.time_in DESC";
$sitinResult = $conn->query($sitinQuery);



// Process check-out if requested
if(isset($_POST['checkout']) && isset($_POST['sitin_id'])) {
    $sitinId = $_POST['sitin_id'];
    
    // Make sure we're using Philippine Time
    date_default_timezone_set('Asia/Manila');
    $timeOut = date('H:i:s'); // 24-hour format for database storage
    
    // First, get the user associated with this sit-in record
    $getUserQuery = "SELECT s.user_id, u.idNo FROM new_sitin s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
    $stmt = $conn->prepare($getUserQuery);
    $stmt->bind_param("i", $sitinId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $studentId = $user['idNo'];
        
        // Update the sit-in record status to completed
        $updateQuery = "UPDATE new_sitin SET time_out = ?, status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $timeOut, $sitinId);
        
        if($stmt->execute()) {
            // Deduct one session after successful checkout
            $updateSessionQuery = "UPDATE users SET session = session - 1 WHERE idNo = ? AND session > 0";
            $stmt = $conn->prepare($updateSessionQuery);
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            
            header("Location: manage_currsitin.php?msg=success");
            exit();
        } else {
            $error = "Error updating sit-in record: " . $conn->error;
        }
    } else {
        $error = "Error finding user associated with this sit-in";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit-In Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
                    },
                    fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script src="js/particles.js" defer></script>
    <style>
        /* Base styles */
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

        /* Unified sidebar styles */
        .sidebar ul li a {
            color: #FFFFFF !important;
        }

        .sidebar ul li a i {
            color: #A67C52 !important;
            transition: all 0.3s ease;
        }

        .sidebar ul li a span {
            color: #FFFFFF !important;
        }

        .sidebar ul li a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar ul li a.active span {
            color: #A67C52 !important;
        }

        /* Admin text colors */
        .sidebar .text-sm.opacity-75,
        .sidebar .font-semibold {
            color: #FFFFFF !important;
        }

        /* Container and card styles */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
        }

        /* Icon container */
        .icon-container {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            color: #A67C52;
            border: 1px solid rgba(166, 124, 82, 0.2);
            transition: all 0.3s ease;
        }

        /* Text colors */
        .text-gray-600 {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        .text-gray-800 {
            color: #ffffff !important;
        }

        /* Button styles */
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

        /* Global icon colors */
        i,
        .fa-tachometer-alt,
        .fa-search,
        .fa-chair,
        .fa-history,
        .fa-chart-bar,
        .fa-users,
        .fa-sign-out-alt,
        .fa-check-circle,
        .fa-exclamation-circle,
        .fa-info-circle,
        .fa-times,
        .fa-mug-hot,
        .fa-sync-alt,
        .fa-code,
        .fa-clock,
        .icon-primary {
            color: #A67C52 !important;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-gradient-to-r from-[rgba(29,59,42,0.9)] to-[rgba(60,46,38,0.9)] backdrop-blur-lg z-50 border-b border-white/20 py-2 px-6 header">
        <h1 class="text-xl md:text-2xl font-bold text-center text-white">Current Sit-In Management</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 glass border-r border-white/20 pt-16 z-40 overflow-y-auto no-print">
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
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="mb-6 p-4 rounded-lg bg-green-100/80 text-green-800">
                <i class="fas fa-check-circle mr-2"></i> Student successfully timeout from sit-in.
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="mb-6 p-4 rounded-lg bg-red-100/80 text-red-800">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="glass p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Current Active Sit-Ins</h2>
                
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search by name or ID..." 
                           class="px-4 py-2 pl-10 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                </div>
            </div>
            
            <?php if($sitinResult && $sitinResult->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-[rgba(178,166,204,0.5)] to-[rgba(217,230,255,0.5)] text-left">
                                <th class="p-3 rounded-tl-lg">Student Name</th>
                                <th class="p-3">ID Number</th>
                                <th class="p-3">Laboratory</th>
                                <th class="p-3">Purpose</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Time In</th>
                                <th class="p-3">Duration</th>
                                <th class="p-3 rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $sitinResult->fetch_assoc()): ?>
                                <tr class="border-b border-white/20 hover:bg-white/10">
                                    <td class="p-3 font-medium"><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['idNo']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['laboratory']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="p-3"><?php echo date('F j, Y', strtotime($row['date'])); ?></td>
                                    <td class="p-3"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                                    <td class="p-3">
                                        <?php 
                                        if ($row['status'] === 'active' && $row['time_out'] === NULL) {
                                            // Make sure we're using Philippine Time
                                            date_default_timezone_set('Asia/Manila');
                                            
                                            // For active sit-ins that haven't been timed out yet
                                            $timeIn = strtotime($row['date'] . ' ' . $row['time_in']);
                                            $currentTime = time();
                                            $duration = $currentTime - $timeIn;
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            
                                            // Add data attributes for the raw values
                                            echo '<span class="text-blue-600 font-medium" 
                                                    data-time-in="' . htmlspecialchars($row['time_in']) . '" 
                                                    data-date="' . htmlspecialchars($row['date']) . '">' 
                                                . $hours . 'h ' . $minutes . 'm (ongoing)</span>';
                                        } else {
                                            // Calculate duration for sit-ins that have been timed out
                                            $timeIn = strtotime($row['date'] . ' ' . $row['time_in']);
                                            $timeOut = strtotime($row['date'] . ' ' . $row['time_out']);
                                            
                                            // If timeOut is earlier than timeIn, assume it's the next day
                                            if ($timeOut < $timeIn) {
                                                $timeOut = strtotime('+1 day', strtotime($row['date'] . ' ' . $row['time_out']));
                                            }
                                            
                                            $duration = $timeOut - $timeIn;
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            echo $hours . 'h ' . $minutes . 'm';
                                        }
                                        ?>
                                    </td>
                                    <td class="p-3">
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to check out this student? This will deduct one session.')">
                                            <input type="hidden" name="sitin_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="checkout" class="bg-primary-dark hover:bg-primary text-white px-3 py-1 rounded transition-colors">
                                                <i class="fas fa-sign-out-alt mr-1"></i> Timeout
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center p-10">
                    <i class="fas fa-mug-hot text-5xl mb-4 text-gray-500"></i>
                    <p class="text-lg mb-2">No active sit-ins at the moment</p>
                    <p class="text-sm text-gray-600">When students register for sit-ins, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
            <div class="glass p-4">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-3 mr-4">
                        <i class="fas fa-users icon-primary"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Students Today</p>
                        <p class="text-xl font-bold">
                            <?php
                            $todayCountQuery = "SELECT COUNT(*) as count FROM new_sitin WHERE date = CURRENT_DATE()";
                            $todayResult = $conn->query($todayCountQuery);
                            $todayCount = $todayResult->fetch_assoc()['count'];
                            echo $todayCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="glass p-4">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-3 mr-4">
                        <i class="fas fa-chair icon-primary"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Currently Active</p>
                        <p class="text-xl font-bold">
                            <?php
                            $activeCountQuery = "SELECT COUNT(*) as count FROM new_sitin WHERE status = 'active' AND time_out IS NULL";
                            $activeResult = $conn->query($activeCountQuery);
                            $activeCount = $activeResult->fetch_assoc()['count'];
                            echo $activeCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="glass p-4">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-3 mr-4">
                        <i class="fas fa-clock icon-primary"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Avg. Session Time</p>
                        <p class="text-xl font-bold">
                            <?php
                            // Better average time calculation that handles overnight sessions
                            $avgTimeQuery = "SELECT date, time_in, time_out 
                                        FROM new_sitin 
                                        WHERE time_out IS NOT NULL 
                                        AND status = 'completed'
                                        AND DATE(date) = CURRENT_DATE()";
                            $avgResult = $conn->query($avgTimeQuery);
                            
                            $totalSeconds = 0;
                            $count = 0;
                            
                            if($avgResult && $avgResult->num_rows > 0) {
                                while($row = $avgResult->fetch_assoc()) {
                                    $timeIn = strtotime($row['date'] . ' ' . $row['time_in']);
                                    $timeOut = strtotime($row['date'] . ' ' . $row['time_out']);
                                    
                                    // If timeOut is earlier than timeIn, assume it's the next day
                                    if ($timeOut < $timeIn) {
                                        $timeOut = strtotime('+1 day', strtotime($row['date'] . ' ' . $row['time_out']));
                                    }
                                    
                                    $duration = $timeOut - $timeIn;
                                    $totalSeconds += $duration;
                                    $count++;
                                }
                                
                                if($count > 0) {
                                    $avgTime = $totalSeconds / $count;
                                    $hours = floor($avgTime / 3600);
                                    $minutes = floor(($avgTime % 3600) / 60);
                                    echo $hours . 'h ' . $minutes . 'm';
                                } else {
                                    echo '0h 0m';
                                }
                            } else {
                                echo '0h 0m';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="glass p-4">
                <div class="flex items-center">
                    <div class="rounded-full icon-container p-3 mr-4">
                        <i class="fas fa-code icon-primary"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Top Purpose</p>
                        <p class="text-xl font-bold">
                            <?php
                            $topPurposeQuery = "SELECT purpose, COUNT(*) as count 
                                              FROM new_sitin 
                                              WHERE DATE(date) = CURRENT_DATE() 
                                              GROUP BY purpose 
                                              ORDER BY count DESC LIMIT 1";
                            $topResult = $conn->query($topPurposeQuery);
                            if($topResult && $topResult->num_rows > 0) {
                                $topPurpose = $topResult->fetch_assoc()['purpose'];
                                echo $topPurpose;
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // Live search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const id = row.cells[1].textContent.toLowerCase();
            
            if(name.includes(searchValue) || id.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Update the duration every minute for ongoing sessions
    setInterval(function() {
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const durationCell = row.querySelector('td:nth-child(7)');
            const durationText = durationCell.textContent;
            
            // Only update cells with "ongoing" text
            if(durationText.includes('(ongoing)')) {
                const timeInText = row.querySelector('td:nth-child(6)').textContent.trim(); // e.g. "04:25 AM"
                const dateText = row.querySelector('td:nth-child(5)').textContent.trim();   // e.g. "March 27, 2025"
                
                // Parse the time components manually to avoid AM/PM issues
                let [time, period] = timeInText.split(' ');
                let [hours, minutes] = time.split(':');
                hours = parseInt(hours);
                minutes = parseInt(minutes);
                
                // Convert to 24-hour format for calculation
                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }
                
                // Create date objects for the time in and current time
                const timeInDate = new Date();
                const currentTime = new Date();
                
                // Set the timeInDate to the correct date and time
                // Parse the month from the dateText
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const dateParts = dateText.split(' ');
                const month = monthNames.indexOf(dateParts[0]);
                const day = parseInt(dateParts[1].replace(',', ''));
                const year = parseInt(dateParts[2]);
                
                timeInDate.setFullYear(year, month, day);
                timeInDate.setHours(hours, minutes, 0, 0);
                
                // If time in is in the future (which can happen if there's a time zone issue),
                // assume it's a date problem and use today's date
                if (timeInDate > currentTime) {
                    timeInDate.setDate(timeInDate.getDate() - 1);
                }
                
                // Calculate duration in milliseconds
                const duration = currentTime - timeInDate;
                const durationHours = Math.floor(duration / (1000 * 60 * 60));
                const durationMinutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
                
                // Update the cell
                durationCell.innerHTML = '<span class="text-blue-600 font-medium">' + durationHours + 'h ' + durationMinutes + 'm (ongoing)</span>';
            }
        });
    }, 60000); // Update every minute
</script>
</body>
</html>