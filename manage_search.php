<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

$searchResult = null;
$searchPerformed = false;
$message = "";
$isCurrentlyInSitIn = false; // Add this flag variable

// Handle search
if(isset($_POST['search'])) {
    $searchPerformed = true;
    $searchTerm = trim($_POST['searchTerm']);
    
    if(!empty($searchTerm)) {
        $searchQuery = "SELECT * FROM users WHERE idNo = ? OR username LIKE ? OR email LIKE ?";
        $stmt = $conn->prepare($searchQuery);
        $searchParam = "%{$searchTerm}%";
        $stmt->bind_param("sss", $searchTerm, $searchParam, $searchParam);
        $stmt->execute();
        $searchResult = $stmt->get_result();
        
        if($searchResult->num_rows === 0) {
            $message = "No students found matching the search criteria.";
        } else {
            // Check if student is currently in an active sit-in
            $student = $searchResult->fetch_assoc();
            $checkActiveSitIn = "SELECT COUNT(*) as active_count FROM new_sitin 
                                 WHERE user_id = ? AND status = 'active' AND time_out IS NULL";
            $stmt = $conn->prepare($checkActiveSitIn);
            $stmt->bind_param("i", $student['id']);
            $stmt->execute();
            $activeSitInResult = $stmt->get_result();
            $activeSitInCount = $activeSitInResult->fetch_assoc()['active_count'];
            
            if($activeSitInCount > 0) {
                $isCurrentlyInSitIn = true;
            }
            
            // Reset the pointer to the beginning of the result set
            $searchResult->data_seek(0);
        }
    } else {
        $message = "Please enter a search term.";
    }
}

