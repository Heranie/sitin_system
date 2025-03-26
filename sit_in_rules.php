<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

// Get user type for conditional display
$userType = $_SESSION['user_type'];

include 'connect.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sit-In Rules</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> <!-- Font Awesome for icons -->
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            background-image: url('img/l.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            width: 100vw;
        }
        .header {
            background: linear-gradient(to right, rgba(178, 166, 204, 0.8), rgba(217, 230, 255, 0.8));
            color: #000;
            padding: 10px;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .sidebar {
            background: linear-gradient(to bottom, rgba(217, 230, 255, 0.9), rgba(178, 166, 204, 0.9));
            width: 250px;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(245, 183, 242, 0.1);
            height: 100vh;
            position: fixed;
            top: 50px;
            left: 0;
            overflow-y: auto;
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
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .sidebar ul li a i {
            margin-right: 10px;
        }
        .sidebar ul li a:hover {
            background-color: rgba(178, 166, 204, 0.8);
            color: #fff;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 70px;
            flex-grow: 1;
            border-radius: 10px;
        }
        .announcement {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                        0 10px 20px rgba(0, 0, 0, 0.1),
                        0 15px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        .announcement h2 {
            margin: 0 0 10px;
        }
        .announcement p {
            margin: 0;
        }
        .announcement .date {
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sit-In Rules</h1>
    </div>
    <div class="sidebar">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i>Announcements</a></li>
            <li><a href="sit_in_rules.php"><i class="fas fa-book"></i>Sit-In Rules</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i>History</a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-alt"></i>Reservation</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Log Out</a></li>
        </ul>
    </div>
</body>
</html>