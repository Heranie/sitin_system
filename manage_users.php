<?php
session_start();
if(!isset($_SESSION['username']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])){
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Process form submissions first
$successMessage = "";
$errorMessage = "";

// Add new user
if(isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    // IMPORTANT: Need to use MD5 for password since your DB field is limited to 32 chars
    $password = md5(trim($_POST['password'])); // Changed to md5 to match your DB structure
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $idNo = trim($_POST['idNo']);
    $course = trim($_POST['course']);
    $yearLevel = trim($_POST['yearLevel']);
    $sessions = isset($_POST['sessions']) ? intval($_POST['sessions']) : 30;
    // Removed user_type since it doesn't exist in your table
    
    // Check if email or username already exists
    $checkQuery = "SELECT * FROM users WHERE email = ? OR username = ? OR idNo = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sss", $email, $username, $idNo);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "User with this email, username, or ID already exists!";
        header("Location: manage_users.php");
        exit();
    } else {
        // ... existing code ...
        
        if($stmt->execute()) {
            $_SESSION['success_message'] = "User added successfully!";
            header("Location: manage_users.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error adding user: " . $conn->error;
            header("Location: manage_users.php");
            exit();
        }
    }
}

// Reset sessions for all users
if(isset($_POST['reset_all_sessions'])) {
    $defaultSessions = 30;
    $updateQuery = "UPDATE users SET session = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $defaultSessions);
    
    if($stmt->execute()) {
        $_SESSION['success_message'] = "All user sessions have been reset to 30!";
        // Redirect to prevent message showing on refresh
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error resetting sessions: " . $conn->error;
        header("Location: manage_users.php");
        exit();
    }
}

// Reset sessions for one user
if(isset($_POST['reset_user_session'])) {
    $userId = $_POST['user_id'];
    $defaultSessions = 30;
    
    // First check if the session was previously 0
    $checkQuery = "SELECT session FROM users WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $previousSession = $result->fetch_assoc()['session'];
    
    // Then update
    $updateQuery = "UPDATE users SET session = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $defaultSessions, $userId);
    
    if($stmt->execute()) {
        if ($previousSession <= 0) {
            $_SESSION['success_message'] = "User sessions have been reset to 30! <span class='font-bold text-green-700'>The student can now use the lab again.</span>";
        } else {
            $_SESSION['success_message'] = "User sessions have been reset to 30!";
        }
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error resetting user sessions: " . $conn->error;
        header("Location: manage_users.php");
        exit();
    }
}

// Edit user
if(isset($_POST['edit_user'])) {
    $userId = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $idNo = trim($_POST['idNo']);
    $course = trim($_POST['course']);
    $yearLevel = trim($_POST['yearLevel']);
    $sessions = isset($_POST['sessions']) ? intval($_POST['sessions']) : 30;
    
    // Check if another user (not this one) has the same email/username/idNo
    $checkQuery = "SELECT * FROM users WHERE (email = ? OR username = ? OR idNo = ?) AND id != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sssi", $email, $username, $idNo, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "Another user with this email, username, or ID already exists!";
        header("Location: manage_users.php");
        exit();
    } else {
        // ... existing code ...
        
        if($stmt->execute()) {
            $_SESSION['success_message'] = "User updated successfully!";
            header("Location: manage_users.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating user: " . $conn->error;
            header("Location: manage_users.php");
            exit();
        }
    }
}

// Delete user
if(isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $userId);
    
    if($stmt->execute()) {
        $_SESSION['success_message'] = "User deleted successfully!";
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error deleting user: " . $conn->error;
        header("Location: manage_users.php");
        exit();
    }
}

// Get all users - removed user_type filter
$sql = "SELECT * FROM users ORDER BY lastName, firstName";
$result = $conn->query($sql);

// Get counts for stats - removed user_type filter
$totalStudentsQuery = "SELECT COUNT(*) as count FROM users";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult->fetch_assoc()['count'];

$activeSitinsQuery = "SELECT COUNT(DISTINCT user_id) as count FROM new_sitin WHERE status = 'active'";
$activeSitinsResult = $conn->query($activeSitinsQuery);
$activeStudents = $activeSitinsResult->fetch_assoc()['count'];

