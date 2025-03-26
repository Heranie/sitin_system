<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

// Get user type for conditional display
$userType = $_SESSION['user_type'];

include 'connect.php';

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

// Get all announcements
$sql = "SELECT * FROM announcements ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Announcements</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> <!-- Font Awesome for icons -->
    <style>
        /* Reset and General Styles */
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-image: url('img/l.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr;
            grid-template-columns: 200px 1fr;
            grid-template-areas: 
                "header header"
                "sidebar content";
        }

        /* Header Style */
        .header {
            grid-area: header;
            background: linear-gradient(to right, rgba(178, 166, 204, 0.8), rgba(217, 230, 255, 0.8));
            color: #000;
            padding: 10px 20px;
            text-align: center;
            position: fixed;
            width: 100%;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        /* Sidebar Style */
        .sidebar {
            grid-area: sidebar;
            background: linear-gradient(to bottom, rgba(217, 230, 255, 0.9), rgba(178, 166, 204, 0.9));
            width: 200px;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(245, 183, 242, 0.1);
            height: calc(100vh - 60px);
            position: fixed;
            left: 0;
            top: 60px; /* Adjusted to be below the header */
            overflow-y: auto;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 25px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            display: flex;
            align-items: center;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar ul li a i {
            margin-right: 15px;
        }

        .sidebar ul li a:hover {
            background-color: rgba(178, 166, 204, 0.8);
            color: #fff;
        }

        /* Content Area */
        .content {
            grid-area: content;
            padding: 80px 20px 20px 20px; /* Adjusted to avoid overlap with sidebar and header */
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Announcement Box */
        .announcement-box, .container {
            width: 100%;
            background: linear-gradient(to bottom, rgba(253, 214, 230, 0.7), rgba(178, 166, 204, 0.7));
            border: 1px solid rgba(178, 166, 204, 0.8);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-y: auto;
            max-height: 400px;
            border-radius: 5px;
            margin: 0 auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .announcement-box:hover, .container:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .announcement-header, .container .header {
            background: linear-gradient(to right, rgba(178, 166, 204, 0.8), rgba(217, 230, 255, 0.8));
            color: #000;
            padding: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 5px 5px 0 0;
        }

        .announcement-item {
            padding: 25px;
            border-bottom: 1px solid #eee;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-date {
            font-weight: bold;
            color: #333;
        }

        .announcement-content {
            margin-top: 15px;
            color: #555;
        }

        /* Responsive Design for Small Screens */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "header"
                    "content";
            }

            .sidebar {
                display: none; /* Hide sidebar on small screens */
            }

            .header {
                width: 100%;
                left: 0;
            }

            .content {
                grid-template-columns: 1fr;
                padding: 80px 20px 20px 20px; /* Adjust padding for small screens */
            }
        }

        /* Add spacing between elements in the rules and regulation container */
        .container h3, .container h4 {
            margin-top: 20px; 
        }

        .container h3, .container h5 {
            text-align: center;
        }

        /* Add spacing between list items */
        .container ol li, .container ul li {
            margin-bottom: 10px;
        }

        /* Add spacing between h4 and list items */
        .container h4 + ul, .container h4 + ol {
            margin-top: 20px;
        }

        /* Add spacing between h4 and p, and li */
        .container h4 + p, .container h4 + li {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Announcements</h1>
    </div>

    <div class="sidebar">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="sit_in_rules.php"><i class="fas fa-book"></i> Sit-In Rules</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-alt"></i> Reservation</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="announcement-box">
            <div class="announcement-header">
                📢 Announcement
            </div>
            <?php if ($result->num_rows > 0) { ?>
                <?php while($row = $result->fetch_assoc()) { ?>
                    <div class="announcement-item">
                        <div class="announcement-date"><?php echo $row['created_by']; ?> | <?php echo $row['date']; ?></div>
                        <div class="announcement-content"><?php echo $row['content']; ?></div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="announcement-item">
                    <div class="announcement-date">No announcements found.</div>
                </div>
            <?php } ?>
        </div>
        <div class="container">
            <div class="header">Rules and Regulation</div>
            <h3 class="text-center mt-3">University of Cebu</h3>
            <h5 class="text-center">COLLEGE OF INFORMATION & COMPUTER STUDIES</h5>
            <h4 class="mt-4"><strong>LABORATORY RULES AND REGULATIONS</strong></h4>
            <p style="margin-top: 20px;">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
            <ol style="margin-top: 20px;">
                <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal pieces of equipment must be switched off.</li>
                <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
                <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing software are strictly prohibited.</li>
                <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                <li>Observe proper decorum while inside the laboratory.</li>
                      <ul>
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
            <h4 class="mt-4"><strong>DISCIPLINARY ACTION</strong></h4>
            <ul>
                <li>First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</li>
                <li>Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
            </ul>
        </div>
    </div>
</body>
</html>