date_default_timezone_set('Asia/Manila');
// Handle sit-in registration (without session deduction)
if(isset($_POST['new_sitin'])) {
    $studentId = $_POST['student_id'];
    $purpose = $_POST['purpose'];
    $laboratory = $_POST['laboratory'];
    $date = date('Y-m-d');
    $time = date('H:i:s'); 
    $displayTime = date('h:i A'); 
    
    // First check if student is already in an active sit-in
    $checkStudentQuery = "SELECT u.id FROM users u 
                          WHERE u.idNo = ? AND NOT EXISTS (
                              SELECT 1 FROM new_sitin s 
                              WHERE s.user_id = u.id 
                              AND s.status = 'active' 
                              AND s.time_out IS NULL
                          )";
    $stmt = $conn->prepare($checkStudentQuery);
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    
    if($checkResult->num_rows === 0) {
        $message = "Error: This student is currently in an active sit-in session.";
    } else {
        // Check if student exists and has available sessions
        $checkStudent = "SELECT * FROM users WHERE idNo = ? AND session > 0";
        $stmt = $conn->prepare($checkStudent);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            
            // Insert sit-in record without deducting a session
            $insertQuery = "INSERT INTO new_sitin (user_id, purpose, laboratory, date, time_in) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("issss", $student['id'], $purpose, $laboratory, $date, $time);
            
            if($stmt->execute()) {
                $message = "Student successfully registered for sit-in. No session deducted yet.";
                
                // Re-run the search to refresh the results
                $searchQuery = "SELECT * FROM users WHERE idNo = ?";
                $stmt = $conn->prepare($searchQuery);
                $stmt->bind_param("s", $studentId);
                $stmt->execute();
                $searchResult = $stmt->get_result();
                
                // Check if student is now in an active sit-in (they should be)
                $checkActiveSitIn = "SELECT COUNT(*) as active_count FROM new_sitin 
                                     WHERE user_id = ? AND status = 'active' AND time_out IS NULL";
                $stmt = $conn->prepare($checkActiveSitIn);
                $stmt->bind_param("i", $student['id']);
                $stmt->execute();
                $activeSitInResult = $stmt->get_result();
                $activeSitInCount = $activeSitInResult->fetch_assoc()['active_count'];
                
                if($activeSitInCount > 0) {
                    $isCurrentlyInSitIn = true;
                }
            } else {
                $message = "Error registering sit-in: " . $conn->error;
            }
        } else {
            $message = "Student not found or has no available sessions.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Students</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        
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
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-gradient-to-r from-[rgba(178,166,204,0.7)] to-[rgba(217,230,255,0.7)] backdrop-blur-lg z-50 border-b border-white/20 py-3 px-6">
        <h1 class="text-xl md:text-2xl font-bold text-center">Search Students</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 glass border-r border-white/20 pt-16 z-40 overflow-y-auto">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
            <img src="images/admin_icon.jpg" alt="Admin Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
            <div class="font-semibold text-center"><?php echo $_SESSION['username']; ?></div>
            <div class="text-sm opacity-75">Administrator</div>
        </div>
        
        <ul class="px-4">
            <li class="mb-1"><a href="admin_dashboard.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-tachometer-alt w-6"></i> <span>Dashboard</span>
            </a></li>
            <li class="mb-1"><a href="manage_search.php" class="flex items-center py-2 px-4 rounded bg-white/30 text-primary-dark font-semibold">
                <i class="fas fa-search w-6"></i> <span>Search</span>
            </a></li>
            <li class="mb-1"><a href="manage_currsitin.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-chair w-6"></i> <span>Current Sit-In</span>
            </a></li>
            <li class="mb-1"><a href="manage_history.php" class="flex items-center py-2 px-4 rounded hover:bg-white/20 transition-colors">
                <i class="fas fa-history w-6"></i> <span>History Sit-In</span>
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
    <div class="ml-64 pt-16 p-6">
        <div class="glass p-6">
            <h2 class="text-xl font-bold mb-4">Search Student</h2>
            <form method="POST" action="" class="mb-6">
                <div class="flex">
                    <input type="text" name="searchTerm" placeholder="Enter ID number, username or email" 
                           class="flex-1 px-4 py-2 rounded-l-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light">
                    <button type="submit" name="search" class="bg-primary-dark hover:bg-primary text-white px-4 py-2 rounded-r-lg transition-colors">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
            
            <?php if(!empty($message)): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo strpos($message, 'Error') !== false ? 'bg-red-100/80 text-red-800' : 'bg-green-100/80 text-green-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($searchPerformed && $searchResult && $searchResult->num_rows > 0): ?>
                <?php 
                // Fetch the student data
                $student = $searchResult->fetch_assoc();
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Student Information -->
                    <div class="col-span-2 glass p-5 rounded-lg">
                        <h3 class="text-lg font-bold mb-4 border-b border-white/30 pb-2">Student Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">ID Number</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($student['idNo']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Full Name</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Course</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($student['course']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Year Level</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($student['yearLevel']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Available Sessions</p>
                                <p class="font-semibold <?php echo $student['session'] <= 5 ? 'text-red-700' : 'text-green-700'; ?>"><?php echo htmlspecialchars($student['session']); ?></p>
                            </div>
                        </div>
                        
                        <?php if($isCurrentlyInSitIn): ?>
                            <div class="mt-4 p-3 bg-amber-100/80 rounded-lg text-amber-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i> 
                                <strong>Note:</strong> This student is currently participating in an active sit-in session. 
                                They must complete their timeout period before being assigned to a new sit-in.
                                <a href="manage_currsitin.php" class="underline ml-1">View current sit-ins</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- New Sit-in Form -->
                    <div class="glass p-5 rounded-lg">
                        <h3 class="text-lg font-bold mb-4 border-b border-white/30 pb-2">New Sit-in</h3>
                        
                        <?php if($isCurrentlyInSitIn): ?>
                            <div class="p-4 bg-amber-100/80 rounded-lg text-amber-800 mb-4">
                                <i class="fas fa-user-clock text-2xl mb-2 block text-center"></i>
                                <p class="text-center">
                                    This student is currently in an active sit-in session.
                                </p>
                                <div class="text-center mt-4">
                                    <a href="manage_currsitin.php" class="inline-block bg-primary-dark hover:bg-primary text-white py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Manage Current Sit-ins
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['idNo']); ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-1" for="purpose">Purpose</label>
                                    <select id="purpose" name="purpose" required
                                            class="w-full px-4 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light">
                                        <option value="">Select Purpose</option>
                                        <option value="C Programming">C Programming</option>
                                        <option value="Java Programming">Java Programming</option>
                                        <option value="C#">C#</option>
                                        <option value="PHP">PHP</option>
                                        <option value="ASP.net">ASP.net</option>
                                    </select>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium mb-1" for="laboratory">Laboratory</label>
                                    <select id="laboratory" name="laboratory" required
                                            class="w-full px-4 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none focus:ring-2 focus:ring-primary-light">
                                        <option value="">Select Laboratory</option>
                                        <option value="524">524</option>
                                        <option value="526">526</option>
                                        <option value="528">528</option>
                                        <option value="530">530</option>
                                        <option value="542">542</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="new_sitin" class="w-full bg-primary-dark hover:bg-primary text-white py-2 px-4 rounded-lg transition-colors"
                                       <?php echo $student['session'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-chair mr-2"></i> Sit-in
                                </button>
                                
                                <?php if($student['session'] <= 0): ?>
                                    <p class="text-red-700 text-sm text-center mt-2">
                                        <i class="fas fa-exclamation-circle"></i> Student has no available sessions
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-600 text-sm text-center mt-2">
                                        <i class="fas fa-info-circle"></i> Session will be deducted on timeout
                                    </p>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif($searchPerformed): ?>
                <div class="text-center p-10 glass">
                    <i class="fas fa-user-slash text-4xl mb-4 text-gray-600"></i>
                    <p class="text-lg">No students found matching your search criteria.</p>
                    <p class="text-sm text-gray-600 mt-2">Try searching with a different ID, username, or email.</p>
                </div>
            <?php else: ?>
                <div class="text-center p-10 glass">
                    <i class="fas fa-search text-4xl mb-4 text-gray-600"></i>
                    <p class="text-lg">Enter a student ID, username or email to begin.</p>
                    <p class="text-sm text-gray-600 mt-2">You can register the student for a sit-in after searching.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>