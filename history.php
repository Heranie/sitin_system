<?php
session_start();

// Debug session values
// Uncomment to see session variables
// echo "<pre>SESSION: "; print_r($_SESSION); echo "</pre>";

// Basic redirects for not logged in users
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
    // Store the current page for redirection after login
    $_SESSION['redirect_after_login'] = 'history.php';
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Fetch user profile information
$username = $_SESSION['username'];
$userQuery = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get username from session
$username = $_SESSION['username'];

try {
    // Direct query to get the user - more reliable than prepared statements for debugging
    $userQuery = "SELECT * FROM users WHERE username = '$username'";
    $userResult = $conn->query($userQuery);
    
    // If no user found, clear session and redirect to login
    if (!$userResult || $userResult->num_rows === 0) {
        // Clear the session to force re-login
        session_unset();
        session_destroy();
        
        // Redirect to login with error message
        header("Location: index.php?error=invalid_user");
        exit();
    }
    
    // Get user data
    $userData = $userResult->fetch_assoc();
    $userId = $userData['id'];
    
    // Check that we have a valid user ID
    if (empty($userId)) {
        throw new Exception("User ID is missing or invalid.");
    }
    
    // Get sit-in history data
    $historyQuery = "SELECT * FROM new_sitin 
                    WHERE user_id = $userId 
                    AND status = 'completed' 
                    ORDER BY date DESC, time_in DESC";
    $historyResult = $conn->query($historyQuery);
    
    // Get statistics - using direct queries for simplicity and reliability
    $totalQuery = "SELECT COUNT(*) as total FROM new_sitin WHERE user_id = $userId AND status = 'completed'";
    $totalResult = $conn->query($totalQuery);
    $totalSessions = $totalResult->fetch_assoc()['total'];
    
    // Duration calculation
    $avgTimeQuery = "SELECT AVG(TIMESTAMPDIFF(MINUTE, CONCAT(date, ' ', time_in), CONCAT(date, ' ', time_out))) as avg_time 
                   FROM new_sitin 
                   WHERE user_id = $userId AND status = 'completed' AND time_out IS NOT NULL";
    $avgTimeResult = $conn->query($avgTimeQuery);
    $avgMinutes = 0;
    if($avgTimeResult && $avgTimeResult->num_rows > 0) {
        $avgMinutes = round($avgTimeResult->fetch_assoc()['avg_time']);
    }
    $avgHours = floor($avgMinutes / 60);
    $avgMins = $avgMinutes % 60;
    
    // Most used lab
    $labQuery = "SELECT laboratory, COUNT(*) as count FROM new_sitin 
              WHERE user_id = $userId AND status = 'completed'
              GROUP BY laboratory ORDER BY count DESC LIMIT 1";
    $labResult = $conn->query($labQuery);
    $mostUsedLab = $labResult && $labResult->num_rows > 0 ? $labResult->fetch_assoc()['laboratory'] : 'None';
    
    // Most common purpose
    $purposeQuery = "SELECT purpose, COUNT(*) as count FROM new_sitin 
                  WHERE user_id = $userId AND status = 'completed'
                  GROUP BY purpose ORDER BY count DESC LIMIT 1";
    $purposeResult = $conn->query($purposeQuery);
    $mostCommonPurpose = $purposeResult && $purposeResult->num_rows > 0 ? $purposeResult->fetch_assoc()['purpose'] : 'None';
    
} catch (Exception $e) {
    // Log the error (you should have a proper error logging system)
    error_log("History page error: " . $e->getMessage());
    
    // Show a user-friendly error page
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body { background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
        </style>
    </head>
    <body>
        <div class="bg-white/30 backdrop-blur-md p-8 rounded-xl shadow-lg max-w-md w-full text-center">
            <div class="text-5xl text-red-500 mb-4"><i class="fas fa-exclamation-circle"></i></div>
            <h1 class="text-2xl font-bold mb-4">Something went wrong</h1>
            <p class="mb-6">We\'re having trouble loading your history data.</p>
            <p class="text-sm mb-6">Error details: ' . htmlspecialchars($e->getMessage()) . '</p>
            <div class="flex gap-4 justify-center">
                <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Go to Dashboard</a>
                <a href="logout.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Logout & Try Again</a>
            </div>
        </div>
    </body>
    </html>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-In History</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        body {
            background: linear-gradient(135deg, #000000, #1a1a1a);
            background-attachment: fixed;
            margin: 0;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(237, 238, 245, 0.37);
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
        
        /* Dashboard card hover effects */
        .dash-card {
            transition: all 0.3s ease;
        }
        
        .dash-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.6), rgba(60, 46, 38, 0.6));
            border: 1px solid rgba(166, 124, 82, 0.3);
        }
        
        /* Adding styles for the detailed view modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .active-filter {
            background-color: rgba(142, 197, 252, 0.3);
            font-weight: bold;
        }

        .text-gray-600 {
            color: rgba(247, 241, 236, 0.8) !important;
        }
        .text-gray-800 {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        /* Add span text color */
        span {
            color: #ffffff !important;
        }

        .text-gray-600 span {
            color: rgba(247, 241, 236, 0.8) !important;
        }

        /* Make sure sidebar spans use white text */
        .sidebar ul li a span {
            color: #FFFFFF !important;
        }

        .dash-card {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            border: 1px solid rgba(166, 124, 82, 0.2);
            transition: all 0.3s ease;
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

        .icon-primary {
            color: #A67C52 !important;
        }

        
        .sidebar i {
            color: #A67C52;
            width: 20px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        /* Add button gradient style to match header */
        .btn-header-gradient {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            border: 1px solid rgba(166, 124, 82, 0.2);
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-header-gradient:hover {
            background: linear-gradient(to right, rgba(29, 59, 42, 1), rgba(60, 46, 38, 1));
            border: 1px solid rgba(166, 124, 82, 0.4);
        }

        /* Add sidebar navigation transitions */
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 4px 8px;
        }
        
        .sidebar ul li a:hover {
            background: linear-gradient(to right, rgba(166, 124, 82, 0.2), rgba(166, 124, 82, 0.1));
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar ul li a:hover i {
            transform: scale(1.2);
            color: #d4a373;
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Particle container -->
    <div id="particles-js" class="fixed inset-0 z-0"></div>
    
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 header z-50 border-b border-white/20 py-2 px-6">
        <h1 class="text-xl md:text-2xl font-bold text-center">Sit-In History</h1>
    </div>
    
<!-- Sidebar -->
<div class="fixed top-0 left-0 bottom-0 w-64 glass border-r border-white/20 pt-16 z-40 overflow-y-auto">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
        <img src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/admin_icon.jpg'; ?>" alt="User Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
            <div class="font-semibold text-center"><?php echo $_SESSION['username']; ?></div>
            <div class="text-sm opacity-75">Student</div>
        </div>
        
        <ul class="px-4">
            <li class="mb-1"><a href="dashboard.php" class="flex items-center py-2 px-4 rounded hover:bg/white/20 transition-colors">
                <i class="fas fa-tachometer-alt w-6" style="color: #A67C52;"></i> <span>Dashboard</span>
            </a></li>
            <li class="mb-1"><a href="profile.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-user w-6" style="color: #A67C52;"></i> <span>Profile</span>
            </a></li>   
            <li class="mb-1"><a href="history.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-history w-6" style="color: #A67C52;"></i> <span>History</span>
            </a></li>
            <li class="mb-1"><a href="reservation.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-calendar-alt w-6" style="color: #A67C52;"></i> <span>Reservation</span>
            </a></li>
            <li class="mb-1"><a href="logout.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-sign-out-alt w-6" style="color: #A67C52;"></i> <span>Log Out</span>
            </a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="ml-64 p-6 pt-20">
        <div class="max-w-6xl mx-auto">
            <?php if(isset($_SESSION['feedback_success'])): ?>
                <div class="glass bg-green-100/70 text-green-800 p-4 mb-6 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $_SESSION['feedback_success']; ?>
                </div>
                <?php unset($_SESSION['feedback_success']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['feedback_error'])): ?>
                <div class="glass bg-red-100/70 text-red-800 p-4 mb-6 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $_SESSION['feedback_error']; ?>
                </div>
                <?php unset($_SESSION['feedback_error']); ?>
            <?php endif; ?>
            
            <!-- History Summary Card -->
            <div class="glass p-6 rounded-lg dash-card mb-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-2" style="color: #A67C52;"></i>
                    Your Sit-In Summary
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="glass bg-white/10 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold"><?php echo $totalSessions; ?></div>
                        <div class="text-sm" style="color: #A67C52;">Total Sessions</div>
                    </div>
                    
                    <div class="glass bg-white/10 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold">
                            <?php echo $avgHours; ?>h <?php echo $avgMins; ?>m
                        </div>
                        <div class="text-sm" style="color: #A67C52;">Average Duration</div>
                    </div>
                    
                    <div class="glass bg-white/10 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold">Lab <?php echo $mostUsedLab; ?></div>
                        <div class="text-sm" style="color: #A67C52;">Most Used Lab</div>
                    </div>
                    
                    <div class="glass bg-white/10 p-4 rounded-lg text-center">
                        <div class="text-xl font-bold truncate"><?php echo $mostCommonPurpose; ?></div>
                        <div class="text-sm" style="color: #A67C52;">Common Purpose</div>
                    </div>
                </div>
            </div>
            
            <!-- History Table with enhanced filters -->
            <div class="glass p-6 rounded-lg dash-card">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-history mr-2" style="color: #A67C52;"></i>
                        Sit-In History
                    </h2>
                    
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex items-center">
                            <input type="text" id="historySearch" placeholder="Search..."
                                class="py-1 px-3 rounded-lg bg-white/20 border border-white/30 focus:outline-none focus:ring-2 focus:ring-primary-light/50 w-full md:w-auto">
                        </div>
                        
                        <div class="flex gap-2">
                            <button id="filterAll" class="py-1 px-3 rounded-lg bg-white/20 border border-white/30 hover:bg-white/30 active-filter">
                                All
                            </button>
                            <button id="filterLastWeek" class="py-1 px-3 rounded-lg bg-white/20 border border-white/30 hover:bg-white/30">
                                Last Week
                            </button>
                            <button id="filterLastMonth" class="py-1 px-3 rounded-lg bg-white/20 border border-white/30 hover:bg-white/30">
                                Last Month
                            </button>
                        </div>
                        
                        <button id="exportHistory" class="py-1 px-3 rounded-lg btn-header-gradient flex items-center">
                            <i class="fas fa-download mr-1"></i> Export
                        </button>
                    </div>
                </div>
                
                <?php if($historyResult && $historyResult->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white/20 rounded-lg overflow-hidden">
                            <thead class="bg-primary-dark/30 text-gray-800">
                                <tr>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-primary-dark/40" onclick="sortTable(0)">
                                        Date <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-primary-dark/40" onclick="sortTable(1)">
                                        Time In <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-primary-dark/40" onclick="sortTable(2)">
                                        Time Out <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-primary-dark/40" onclick="sortTable(3)">
                                        Duration <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-primary-dark/40" onclick="sortTable(4)">
                                        Laboratory <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="py-3 px-4 text-left">Purpose</th>
                                    <th class="py-3 px-4 text-left">Feedback</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <?php 
                                // Reset data pointer
                                $historyResult->data_seek(0);
                                while($record = $historyResult->fetch_assoc()): 
                                    // Calculate duration
                                    $timeIn = strtotime($record['date'] . ' ' . $record['time_in']);
                                    $timeOut = strtotime($record['date'] . ' ' . $record['time_out']);
                                    $duration = $timeOut - $timeIn;
                                    $durationHours = floor($duration / 3600);
                                    $durationMinutes = floor(($duration % 3600) / 60);
                                    
                                    // Store the raw date for filtering
                                    $rawDate = strtotime($record['date']);
                                ?>
                                <tr class="history-row border-b border-white/10 hover:bg-white/30 transition-colors cursor-pointer"
                                    data-date="<?php echo $rawDate; ?>"
                                    data-record='<?php echo json_encode($record); ?>'
                                    onclick="showDetails(this)">
                                    <td class="py-3 px-4">
                                        <?php echo date('M d, Y', strtotime($record['date'])); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo date('h:i A', strtotime($record['time_in'])); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo date('h:i A', strtotime($record['time_out'])); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo $durationHours; ?>h <?php echo $durationMinutes; ?>m
                                    </td>
                                    <td class="py-3 px-4">
                                        Lab <?php echo htmlspecialchars($record['laboratory']); ?>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs truncate" title="<?php echo htmlspecialchars($record['purpose']); ?>">
                                        <?php echo htmlspecialchars($record['purpose']); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php 
                                        // Check if feedback has already been given for this sit-in
                                        $feedbackQuery = "SELECT id FROM feedback WHERE sitin_id = " . $record['id'];
                                        $feedbackResult = $conn->query($feedbackQuery);
                                        $hasFeedback = $feedbackResult && $feedbackResult->num_rows > 0;
                                        
                                        if ($hasFeedback): ?>
                                            <button class="px-3 py-1 rounded bg-gray-400 text-white cursor-not-allowed" disabled>
                                                <i class="fas fa-check-circle"></i> Submitted
                                            </button>
                                        <?php else: ?>
                                            <button class="px-3 py-1 rounded bg-primary-dark text-white hover:bg-primary-dark/80 transition-colors" 
                                                    onclick="openFeedbackModal(<?php echo $record['id']; ?>, '<?php echo $record['laboratory']; ?>', '<?php echo date('M d, Y', strtotime($record['date'])); ?>'); event.stopPropagation();">
                                                <i class="fas fa-comment"></i> Feedback
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No results message (hidden by default) -->
                    <div id="noResultsMessage" class="hidden text-center py-10">
                        <div class="text-4xl text-gray-400 mb-4">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">No Results Found</h3>
                        <p class="text-gray-600">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10">
                        <div class="text-5xl text-gray-400 mb-4">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">No History Found</h3>
                        <p class="text-gray-600">
                            You haven't completed any sit-in sessions yet.
                            <br>Visit the reservation page to book your first session.
                        </p>
                        <a href="reservation.php" class="mt-4 inline-block btn-header-gradient px-4 py-2 rounded-lg">
                            <i class="fas fa-calendar-plus mr-2"></i> Make a Reservation
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content glass bg-white/50 p-6 rounded-xl shadow-lg max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold" id="modalTitle">Sit-In Details</h2>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <h3 class="text-sm text-gray-600">Date & Time</h3>
                        <div class="font-semibold" id="modalDateTime">-</div>
                    </div>
                    <div>
                        <h3 class="text-sm text-gray-600">Duration</h3>
                        <div class="font-semibold" id="modalDuration">-</div>
                    </div>
                    <div>
                        <h3 class="text-sm text-gray-600">Laboratory</h3>
                        <div class="font-semibold" id="modalLab">-</div>
                    </div>
                    <div>
                        <h3 class="text-sm text-gray-600">PC Number</h3>
                        <div class="font-semibold" id="modalPC">-</div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-sm text-gray-600">Purpose</h3>
                    <div class="bg-white/30 p-3 rounded-lg mt-1" id="modalPurpose">-</div>
                </div>
                
                <div class="flex justify-end">
                    <button onclick="closeModal()" class="bg-primary-dark hover:bg-primary text-white px-4 py-2 rounded-lg">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content glass bg-white/50 p-6 rounded-xl shadow-lg max-w-lg w-full">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Submit Feedback</h2>
                    <button onclick="closeFeedbackModal()" class="text-gray-500 hover:text-gray-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="feedbackForm" action="feedback.php" method="POST">
                    <input type="hidden" id="sitin_id" name="sitin_id" value="">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-1">Session Details:</p>
                        <p class="font-semibold" id="feedbackSessionDetails"></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-2">How was your experience?</label>
                        <div class="flex space-x-2 text-2xl">
                            <span class="star cursor-pointer text-gray-300" data-rating="1">★</span>
                            <span class="star cursor-pointer text-gray-300" data-rating="2">★</span>
                            <span class="star cursor-pointer text-gray-300" data-rating="3">★</span>
                            <span class="star cursor-pointer text-gray-300" data-rating="4">★</span>
                            <span class="star cursor-pointer text-gray-300" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="rating_value" value="0">
                    </div>
                    
                    <div class="mb-6">
                        <label for="comments" class="block text-sm text-gray-600 mb-2">Additional Comments</label>
                        <textarea id="comments" name="comments" rows="4" class="w-full px-3 py-2 bg-white/40 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="Tell us about your experience..."></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeFeedbackModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
                            Cancel
                        </button>
                        <button type="submit" class="bg-primary-dark hover:bg-primary text-white px-4 py-2 rounded-lg">
                            Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced search functionality for the history table
        document.getElementById('historySearch').addEventListener('keyup', function() {
            filterTable();
        });
        
        // Filter buttons
        document.getElementById('filterAll').addEventListener('click', function() {
            setActiveFilter(this);
            filterTable();
        });
        
        document.getElementById('filterLastWeek').addEventListener('click', function() {
            setActiveFilter(this);
            filterTable('week');
        });
        
        document.getElementById('filterLastMonth').addEventListener('click', function() {
            setActiveFilter(this);
            filterTable('month');
        });
        
        function setActiveFilter(button) {
            // Remove active class from all buttons
            document.querySelectorAll('#filterAll, #filterLastWeek, #filterLastMonth').forEach(btn => {
                btn.classList.remove('active-filter');
            });
            
            // Add active class to clicked button
            button.classList.add('active-filter');
        }
        
        function filterTable(timeFrame = null) {
            const searchTerm = document.getElementById('historySearch').value.toLowerCase();
            const rows = document.querySelectorAll('.history-row');
            const now = Math.floor(Date.now() / 1000); // Current time in seconds
            const oneWeek = 7 * 24 * 60 * 60; // One week in seconds
            const oneMonth = 30 * 24 * 60 * 60; // Approx one month in seconds
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowDate = parseInt(row.getAttribute('data-date'));
                
                let showByDate = true;
                
                if (timeFrame === 'week') {
                    showByDate = (now - rowDate) <= oneWeek;
                } else if (timeFrame === 'month') {
                    showByDate = (now - rowDate) <= oneMonth;
                }
                
                if (rowText.includes(searchTerm) && showByDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && rows.length > 0) {
                noResultsMessage.classList.remove('hidden');
            } else {
                noResultsMessage.classList.add('hidden');
            }
        }
        
        // Function to show details modal
        function showDetails(row) {
            const record = JSON.parse(row.getAttribute('data-record'));
            
            // Format date and time for display
            const date = new Date(record.date);
            const formattedDate = date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Calculate duration
            const timeIn = new Date(`${record.date} ${record.time_in}`);
            const timeOut = new Date(`${record.date} ${record.time_out}`);
            const duration = (timeOut - timeIn) / 1000; // in seconds
            const hours = Math.floor(duration / 3600);
            const minutes = Math.floor((duration % 3600) / 60);
            
            // Update modal content
            document.getElementById('modalDateTime').textContent = `${formattedDate}, ${formatTime(record.time_in)} - ${formatTime(record.time_out)}`;
            document.getElementById('modalDuration').textContent = `${hours}h ${minutes}m`;
            document.getElementById('modalLab').textContent = `Laboratory ${record.laboratory}`;
            document.getElementById('modalPC').textContent = record.pc_number || 'Not specified';
            document.getElementById('modalPurpose').textContent = record.purpose;
            
            // Show modal
            document.getElementById('detailsModal').style.display = 'block';
        }
        
        // Function to close modal
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Helper function to format time
        function formatTime(timeStr) {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            return `${hour > 12 ? hour - 12 : hour}:${minutes} ${hour >= 12 ? 'PM' : 'AM'}`;
        }
        
        // Feedback Modal Functions
        function openFeedbackModal(sitinId, lab, date) {
            document.getElementById('sitin_id').value = sitinId;
            document.getElementById('feedbackSessionDetails').textContent = `Lab ${lab} on ${date}`;
            document.getElementById('feedbackModal').style.display = 'block';
            
            // Clear any previous selection
            document.querySelectorAll('.star').forEach(star => {
                star.classList.remove('text-yellow-500');
                star.classList.add('text-gray-300');
            });
            document.getElementById('rating_value').value = 0;
            document.getElementById('comments').value = '';
        }
        
        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
        }
        
        // Star rating system
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating_value').value = rating;
                
                // Update visual state of stars
                document.querySelectorAll('.star').forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating <= rating) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-500');
                    } else {
                        s.classList.remove('text-yellow-500');
                        s.classList.add('text-gray-300');
                    }
                });
            });
            
            // Hover effects
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                
                document.querySelectorAll('.star').forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating <= rating) {
                        s.classList.add('text-yellow-400');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentRating = document.getElementById('rating_value').value;
                
                document.querySelectorAll('.star').forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    s.classList.remove('text-yellow-400');
                    
                    if (starRating <= currentRating) {
                        s.classList.add('text-yellow-500');
                    } else {
                        s.classList.add('text-gray-300');
                    }
                });
            });
        });
        
        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.getElementById('rating_value').value;
            
            if (rating === '0') {
                e.preventDefault();
                alert('Please select a rating before submitting your feedback.');
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const feedbackModal = document.getElementById('feedbackModal');
            
            if (event.target === detailsModal) {
                closeModal();
            }
            
            if (event.target === feedbackModal) {
                closeFeedbackModal();
            }
        }
        
        // Table sorting functionality
        function sortTable(columnIndex) {
            const table = document.getElementById('historyTableBody');
            const rows = Array.from(table.rows);
            let sortDirection = 'asc';
            
            // Check if we're reversing an existing sort
            if (table.getAttribute('data-sort-column') == columnIndex) {
                sortDirection = table.getAttribute('data-sort-direction') === 'asc' ? 'desc' : 'asc';
            }
            
            // Store sort state
            table.setAttribute('data-sort-column', columnIndex);
            table.setAttribute('data-sort-direction', sortDirection);
            
            // Sort the rows
            rows.sort((a, b) => {
                let aValue = a.cells[columnIndex].textContent.trim();
                let bValue = b.cells[columnIndex].textContent.trim();
                
                // Special handling for date column
                if (columnIndex === 0) {
                    aValue = new Date(aValue).getTime();
                    bValue = new Date(bValue).getTime();
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }
                
                // Special handling for duration column
                if (columnIndex === 3) {
                    aValue = parseDuration(aValue);
                    bValue = parseDuration(bValue);
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }
                
                // Default string comparison
                return sortDirection === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });
            
            // Reorder the table
            rows.forEach(row => table.appendChild(row));
        }
        
        // Helper function to parse duration into minutes
        function parseDuration(durationStr) {
            const parts = durationStr.match(/(\d+)h\s+(\d+)m/);
            if (!parts) return 0;
            return parseInt(parts[1]) * 60 + parseInt(parts[2]);
        }
        
        // Export functionality
        document.getElementById('exportHistory').addEventListener('click', function() {
            // Get visible rows only
            const rows = Array.from(document.querySelectorAll('.history-row'))
                .filter(row => row.style.display !== 'none');
            
            if (rows.length === 0) {
                alert('No data to export.');
                return;
            }
            
            let csvContent = 'Date,Time In,Time Out,Duration,Laboratory,Purpose\n';
            
            rows.forEach(row => {
                // Only select the first 6 cells (exclude the feedback column)
                const cells = Array.from(row.cells).slice(0, 6);
                const rowData = cells.map(cell => `"${cell.textContent.trim()}"`);
                csvContent += rowData.join(',') + '\n';
            });
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'sit_in_history.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS('particles-js',
        {
            "particles": {
                "number": {
                    "value": 50,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#A67C52"
                },
                "shape": {
                    "type": "circle"
                },
                "opacity": {
                    "value": 0.3,
                    "random": false
                },
                "size": {
                    "value": 3,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#A67C52",
                    "opacity": 0.2,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                }
            },
            "retina_detect": true
        });
    </script>
</html>