<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Fetch activity (sit-in/reservation) reports
$activity_query = "SELECT ns.*, u.firstName, u.lastName, u.idNo 
                  FROM new_sitin ns
                  JOIN users u ON ns.user_id = u.id
                  ORDER BY ns.date DESC, ns.time_in DESC";
$activity_result = $conn->query($activity_query);

// Fetch feedback reports
$feedback_query = "SELECT f.id, f.sitin_id, f.rating, f.comments, f.date_submitted, 
                  ns.laboratory, ns.date, u.firstName, u.lastName, u.idNo 
                  FROM feedback f 
                  JOIN new_sitin ns ON f.sitin_id = ns.id 
                  JOIN users u ON ns.user_id = u.id 
                  ORDER BY f.date_submitted DESC";
$feedback_result = $conn->query($feedback_query);

// Get statistics for activity reports
$statsQuery = "SELECT 
    COUNT(*) as total_sessions,
    COUNT(DISTINCT user_id) as unique_users
    FROM new_sitin 
    WHERE status = 'completed'";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Calculate average duration properly
$avgDurationQuery = "SELECT time_in, time_out, date FROM new_sitin WHERE status = 'completed'";
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
    <title>Sit-in Reports</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        /* Base styles */
        body {
            background: #000000;
            margin: 0;
            min-height: 100vh;
            display: flex;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        /* Glass and container styles */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
        }

        /* Header style */
        .header {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Sidebar styles */
        .sidebar {
            background: rgba(0, 0, 0, 0.8);
            border-right: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Sidebar menu and icon colors */
        .sidebar i {
            color: #A67C52 !important;
            transition: all 0.3s ease;
        }

        .sidebar a:hover i {
            color: #5A6B4D !important;
        }

        .sidebar ul li a span {
            color: #FFFFFF !important;
        }

        .sidebar ul li a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar ul li a.active i,
        .sidebar ul li a.active span {
            color: #A67C52 !important;
        }

        /* Fix admin text colors */
        .sidebar .text-sm.opacity-75,
        .sidebar .font-semibold {
            color: #FFFFFF !important;
        }

        /* Text colors */
        .text-gray-600 {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        .text-gray-800 {
            color: #ffffff !important;
        }

        /* Button and stats styles */
        .action-btn,
        #printBtn, 
        #pdfBtn, 
        #excelBtn {
            background: linear-gradient(135deg, #1D3B2A, #3C2E26) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
            transition: all 0.3s ease;
        }

        .action-btn:hover,
        #printBtn:hover, 
        #pdfBtn:hover, 
        #excelBtn:hover {
            background: linear-gradient(135deg, #3C2E26, #1D3B2A) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
            transform: translateY(-1px);
        }

        /* Stats icon container */
        .stats-icon-container {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            color: #A67C52;
            border: 1px solid rgba(166, 124, 82, 0.2);
        }

        .rounded-full i {
            color: #A67C52 !important;
        }

        /* Tab styles */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background-color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(178, 166, 204, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(178, 166, 204, 0.8);
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                font-size: 12pt;
            }
            
            .no-print {
                display: none !important;
            }
            
            .glass {
                background: white;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .page-container {
                margin: 0;
                padding: 0;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
        }

        /* Update all icon colors */
        i,
        .fa-tachometer-alt,
        .fa-search,
        .fa-chair,
        .fa-history,
        .fa-chart-bar,
        .fa-users,
        .fa-sign-out-alt,
        .fa-print,
        .fa-file-pdf,
        .fa-file-excel,
        .fa-clipboard-list,
        .fa-comment-dots,
        .fa-clock,
        .fa-chart-line,
        .icon-primary {
            color: #A67C52 !important;
            transition: all 0.3s ease;
        }

        /* Maintain hover states for sidebar icons */
        .sidebar ul li a:hover i {
            color: #B4B0D5 !important;
        }

        .sidebar ul li a.active i {
            color: #A67C52 !important;
        }

        /* Fix rating stars color to stay yellow */
        .text-yellow-500 {
            color: #EFB839 !important;
        }
        
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-gradient-to-r from-[rgba(29,59,42,0.9)] to-[rgba(60,46,38,0.9)] backdrop-blur-lg z-50 border-b border-white/20 py-2 px-6 header">
        <h1 class="text-xl md:text-2xl font-bold text-center text-white">Sit-in Reports</h1>
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
            <li class="mb-1"><a href="reports.php" class="flex items-center py-2 px-4 rounded bg-white/30 font-semibold">
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
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="stats-icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo number_format($stats['total_sessions']); ?></p>
                        <p class="text-sm text-gray-600">Total Completed Sessions</p>
                    </div>
                </div>
            </div>
            
            <!-- Unique Users Card -->
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="stats-icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo number_format($stats['unique_users']); ?></p>
                        <p class="text-sm text-gray-600">Unique Students</p>
                    </div>
                </div>
            </div>
            
            <!-- Average Duration Card -->
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="stats-icon-container rounded-full p-4 mr-4">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo $avgHours; ?>h <?php echo $avgMinutes; ?>m</p>
                        <p class="text-sm text-gray-600">Average Duration</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="flex justify-between items-center mb-4 no-print">
            <h2 class="text-xl font-bold">Reports Dashboard</h2>
            
            <div class="flex space-x-2">
                <button id="printBtn" class="px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <button id="pdfBtn" class="px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </button>
                <button id="excelBtn" class="px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Export Excel
                </button>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="flex mb-4 no-print">
            <button id="activity-tab-btn" class="tab-btn active mr-2" onclick="openTab('activity')">
                <i class="fas fa-clipboard-list mr-2"></i> Activity Reports
            </button>
            <button id="feedback-tab-btn" class="tab-btn" onclick="openTab('feedback')">
                <i class="fas fa-comment-dots mr-2"></i> Feedback Reports
            </button>
        </div>
        
        <!-- Tab Contents -->
        <div id="tab-container">
            <!-- Activity Reports Tab -->
            <div id="activity-tab" class="tab-content active">
                <div class="glass p-5 rounded-lg" id="activity-table-container">
                    <h3 class="text-lg font-semibold mb-4">Sit-in Activity Reports</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full" id="activity-table">
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
                                if($activity_result && $activity_result->num_rows > 0):
                                    while($row = $activity_result->fetch_assoc()):
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
                                        
                                        // Determine if it's sit-in or reservation
                                        $type = isset($row['reservation_type']) && $row['reservation_type'] ? 'reservation' : 'sit-in';
                                        $typeBadge = $type == 'sit-in' ? 
                                                '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-blue-100/50 text-blue-800">Sit-In</span>' : 
                                                '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-green-100/50 text-green-800">Reservation</span>';
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
                                            <i class="fas fa-clipboard-list text-4xl mb-3 block"></i>
                                            <p>No activity records found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Reports Tab -->
            <div id="feedback-tab" class="tab-content">
                <div class="glass p-5 rounded-lg" id="feedback-table-container">
                    <h3 class="text-lg font-semibold mb-4">Feedback Reports</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full" id="feedback-table">
                            <thead>
                                <tr class="bg-white/20 text-left">
                                    <th class="p-3 rounded-tl-lg">Feedback ID</th>
                                    <th class="p-3">Student</th>
                                    <th class="p-3">ID Number</th>
                                    <th class="p-3">Laboratory</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Rating</th>
                                    <th class="p-3 rounded-tr-lg">Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($feedback_result && $feedback_result->num_rows > 0):
                                    while($row = $feedback_result->fetch_assoc()):
                                        // Format date for display
                                        $formattedDate = date('M d, Y', strtotime($row['date']));
                                        
                                        // Generate star rating display
                                        $rating = intval($row['rating']);
                                        $stars = '';
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                $stars .= '<i class="text-yellow-500">★</i>';
                                            } else {
                                                $stars .= '<i class="text-gray-300">★</i>';
                                            }
                                        }
                                ?>
                                    <tr class="border-b border-white/20 hover:bg-white/10">
                                        <td class="p-3"><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td class="p-3 font-medium">
                                            <?php echo htmlspecialchars($row['lastName'] . ', ' . $row['firstName']); ?>
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['idNo']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['laboratory']); ?></td>
                                        <td class="p-3"><?php echo $formattedDate; ?></td>
                                        <td class="p-3"><?php echo $stars; ?> (<?php echo $rating; ?>/5)</td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['comments']); ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="7" class="p-6 text-center text-gray-500">
                                            <i class="fas fa-comment-dots text-4xl mb-3 block"></i>
                                            <p>No feedback records found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to the clicked button
            document.getElementById(tabName + '-tab-btn').classList.add('active');
        }
        
        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        // PDF Export
        document.getElementById('pdfBtn').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            
            // Create timestamp for filename
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const activeTab = document.querySelector('.tab-content.active').id;
            const reportType = activeTab === 'activity-tab' ? 'activity' : 'feedback';
            const filename = `sitin-${reportType}-report-${timestamp}.pdf`;
            
            // Get active table container
            const tableContainerId = activeTab === 'activity-tab' ? 'activity-table-container' : 'feedback-table-container';
            const element = document.getElementById(tableContainerId);
            
            // Create PDF with larger paper size
            const doc = new jsPDF('l', 'mm', 'a3');
            
            // Add title and date
            doc.setFontSize(18);
            doc.text(`Sit-in Lab ${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report`, 14, 15);
            
            doc.setFontSize(10);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);
            
            // Add statistics for activity report
            if (reportType === 'activity') {
                let yPosition = 29;
                doc.text(`Total Sessions: <?php echo $stats['total_sessions'] ?>`, 14, yPosition);
                yPosition += 6;
                doc.text(`Unique Students: <?php echo $stats['unique_users'] ?>`, 14, yPosition);
                yPosition += 6;
                doc.text(`Average Duration: <?php echo $avgHours ?>h <?php echo $avgMinutes ?>m`, 14, yPosition);
                yPosition += 10;
            }
            
            // Use html2canvas to capture the table
            html2canvas(element, {
                scale: 2,
                backgroundColor: null
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 280;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                
                doc.addImage(imgData, 'PNG', 10, reportType === 'activity' ? 45 : 29, imgWidth, imgHeight);
                doc.save(filename);
            });
        });
        
        // Excel Export
        document.getElementById('excelBtn').addEventListener('click', function() {
            // Create timestamp for filename
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const activeTab = document.querySelector('.tab-content.active').id;
            const reportType = activeTab === 'activity-tab' ? 'activity' : 'feedback';
            const filename = `sitin-${reportType}-report-${timestamp}.xlsx`;
            
            // Get the table
            const tableId = activeTab === 'activity-tab' ? 'activity-table' : 'feedback-table';
            const table = document.getElementById(tableId);
            
            // Prepare data array
            const data = [];
            
            // Add header row
            const headerRow = [];
            const headers = table.querySelectorAll('thead th');
            headers.forEach(header => {
                headerRow.push(header.innerText);
            });
            data.push(headerRow);
            
            // Add data rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (!row.querySelector('td[colspan]')) { // Skip "no records" row
                    const rowData = [];
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        rowData.push(cell.innerText.replace(/\s+/g, ' ').trim());
                    });
                    data.push(rowData);
                }
            });
            
            // Create workbook and worksheet
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report`);
            
            // Generate Excel file
            XLSX.writeFile(wb, filename);
        });
    </script>
</body>
</html>
