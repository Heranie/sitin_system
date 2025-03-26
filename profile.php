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

if(isset($_POST['updateProfile'])){
    $firstName = $_POST['fName'];
    $lastName = $_POST['lName'];
    $middleName = $_POST['mName'];
    $course = $_POST['course'];
    $yearLevel = $_POST['yearLevel'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $updateQuery = "UPDATE users SET firstName='$firstName', lastName='$lastName', middleName='$middleName', course='$course', yearLevel='$yearLevel', email='$email', address='$address' WHERE username='$username'";
    if($conn->query($updateQuery) === TRUE){
        echo "Profile updated successfully!";
        header("Refresh:0");
    } else {
        echo "Error: " . $conn->error;
    }
}

if(isset($_FILES['profileImage'])){
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
                echo "<script>alert('Profile image updated successfully!');</script>";
                header("Refresh:0");
            } else {
                echo "<script>alert('Error updating profile image in database.');</script>";
            }
        } else {
            echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
        }
    } else {
        echo "<script>alert('File is not an image.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Information</title>
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
            background-attachment: fixed;
            height: 110vh;
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
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            background: linear-gradient(to bottom, rgba(253, 214, 230, 0.7), rgba(178, 166, 204, 0.7));
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                        0 10px 20px rgba(0, 0, 0, 0.1),
                        0 15px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        .profile-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
            text-align: left;
        }
        .info-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
        }
        .info-item i {
            margin-right: 15px;
            font-size: 1.2em;
            color: rgba(0, 0, 0, 0.7);
        }
        .info-item .label {
            font-weight: bold;
            margin-right: 10px;
            min-width: 100px;
        }
        .info-item .value {
            flex-grow: 1;
        }
        .change-photo-btn {
            background: rgba(178, 166, 204, 0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            display: inline-block;
            margin-top: 15px;
            transition: all 0.3s ease;
            border: none;
        }
        .change-photo-btn:hover {
            background: rgba(178, 166, 204, 1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Information</h1>
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
    <div class="content">
        <div class="profile-container">
            <div class="profile-image-container">
                <img src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/default-profile.jpg'; ?>" alt="Profile Image">
            </div>
            <h2><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></h2>
            
            <a href="edit.php" class="change-photo-btn">
                <i class="fas fa-edit"></i> Edit Profile
            </a>

            <div class="profile-info">
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <span class="label">ID Number:</span>
                    <span class="value"><?php echo htmlspecialchars($user['idNo']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span class="label">Username:</span>
                    <span class="value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="label">Course:</span>
                    <span class="value"><?php echo htmlspecialchars($user['course']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-layer-group"></i>
                    <span class="label">Year Level:</span>
                    <span class="value"><?php echo htmlspecialchars($user['yearLevel']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span class="label">Sessions:</span>
                    <span class="value"><?php echo htmlspecialchars($user['session']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('profileImage').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('submitPhoto').style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>