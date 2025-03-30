<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'user'){
    header("Location: index.php");
    exit();
}

include 'connect.php';

$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($query);
$user = $result->fetch_assoc();

// Handle profile update
if(isset($_POST['updateProfile'])){
    $firstName = $_POST['fName'];
    $lastName = $_POST['lName'];
    $middleName = $_POST['mName'];
    $course = $_POST['course'];
    $yearLevel = $_POST['yearLevel'];
    $email = $_POST['email'];
    $address = $_POST['address'] ?? '';

    // Validate course selection
    $validCourses = array(
        "BS in Accountancy",
        "BS in Business Administration",
        "BS in Computer Engineering",
        "BS in Criminology",
        "BS in Customs Administration",
        "BS in Information Technology",
        "BS in Computer Science",
        "BS in Office Administration",
        "BS in Social Work",
        "Bachelors of Secondary Education",
        "Bachelors of Elementary Education"
    );

    if (!in_array($course, $validCourses)) {
        $error_message = "Invalid course selection";
    } else {
        $updateQuery = "UPDATE users SET 
            firstName='$firstName',
            lastName='$lastName',
            middleName='$middleName',
            course='$course',
            yearLevel='$yearLevel',
            email='$email',
            address='$address'
            WHERE username='$username'";

        if($conn->query($updateQuery) === TRUE){
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $result = $conn->query($query);
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    }
}

// Handle profile image upload
if(isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0){
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $imageFileType = strtolower(pathinfo($_FILES["profileImage"]["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . $username . "_" . time() . "." . $imageFileType;
    
    // Check if image file is a actual image or fake image
    if(getimagesize($_FILES["profileImage"]["tmp_name"]) !== false) {
        if(move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)){
            $updateImageQuery = "UPDATE users SET profileImage='$target_file' WHERE username='$username'";
            if($conn->query($updateImageQuery) === TRUE){
                $success_message = "Profile image updated successfully!";
                // Refresh user data
                $result = $conn->query($query);
                $user = $result->fetch_assoc();
            } else {
                $error_message = "Error updating profile image in database.";
            }
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    } else {
        $error_message = "File is not an image.";
    }
}

// Get active tab (view or edit)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'view';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
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
            background-attachment: fixed;
            background: linear-gradient(135deg, #000000, #1a1a1a);
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

        .header {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(166, 124, 82, 0.2);
        }

        .text-gray-600 {
            color: rgba(247, 241, 236, 0.8) !important;
        }
        .text-gray-800 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        span {
            color: #ffffff !important;
        }

        /* Add text color for gray-600 spans */
        .text-gray-600 span {
            color: rgba(247, 241, 236, 0.8) !important;
        }
        .sidebar ul li a span {
            color: #FFFFFF !important;
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
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.4), rgba(60, 46, 38, 0.4));
            border: 1px solid rgba(166, 124, 82, 0.2);
            transition: all 0.3s ease;
        }
        
        .dash-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.6), rgba(60, 46, 38, 0.6));
            border: 1px solid rgba(166, 124, 82, 0.3);
        }
        
        /* Update profile tab styles */
        .profile-tab {
            position: relative;
            z-index: 1;
            color: rgba(166, 124, 82, 0.7);
        }
        
        .profile-tab:hover {
            color: #A67C52;
        }
        
        .profile-tab.active {
            color: #A67C52;
        }
        
        .profile-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #A67C52;
            transition: width 0.3s ease;
        }
        
        .profile-tab:hover::after,
        .profile-tab.active::after {
            width: 100%;
        }
        
        /* Form styles */
        .form-input {
            background: rgba(71, 69, 69, 0.5);
            border: 1px solid rgba(88, 84, 84, 0.3);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background: rgba(29, 28, 28, 0.7);
            border-color: rgba(16, 16, 17, 0.8);
            box-shadow: 0 0 0 2px rgba(25, 25, 26, 0.3);
        }

        .sidebar i {
            color: #A67C52;
            transition: all 0.3s ease;
        }

        /* Add profile icon color */
        .profile-tab i {
            color: #A67C52 !important;
        }

        /* Add button gradient style */
        .btn-gradient {
            background: linear-gradient(135deg, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            border: 1px solid rgba(166, 124, 82, 0.2);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, rgba(29, 59, 42, 1), rgba(60, 46, 38, 1));
            border: 1px solid rgba(166, 124, 82, 0.4);
        }

        /* Add these styles for centering dash cards */
        .content-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Add style for rounded background divs to match header */
        .bg-primary-light\/30,
        .rounded-full.bg-purple-100\/50,
        .rounded-full.bg-blue-100\/50 {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            border: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Update icon colors in rounded divs */
        .bg-primary-light\/30 i,
        .rounded-full i {
            color: #A67C52 !important;
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
        
        .sidebar i {
            color: #A67C52;
            width: 20px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">
    <!-- Particle container -->
    <div id="particles-js" class="fixed inset-0 z-0"></div>
    
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 header z-50 border-b border-white/20 py-2 px-6">
        <h1 class="text-xl md:text-2xl font-bold text-center">Student Profile</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 glass border-r border-white/20 pt-16 z-40 overflow-y-auto">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
            <img src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/user_icon.jpg'; ?>" alt="User Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
            <div class="font-semibold text-center"><?php echo $_SESSION['username']; ?></div>
            <div class="text-sm opacity-75">Student</div>
        </div>
        
        <ul class="px-4">
            <li class="mb-1"><a href="dashboard.php" class="flex items-center py-2 px-4 rounded hover:bg/white/20 transition-colors">
                <i class="fas fa-tachometer-alt w-6" style="color: #A67C52;"></i> <span>Dashboard</span>
            </a></li>
            <li class="mb-1"><a href="profile.php" class="profile-tab flex items-center py-2 px-4 rounded bg-white/30 text-primary-dark font-semibold">
                <i class="fas fa-user w-6"></i> <span>Profile</span>
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
        <div class="content-container">
            <!-- Profile Card -->
            <div class="glass p-8 rounded-lg dash-card mb-6">
                <!-- Success/Error Messages -->
                <?php if(isset($success_message)): ?>
                    <div class="mb-4 p-3 bg-green-100/70 text-green-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <p><?php echo $success_message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="mb-4 p-3 bg-red-100/70 text-red-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="flex border-b border-white/30 mb-6">
                    <a href="profile.php?tab=view" class="profile-tab px-6 py-3 font-medium text-center <?php echo $activeTab == 'view' ? 'active' : ''; ?>">
                        <i class="fas fa-user mr-2"></i> View Profile
                    </a>
                    <a href="profile.php?tab=edit" class="profile-tab px-6 py-3 font-medium text-center <?php echo $activeTab == 'edit' ? 'active' : ''; ?>">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </a>
                </div>
                
                <?php if($activeTab == 'view'): ?>
                <!-- View Profile Tab -->
                <div class="flex flex-col items-center">
                    <!-- Profile Image -->
                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white/50 shadow-lg mb-6">
                        <img 
                            src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/user_icon.jpg'; ?>" 
                            alt="Profile" 
                            class="w-full h-full object-cover"
                        >
                    </div>
                    
                    <!-- Profile Name -->
                    <h2 class="text-2xl font-bold mb-1">
                        <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['middleName'] . ' ' . $user['lastName']); ?>
                    </h2>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($user['course'] . ' - Year ' . $user['yearLevel']); ?></p>
                    
                    <!-- Profile Information Grid -->
                    <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- ID Number -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-id-card text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ID Number</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['idNo']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Username -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-user text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Username</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-envelope text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['email'] ?: 'Not set'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Course -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-graduation-cap text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Course</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['course']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Year Level -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-layer-group text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Year Level</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['yearLevel']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Total Sessions -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-clock text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Sessions</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['session'] ?: '0'); ?></p>
                            </div>
                        </div>
                        
                        <?php if(!empty($user['address'])): ?>
                        <!-- Address (if available) -->
                        <div class="bg-white/20 rounded-lg p-4 flex items-center md:col-span-2">
                            <div class="bg-primary-light/30 rounded-full p-3 mr-3">
                                <i class="fas fa-map-marker-alt text-primary-dark"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Address</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($user['address']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Edit Profile Tab -->
                <div class="flex flex-col">
                    <!-- Profile Image with Upload Option -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white/50 shadow-lg">
                                <img 
                                    src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/user_icon.jpg'; ?>" 
                                    alt="Profile" 
                                    id="profile-preview"
                                    class="w-full h-full object-cover"
                                >
                            </div>
                        </div>
                        
                        <!-- Upload/Change Photo Button -->
                        <form method="POST" action="" enctype="multipart/form-data" class="flex flex-col items-center" id="image-form">
                            <input type="file" id="profileImage" name="profileImage" class="hidden" accept="image/*">
                            <button type="button" onclick="document.getElementById('profileImage').click();" 
                                class="btn-gradient text-white px-4 py-2 rounded-full text-sm font-medium mb-2 flex items-center">
                                <i class="fas fa-camera mr-2"></i> Change Photo
                            </button>
                            <button type="submit" id="upload-btn" class="hidden bg-primary-dark text-white transition-colors px-4 py-2 rounded-full text-sm font-medium flex items-center">
                                <i class="fas fa-upload mr-2"></i> Upload Photo
                            </button>
                        </form>
                    </div>
                    
                    <!-- Edit Profile Form -->
                    <form method="POST" action="" class="w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <!-- ID Number (Read Only) -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">ID Number</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                    <input type="text" value="<?php echo htmlspecialchars($user['idNo']); ?>" class="w-full pl-10 py-2 rounded-lg form-input bg-gray-100" readonly>
                                </div>
                            </div>
                            
                            <!-- Username (Read Only) -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Username</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full pl-10 py-2 rounded-lg form-input bg-gray-100" readonly>
                                </div>
                            </div>
                            
                            <!-- First Name -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">First Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="fName" value="<?php echo htmlspecialchars($user['firstName']); ?>" class="w-full pl-10 py-2 rounded-lg form-input" required>
                                </div>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Last Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="lName" value="<?php echo htmlspecialchars($user['lastName']); ?>" class="w-full pl-10 py-2 rounded-lg form-input" required>
                                </div>
                            </div>
                            
                            <!-- Middle Name -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Middle Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="mName" value="<?php echo htmlspecialchars($user['middleName']); ?>" class="w-full pl-10 py-2 rounded-lg form-input">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full pl-10 py-2 rounded-lg form-input" required>
                                </div>
                            </div>
                            
                            <!-- Course -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Course</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-graduation-cap"></i>
                                    </span>
                                    <select name="course" class="w-full pl-10 py-2 rounded-lg form-input appearance-none" required>
                                        <option value="BS in Accountancy" <?php echo ($user['course'] == 'BS in Accountancy') ? 'selected' : ''; ?>>BS in Accountancy</option>
                                        <option value="BS in Business Administration" <?php echo ($user['course'] == 'BS in Business Administration') ? 'selected' : ''; ?>>BS in Business Administration</option>
                                        <option value="BS in Computer Engineering" <?php echo ($user['course'] == 'BS in Computer Engineering') ? 'selected' : ''; ?>>BS in Computer Engineering</option>
                                        <option value="BS in Criminology" <?php echo ($user['course'] == 'BS in Criminology') ? 'selected' : ''; ?>>BS in Criminology</option>
                                        <option value="BS in Customs Administration" <?php echo ($user['course'] == 'BS in Customs Administration') ? 'selected' : ''; ?>>BS in Customs Administration</option>
                                        <option value="BS in Information Technology" <?php echo ($user['course'] == 'BS in Information Technology') ? 'selected' : ''; ?>>BS in Information Technology</option>
                                        <option value="BS in Computer Science" <?php echo ($user['course'] == 'BS in Computer Science') ? 'selected' : ''; ?>>BS in Computer Science</option>
                                        <option value="BS in Office Administration" <?php echo ($user['course'] == 'BS in Office Administration') ? 'selected' : ''; ?>>BS in Office Administration</option>
                                        <option value="BS in Social Work" <?php echo ($user['course'] == 'BS in Social Work') ? 'selected' : ''; ?>>BS in Social Work</option>
                                        <option value="Bachelors of Secondary Education" <?php echo ($user['course'] == 'Bachelors of Secondary Education') ? 'selected' : ''; ?>>Bachelors of Secondary Education</option>
                                        <option value="Bachelors of Elementary Education" <?php echo ($user['course'] == 'Bachelors of Elementary Education') ? 'selected' : ''; ?>>Bachelors of Elementary Education</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Year Level -->
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Year Level</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-layer-group"></i>
                                    </span>
                                    <select name="yearLevel" class="w-full pl-10 py-2 rounded-lg form-input appearance-none" required>
                                        <option value="1st Year" <?php echo ($user['yearLevel'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo ($user['yearLevel'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo ($user['yearLevel'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo ($user['yearLevel'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Address (Optional)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="w-full pl-10 py-2 rounded-lg form-input">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit and Cancel Buttons -->
                        <div class="flex justify-center gap-4">
                            <button type="submit" name="updateProfile" 
                                class="btn-gradient text-white px-6 py-2 rounded-lg font-medium flex items-center">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                            <a href="profile.php" 
                                class="btn-gradient text-white px-6 py-2 rounded-lg font-medium flex items-center">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Script for profile image preview and upload
        document.addEventListener('DOMContentLoaded', function() {
            const profileImage = document.getElementById('profileImage');
            const uploadBtn = document.getElementById('upload-btn');
            const preview = document.getElementById('profile-preview');
            
            if (profileImage) {
                profileImage.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            uploadBtn.classList.remove('hidden');
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
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
</body>
</html>