$fullSessionsQuery = "SELECT COUNT(*) as count FROM users WHERE session <= 0";
$fullSessionsResult = $conn->query($fullSessionsQuery);
$fullSessions = $fullSessionsResult->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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

        /* Card and container styles */
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

        /* Text colors */
        .text-gray-600 {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        .text-gray-800 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Icon and button styles */
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

        /* Form button styles */
        button[type="submit"], 
        #addUserBtn,
        .action-btn {
            background: linear-gradient(135deg, #1D3B2A, #3C2E26) !important;
            color: #A67C52 !important;
            border: 1px solid rgba(166, 124, 82, 0.3) !important;
            transition: all 0.3s ease;
        }

        button[type="submit"]:hover,
        #addUserBtn:hover,
        .action-btn:hover {
            background: linear-gradient(135deg, #3C2E26, #1D3B2A) !important;
            border-color: rgba(166, 124, 82, 0.5) !important;
            transform: translateY(-1px);
        }

        /* Stats card icons */
        .stats-icon {
            color: #A67C52 !important;
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
        }

        /* Icon colors - Updated all icons to match */
        i,
        .fa-tachometer-alt,
        .fa-search,
        .fa-chair,
        .fa-history,
        .fa-chart-bar,
        .fa-users,
        .fa-sign-out-alt,
        .fa-sync-alt,
        .fa-edit,
        .fa-trash-alt,
        .fa-check-circle,
        .fa-exclamation-circle,
        .fa-info-circle,
        .fa-times,
        .fa-user-plus,
        .fa-user-graduate,
        .fa-user-clock,
        .fa-user-slash {
            color: #A67C52 !important;
            transition: all 0.3s ease;
        }

        /* Hover state for sidebar icons */
        .sidebar ul li a:hover i {
            color: #B4B0D5 !important;
        }

        .sidebar ul li a.active i {
            color: #A67C52 !important;
        }

        .sidebar .text-primary-dark {
            color: #B4B0D5 !important;
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 header backdrop-blur-lg z-50 border-b border-white/20 py-2 px-6">
        <h1 class="text-xl md:text-2xl font-bold text-center">Manage Users</h1>
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
    <div class="ml-64 pt-4 p-6">
        <!-- Success and Error Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="p-3 mb-4 bg-green-100/80 text-green-800 rounded-lg flex items-center justify-between">
                <div><i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?></div>
                <button onclick="this.parentElement.style.display='none'" class="text-green-800 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="p-3 mb-4 bg-red-100/80 text-red-800 rounded-lg flex items-center justify-between">
                <div><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?></div>
                <button onclick="this.parentElement.style.display='none'" class="text-red-800 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Students Card -->
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="rounded-full stats-icon p-4 mr-4">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo $totalStudents; ?></p>
                        <p class="text-sm text-gray-600">Total Students</p>
                    </div>
                </div>
            </div>
            
            <!-- Active Students Card -->
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="rounded-full stats-icon p-4 mr-4">
                        <i class="fas fa-user-clock text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo $activeStudents; ?></p>
                        <p class="text-sm text-gray-600">Currently Active</p>
                    </div>
                </div>
            </div>
            
            <!-- Session Used Up Card -->
            <div class="glass p-5 rounded-lg">
                <div class="flex items-center">
                    <div class="rounded-full stats-icon p-4 mr-4">
                        <i class="fas fa-user-slash text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold"><?php echo $fullSessions; ?></p>
                        <p class="text-sm text-gray-600">Session Used Up</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="glass p-6 rounded-lg mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">User Management</h2>
                <div class="flex space-x-3">
                    <button id="addUserBtn" class="action-btn px-3 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-user-plus mr-2"></i> Add User
                    </button>
                    <form method="POST" name="reset_all_sessions_form">
                        <button type="submit" name="reset_all_sessions" class="action-btn px-3 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Reset All Sessions
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <input type="text" id="userSearch" placeholder="Search by name, email, ID number, or course..." 
                           class="w-full px-4 py-3 pl-10 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    <i class="fas fa-search absolute left-3 top-3.5 text-gray-500"></i>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-white/20 text-left">
                            <th class="p-3 rounded-tl-lg">Name</th>
                            <th class="p-3">ID Number</th>
                            <th class="p-3">Course</th>
                            <th class="p-3">Year</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Sessions</th>
                            <th class="p-3 rounded-tr-lg text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                $sessionClass = ($row['session'] <= 0) ? 'bg-red-100/50 text-red-800' : 'bg-green-100/50 text-green-800';
                        ?>
                            <tr class="border-b border-white/20 hover:bg-white/10">
                                <td class="p-3 font-medium">
                                    <?php echo htmlspecialchars($row['lastName'] . ', ' . $row['firstName']); ?>
                                </td>
                                <td class="p-3"><?php echo htmlspecialchars($row['idNo']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['course']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['yearLevel']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="p-3">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium <?php echo $sessionClass; ?>">
                                        <?php echo htmlspecialchars($row['session']); ?> sessions
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1">
                                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                            class="action-btn px-2 py-1 rounded">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form method="POST" class="inline" name="reset_session_form">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="reset_user_session" class="action-btn px-2 py-1 rounded">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                    
                                    <button onclick="deleteUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?>')" 
                                            class="action-btn px-2 py-1 rounded">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="7" class="p-6 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-3 block"></i>
                                    <p>No users found. Add a new user to get started.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" id="modalOverlay"></div>
        <div class="glass relative z-10 p-6 rounded-lg max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Add New User</h3>
                <button id="closeAddModal" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" id="firstName" name="firstName" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="idNo" class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" id="idNo" name="idNo" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="course" class="block text-sm font-medium mb-1">Course</label>
                        <select id="course" name="course" required
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                            <option value="">Select Course</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSCE">BSCE</option>
                            <option value="BSME">BSME</option>
                            <option value="BSEE">BSEE</option>
                            <option value="BSECE">BSECE</option>
                            <option value="BSIE">BSIE</option>
                            <option value="BSA">BSA</option>
                            <option value="BSBA">BSBA</option>
                        </select>
                    </div>
                    <div>
                        <label for="yearLevel" class="block text-sm font-medium mb-1">Year Level</label>
                        <select id="yearLevel" name="yearLevel" required
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="username" class="block text-sm font-medium mb-1">Username</label>
                        <input type="text" id="username" name="username" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium mb-1">Password</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="sessions" class="block text-sm font-medium mb-1">Initial Sessions</label>
                        <input type="number" id="sessions" name="sessions" value="30" min="0" max="100"
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="button" id="cancelAddBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                        Cancel
                    </button>
                    <button type="submit" name="add_user" class="action-btn px-4 py-2 rounded-lg">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" id="editModalOverlay"></div>
        <div class="glass relative z-10 p-6 rounded-lg max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Edit User</h3>
                <button id="closeEditModal" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_firstName" class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" id="edit_firstName" name="firstName" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="edit_lastName" class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" id="edit_lastName" name="lastName" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_idNo" class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" id="edit_idNo" name="idNo" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="edit_email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="edit_email" name="email" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_course" class="block text-sm font-medium mb-1">Course</label>
                        <select id="edit_course" name="course" required
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                            <option value="">Select Course</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSCE">BSCE</option>
                            <option value="BSME">BSME</option>
                            <option value="BSEE">BSEE</option>
                            <option value="BSECE">BSECE</option>
                            <option value="BSIE">BSIE</option>
                            <option value="BSA">BSA</option>
                            <option value="BSBA">BSBA</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_yearLevel" class="block text-sm font-medium mb-1">Year Level</label>
                        <select id="edit_yearLevel" name="yearLevel" required
                                class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="edit_username" class="block text-sm font-medium mb-1">Username</label>
                        <input type="text" id="edit_username" name="username" required
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="edit_password" class="block text-sm font-medium mb-1">Password (leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password"
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                    <div>
                        <label for="edit_sessions" class="block text-sm font-medium mb-1">Available Sessions</label>
                        <input type="number" id="edit_sessions" name="sessions" min="0" max="100"
                               class="w-full px-3 py-2 rounded-lg border border-white/30 bg-white/20 backdrop-blur focus:outline-none">
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="button" id="cancelEditBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                        Cancel
                    </button>
                    <button type="submit" name="edit_user" class="action-btn px-4 py-2 rounded-lg">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Form (hidden) -->
    <form id="deleteUserForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="delete_user_id">
        <input type="hidden" name="delete_user" value="1">
    </form>
    
    <script>
        // Add User Modal
        const addUserBtn = document.getElementById('addUserBtn');
        const addUserModal = document.getElementById('addUserModal');
        const closeAddModal = document.getElementById('closeAddModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const modalOverlay = document.getElementById('modalOverlay');
        
        addUserBtn.addEventListener('click', () => {
            addUserModal.classList.remove('hidden');
        });
        
        closeAddModal.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
        });
        
        cancelAddBtn.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
        });
        
        modalOverlay.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
        });
        
        // Edit User Modal
        const editUserModal = document.getElementById('editUserModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const editModalOverlay = document.getElementById('editModalOverlay');
        
        function editUser(user) {
            // Populate form fields
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_firstName').value = user.firstName;
            document.getElementById('edit_lastName').value = user.lastName;
            document.getElementById('edit_idNo').value = user.idNo;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_course').value = user.course;
            document.getElementById('edit_yearLevel').value = user.yearLevel;
            document.getElementById('edit_sessions').value = user.session;
            
            // Clear password field (we don't want to show the hashed password)
            document.getElementById('edit_password').value = '';
            
            // Show modal
            editUserModal.classList.remove('hidden');
        }
        
        closeEditModal.addEventListener('click', () => {
            editUserModal.classList.add('hidden');
        });
        
        cancelEditBtn.addEventListener('click', () => {
            editUserModal.classList.add('hidden');
        });
        
        editModalOverlay.addEventListener('click', () => {
            editUserModal.classList.add('hidden');
        });
        
        // Delete User
        function deleteUser(userId, userName) {
            if(confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteUserForm').submit();
            }
        }
        
        // Search functionality
        document.getElementById('userSearch').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>

    <script>
        // Add this to your existing script section
        
        // Handle session reset with check for max sessions
        document.querySelectorAll('form[name="reset_session_form"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const currentSessionSpan = this.closest('tr').querySelector('td:nth-child(6) span');
                const currentSessionText = currentSessionSpan.textContent.trim();
                const currentSession = parseInt(currentSessionText);
                
                if (currentSession >= 30) {
                    e.preventDefault(); // Prevent form submission
                    
                    // Create notification
                    const notification = document.createElement('div');
                    notification.className = 'p-3 mb-4 bg-blue-100/80 text-blue-800 rounded-lg flex items-center justify-between fixed top-20 right-6 left-64 z-50';
                    notification.innerHTML = `
                        <div><i class="fas fa-info-circle mr-2"></i> This student already has the maximum number of sessions (30).</div>
                        <button onclick="this.parentElement.remove()" class="text-blue-800 hover:text-blue-900">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    // Add to DOM
                    document.body.appendChild(notification);
                    
                    // Auto remove after 5 seconds
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 5000);
                    
                    return false;
                }
                
                // If confirmation is needed
                return confirm('Are you sure you want to reset this user\'s sessions to 30?');
            });
        });
    </script>

    <script>
        // Add this new function to check if all sessions are already at maximum
        document.querySelector('form[name="reset_all_sessions_form"]').addEventListener('submit', function(e) {
            // Get all session counts
            const sessionSpans = document.querySelectorAll('tbody tr td:nth-child(6) span');
            let allAlreadyFull = true;
            
            // Check if at least one student doesn't have max sessions
            sessionSpans.forEach(span => {
                const sessionCount = parseInt(span.textContent);
                if (sessionCount < 30) {
                    allAlreadyFull = false;
                }
            });
            
            if (allAlreadyFull) {
                e.preventDefault(); // Prevent form submission
                
                // Create notification
                const notification = document.createElement('div');
                notification.className = 'p-3 mb-4 bg-blue-100/80 text-blue-800 rounded-lg flex items-center justify-between fixed top-20 right-6 left-64 z-50';
                notification.innerHTML = `
                    <div><i class="fas fa-info-circle mr-2"></i> All students already have the maximum number of sessions (30).</div>
                    <button onclick="this.parentElement.remove()" class="text-blue-800 hover:text-blue-900">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                // Add to DOM
                document.body.appendChild(notification);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
                
                return false;
            }
            
            // If confirmation is needed (this is your existing confirmation)
            return confirm('Are you sure you want to reset ALL student sessions to 30?');
        });
    </script>
</body>
</html>