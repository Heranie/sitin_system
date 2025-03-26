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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        body {
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            background-attachment: fixed;
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
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
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-gradient-to-r from-[rgba(178,166,204,0.7)] to-[rgba(217,230,255,0.7)] backdrop-blur-lg z-50 border-b border-white/20 py-2 px-6 no-print">
        <h1 class="text-xl md:text-2xl font-bold text-center">Sit-in History</h1>
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
            <li class="mb-1"><a href="manage_history.php" class="flex items-center py-2 px-4 rounded bg-white/30 text-primary-dark font-semibold">
                <i class="fas fa-history w-6"></i> <span>Sit-In History</span>
            </a></li>
            <li class="mb-1"><a href="manage_users.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-users w-6"></i> <span>Manage Users</span>
            </a></li>
            <li class="mb-1"><a href="manage_feedback.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-comment w-6"></i> <span>Feedback</span>
            </a></li>
            <li class="mb-1"><a href="#" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-cog w-6"></i> <span>Settings</span>
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
                    <div class="rounded-full bg-purple-100/50 p-4 mr-4">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
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
                    <div class="rounded-full bg-blue-100/50 p-4 mr-4">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
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
                    <div class="rounded-full bg-green-100/50 p-4 mr-4">
                        <i class="fas fa-clock text-green-600 text-xl"></i>
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
            <h2 class="text-xl font-bold">Sit-in History</h2>
            
            <div class="flex space-x-2">
                <button id="printBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <button id="pdfBtn" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </button>
                <button id="excelBtn" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Export Excel
                </button>
            </div>
        </div>
        
        <!-- History Table -->
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
    
    <script>
        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        // PDF Export
        document.getElementById('pdfBtn').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            
            // Create timestamp for filename
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `sitin-history-${timestamp}.pdf`;
            
            // Get table container and create PDF
            const element = document.getElementById('history-table-container');
            
            // Create PDF with larger paper size
            const doc = new jsPDF('l', 'mm', 'a3');
            
            // Add title and date
            doc.setFontSize(18);
            doc.text('Sit-in Lab History Report', 14, 15);
            
            doc.setFontSize(10);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);
            
            // Add filter information if present
            let yPosition = 29;
            if (<?php echo $dateFilter ? 'true' : 'false' ?>) {
                doc.text(`Date Filter: <?php echo $dateFilter ?>`, 14, yPosition);
                yPosition += 6;
            }
            
            if (<?php echo $dateRangeStart && $dateRangeEnd ? 'true' : 'false' ?>) {
                doc.text(`Date Range: <?php echo $dateRangeStart ?> to <?php echo $dateRangeEnd ?>`, 14, yPosition);
                yPosition += 6;
            }
            
            if (<?php echo $labFilter ? 'true' : 'false' ?>) {
                doc.text(`Laboratory: <?php echo $labFilter ?>`, 14, yPosition);
                yPosition += 6;
            }
            
            if (<?php echo $purposeFilter ? 'true' : 'false' ?>) {
                doc.text(`Purpose: <?php echo $purposeFilter ?>`, 14, yPosition);
                yPosition += 6;
            }
            
            // Add statistics
            doc.text(`Total Sessions: <?php echo $stats['total_sessions'] ?>`, 14, yPosition);
            yPosition += 6;
            doc.text(`Unique Students: <?php echo $stats['unique_users'] ?>`, 14, yPosition);
            yPosition += 6;
            doc.text(`Average Duration: <?php echo $avgHours ?>h <?php echo $avgMinutes ?>m`, 14, yPosition);
            yPosition += 10;
            
            // Use html2canvas to capture the table
            html2canvas(element, {
                scale: 2,
                backgroundColor: null
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 280;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                
                doc.addImage(imgData, 'PNG', 10, yPosition, imgWidth, imgHeight);
                doc.save(filename);
            });
        });
        
        // Excel Export
        document.getElementById('excelBtn').addEventListener('click', function() {
            // Create timestamp for filename
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `sitin-history-${timestamp}.xlsx`;
            
            // Get the table
            const table = document.getElementById('history-table');
            
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
            XLSX.utils.book_append_sheet(wb, ws, 'Sit-in History');
            
            // Generate Excel file
            XLSX.writeFile(wb, filename);
        });
    </script>
</body>
</html>