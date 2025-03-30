<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'user'){
    header("Location: index.php");
    exit();
}


// Add database connection for announcements
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

// Create announcements table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    date DATETIME NOT NULL,
    created_by VARCHAR(100) NOT NULL
)";

if($conn->query($createTable) !== TRUE) {
    $error = "Error creating announcements table: " . $conn->error;
}

// Get all announcements (already ordered by date DESC for most recent first)
$sql = "SELECT * FROM announcements ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
            display: flex;
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
            background: rgba(0, 0, 0, 0.3);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(166, 124, 82, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(166, 124, 82, 0.7);
        }
        
        /* Add h1 text color */
        h1 {
            color: #ffffff !important;
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

        /* Add text color for gray-600 spans */
        .text-gray-600 span {
            color: rgba(247, 241, 236, 0.8) !important;
        }

        /* Make sure sidebar spans use white text */
        .sidebar ul li a span {
            color: #FFFFFF !important;
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
            transition: all 0.3s ease;
        }

        /* Update rounded div backgrounds to match header */
        .bg-primary-light\/50,
        .bg-purple-100\/50,
        .bg-blue-100\/50 {
            background: linear-gradient(to right, rgba(29, 59, 42, 0.9), rgba(60, 46, 38, 0.9));
            border: 1px solid rgba(166, 124, 82, 0.2);
        }

        /* Update icon color in rounded divs */
        .bg-primary-light\/50 i,
        .bg-purple-100\/50 i,
        .bg-blue-100\/50 i {
            color: #A67C52 !important;
        }

        /* Add these styles for sidebar hover effects */
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
        <h1 class="text-xl md:text-2xl font-bold text-center">Student Dashboard</h1>
    </div>
    
    <!-- Sidebar -->
    <div class="fixed top-0 left-0 bottom-0 w-64 glass border-r border-white/20 pt-16 z-40 overflow-y-auto">
        <div class="flex flex-col items-center p-5 mb-5 border-b border-white/30">
        <img src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : './images/admin_icon.jpg'; ?>" alt="User Profile" class="w-20 h-20 rounded-full object-cover border-3 border-white/50 mb-3">
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

    <div class="glass p-5 rounded-lg dash-card mb-6">
            <div class="flex items-center">
                <div class="rounded-full bg-primary-light/50 p-4 mr-4">
                    <i class="fas fa-user" style="color: #A67C52;"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Welcome, <?php echo $_SESSION['username']; ?>!</h2>
                    <p class="text-sm text-gray-600">
                        Manage your student account and access sit-in lab features
                    </p>
                </div>
            </div>
        </div>
        <!-- Two-column grid for Announcements and Rules -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Announcements Section -->
            <div class="glass p-6 rounded-lg dash-card h-full">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-bullhorn mr-4" style="color: #A67C52;"></i> Announcements
                </h3>
                <div class="max-h-96 overflow-y-auto">
                    <?php if ($result && $result->num_rows > 0) { ?>
                        <?php while($row = $result->fetch_assoc()) { ?>
                            <div class="p-4 border-b border-white/30 hover:bg-white/10 transition-colors">
                                <div class="flex justify-between">
                                    <div class="font-semibold"><?php echo htmlspecialchars($row['created_by']); ?></div>
                                    <div class="text-sm opacity-75"><?php echo htmlspecialchars($row['date']); ?></div>
                                </div>
                                <div class="mt-2"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="p-4 text-center text-gray-600">
                            No announcements available at this time.
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Rules and Regulations Section -->
            <div class="glass p-6 rounded-lg dash-card h-full">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-book mr-4" style="color: #A67C52;"></i> Rules and Regulations
                </h3>
                <div class="max-h-96 overflow-y-auto">
                    <h4 class="text-center font-semibold mb-2">University of Cebu</h4>
                    <h5 class="text-center text-sm mb-4">COLLEGE OF INFORMATION & COMPUTER STUDIES</h5>
                    
                    <h4 class="font-bold mb-2">LABORATORY RULES AND REGULATIONS</h4>
                    <p class="mb-4 text-sm">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                    
                    <ol class="list-decimal pl-6 space-y-2 text-sm mb-4">
                        <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal pieces of equipment must be switched off.</li>
                        <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
                        <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing software are strictly prohibited.</li>
                        <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                        <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                        <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                        <li>Observe proper decorum while inside the laboratory.
                            <ul class="list-disc pl-6 mt-2 space-y-1">
                                <li>Do not get inside the lab unless the instructor is present.</li>
                                <li>All bags, knapsacks, and the likes must be deposited at the counter.</li>
                                <li>Follow the seating arrangement of your instructor.</li>
                                <li>At the end of class, all software programs must be closed.</li>
                                <li>Return all chairs to their proper places after using.</li>
                            </ul>
                        </li>
                        <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                        <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                        <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be subject to disciplinary action.</li>
                        <li>For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                        <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.</li>
                    </ol>
                    
                    <h4 class="font-bold mb-2">DISCIPLINARY ACTION</h4>
                    <ul class="list-disc pl-6 space-y-2 text-sm">
                        <li>First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</li>
                        <li>Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Vision Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex flex-col items-center text-center">
                    <div class="rounded-full bg-purple-100/50 p-4 mb-3">
                        <i class="fas fa-eye" style="color: #A67C52;"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">VISION</h3>
                    <p class="text-sm">
                        "Democratize quality education.<br>
                        Be the visionary and industry leader.<br>
                        Give hope and transform lives."
                    </p>
                </div>
            </div>
            
            <!-- Mission Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex flex-col items-center text-center">
                    <div class="rounded-full bg-blue-100/50 p-4 mb-3">
                        <i class="fas fa-bullseye" style="color: #A67C52;"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">MISSION</h3>
                    <p class="text-sm">
                        "University of Cebu offers affordable and quality education<br>
                        responsive to the demands of local and international communities."
                    </p>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="glass p-5 rounded-lg dash-card">
                <div class="flex flex-col h-full">
                    <h3 class="text-lg font-bold mb-3 text-center">Quick Actions</h3>
                    <div class="flex flex-col space-y-2 flex-grow justify-center">
                        <a href="reservation.php" class="flex items-center py-2 px-4 rounded bg-white/30 hover:bg-white/50 transition-colors">
                            <i class="fas fa-calendar-plus w-6" style="color: #A67C52;"></i>
                            <span>Make Reservation</span>
                        </a>
                        <a href="history.php" class="flex items-center py-2 px-4 rounded bg-white/30 hover:bg-white/50 transition-colors">
                            <i class="fas fa-history w-6" style="color: #A67C52;"></i>
                            <span>View History</span>
                        </a>
                        <a href="sit_in_rules.php" class="flex items-center py-2 px-4 rounded bg-white/30 hover:bg-white/50 transition-colors">
                            <i class="fas fa-info-circle w-6" style="color: #A67C52;"></i>
                            <span>Check Sit-In Rules</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Video Section -->
        <div class="glass p-6 rounded-lg dash-card">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <i class="fas fa-video text-primary-dark mr-2"></i> University Showcase
            </h3>
            <div class="aspect-w-16 aspect-h-9">
                <iframe 
                    class="w-full rounded-lg shadow-lg" 
                    style="aspect-ratio: 16/9;"
                    src="https://www.youtube.com/embed/IhsA6PZ2oy0" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="mt-4 text-sm text-center text-gray-600">
                Learn more about the University of Cebu and its facilities
            </div>
        </div>
    </div>
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