<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'user'){
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> <!-- Font Awesome for icons -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap'); /* Import Google Fonts */

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif; /* Apply font to body */
            display: flex;
            background-image: url('img/l.png'); /* Ensure the path is correct */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 120vh;
            width: 100vw;
        }
        .header {
            background: linear-gradient(to right, rgba(178, 166, 204, 0.8), rgba(217, 230, 255, 0.8)); /* Gradient background */
            color: #000; /* Change font color to black */
            padding: 10px;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            font-family: 'Roboto', sans-serif; 
        }
        .sidebar {
            background: linear-gradient(to bottom, rgba(217, 230, 255, 0.9), rgba(178, 166, 204, 0.9)); /* Gradient background */
            width: 250px;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(245, 183, 242, 0.1);
            height: 100vh;
            position: fixed;
            top: 50px;
            left: 0;
            overflow-y: auto;
            font-family: 'Roboto', sans-serif; /* Apply font to sidebar */
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li {
            margin: 15px 0;
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease; /* Add transition for hover effect */
        }
        .sidebar ul li a i {
            margin-right: 10px; /* Space between icon and text */
        }
        .sidebar ul li a:hover {
            background-color: rgba(178, 166, 204, 0.8); /* Match hover color with background */
            color: #fff;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 60px; /* Adjust for header height */
            flex-grow: 1;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif; /* Apply new font to content */
        }
        .box {
            width: 30%;
            border-width: 10px;
            border-image: linear-gradient(to bottom, rgba(178, 166, 204, 0.8), rgba(253, 214, 230, 0.8)); /* Gradient border */
            border-image-slice: 1;
            border-style: dashed;
            height: 250px;
            background: linear-gradient(to bottom, rgba(253, 214, 230, 0.7), rgba(178, 166, 204, 0.7)); /* Gradient background */
            margin: 15px;
            padding: 10px;
            float: left;
            text-align: center;
            backdrop-filter: blur(10px); /* Glassmorphism effect */
            -webkit-backdrop-filter: blur(10px);
            transition: transform 0.3s ease; /* Add transition for hover effect */
            opacity: 0.9;
            font-family: 'Roboto', sans-serif; /* Apply font to box */
        }
        .box:hover {
            transform: scale(1.05); /* Scale up the box on hover */
        }
        .form-container {
            margin-bottom: 20px;
        }
        .form-container h2 {
            margin-bottom: 10px;
        }
        .form-container .input-group {
            margin-bottom: 15px;
        }
        .form-container .input-group label {
            margin-bottom: 5px;
        }
        .form-container .input-group input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .form-container .btn {
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container .btn:hover {
            background-color: #555;
        }
        .video-container {
            margin-top: 20px;
        }
        .video-container h2 {
            margin-bottom: 10px;
        }
        .video-container iframe {
            width: 100%;
            height: 315px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                        0 10px 20px rgba(0, 0, 0, 0.1),
                        0 15px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard</h1>
    </div>
    <div class="sidebar">
        <ul>
            <li><a href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i>Announcements</a></li>
            <li><a href="sit_in_rules.php"><i class="fas fa-book"></i>Sit-In Rules</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i>History</a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-alt"></i>Reservation</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i>Feedback</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Log Out</a></li>
        </ul>
    </div>
    <div class="content">
        <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
        <p>This is your dashboard where you can manage your profile, view announcements, and access other features.</p>
        
        <div class="box">
            VISION
            <br><br>
            <p>“Democratize quality education.<br><br> Be the visionary and industry leader.<br><br> Give hope and transform lives.”
            </p>
        </div>

        <div class="box">
            MISSION
            <br><br>
            <p>“University of Cebu offers affordable and quality education<br><br> responsive to the demands of local and international communities.”
            </p>
        </div>

        <div class="video-container">
            <iframe src="https://www.youtube.com/embed/IhsA6PZ2oy0" allowfullscreen></iframe>
        </div>
    </div>
</body>
</html>