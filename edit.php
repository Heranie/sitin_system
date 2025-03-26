<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'user'){
    header("Location: index.php");
    exit();
}

include 'connect.php';

$username = $_SESSION['username'];
// Debug session data
error_log("Session data - Username: $username, ID: " . (isset($_SESSION['idNo']) ? $_SESSION['idNo'] : 'Not set'));

$query = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$user = $result->fetch_assoc();

if (!$user) {
    die("User not found!");
}

// Debug user data
error_log("User data from DB: " . print_r($user, true));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['fName'];
    $lastName = $_POST['lName'];
    $middleName = $_POST['mName'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $yearLevel = $_POST['yearLevel'];

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
        echo "Invalid course selection";
        exit();
    }

    // Handle profile image upload
    if(isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0){
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $imageFileType = strtolower(pathinfo($_FILES["profileImage"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . $username . "_" . time() . "." . $imageFileType;
        
        if(getimagesize($_FILES["profileImage"]["tmp_name"]) !== false) {
            if(move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)){
                $updateQuery = "UPDATE users SET 
                    firstName='$firstName',
                    lastName='$lastName',
                    middleName='$middleName',
                    course='$course',
                    yearLevel='$yearLevel',
                    email='$email',
                    profileImage='$target_file'
                    WHERE username='$username'";
            }
        }
    } else {
        $updateQuery = "UPDATE users SET 
            firstName='$firstName',
            lastName='$lastName',
            middleName='$middleName',
            course='$course',
            yearLevel='$yearLevel',
            email='$email'
            WHERE username='$username'";
    }

    $stmt = $conn->prepare($updateQuery);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            background-image: url('img/l.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
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
            font-family: 'Roboto', sans-serif;
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
            font-family: 'Roboto', sans-serif;
            z-index: 999;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin-top: 20px;
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
            width: 20px;
            text-align: center;
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
            width: calc(100% - 290px);
        }
        .edit-container {
            max-width: 450px;
            margin: 15px auto;
            background: linear-gradient(to bottom, rgba(253, 214, 230, 0.7), rgba(178, 166, 204, 0.7));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1),
                        0 10px 20px rgba(0, 0, 0, 0.1),
                        0 15px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        .profile-image-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .profile-image-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 12px;
            border: 3px solid rgba(255, 255, 255, 0.5);
        }
        .file-input-container {
            text-align: center;
        }
        .change-photo-btn {
            background: rgba(178, 166, 204, 0.9);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            display: inline-block;
            font-size: 0.9em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 22px;
            position: relative;
            color: #666; /* Match the color of the ID form group */
        }
        .form-group label {
            display: block;
            color: #666; /* Match the color of the ID form group */
            font-size: 0.85em;
            font-weight: 500;
            position: absolute;
            top: -20px;
            left: 2px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid rgba(178, 166, 204, 0.5);
            background: rgba(255, 255, 255, 0.8);
            font-size: 0.9em;
            box-sizing: border-box;
            height: 34px;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(178, 166, 204, 0.8);
            box-shadow: 0 0 5px rgba(178, 166, 204, 0.3);
        }
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 1em;
            padding-right: 35px;
            cursor: pointer;
        }
        .readonly-field {
            background: rgba(255, 255, 255, 0.5) !important;
            border: 2px solid rgba(178, 166, 204, 0.5) !important;
            color: #666 !important;
            font-weight: 500;
        }
        .form-group i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: rgba(178, 166, 204, 0.8);
        }
        .form-group input,
        .form-group select {
            padding-left: 35px; /* Adjust padding to make space for the icon */
        }
        .btn-container {
            text-align: center;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            background: rgba(178, 166, 204, 0.9);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
            min-width: 120px;
        }
        .btn:hover {
            background: rgba(178, 166, 204, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .cancel-btn {
            background: rgba(244, 67, 54, 0.9);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
            min-width: 120px;
        }
        .cancel-btn:hover {
            background: rgba(244, 67, 54, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9em;
        }
        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Edit Profile</h1>
    </div>
    
    <div class="sidebar">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="announcement.php"><i class="fas fa-bullhorn"></i>Announcement</a></li>
            <li><a href="sit-in-rules.php"><i class="fas fa-clipboard-list"></i>Sit-In Rules</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i>History</a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-alt"></i>Reservation</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="edit-container">
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="profile-image-container">
                    <img src="<?php echo !empty($user['profileImage']) ? htmlspecialchars($user['profileImage']) : 'img/default-profile.jpg'; ?>" 
                         alt="Profile Image" id="preview-image">
                    <div class="file-input-container">
                        <input type="file" name="profileImage" id="profileImage" accept="image/*" onchange="previewImage(this)" style="display: none;">
                        <button type="button" class="change-photo-btn" onclick="document.getElementById('profileImage').click();">
                            <i class="fas fa-camera"></i> Change Profile Picture
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>ID Number</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="idNo" value="<?php echo htmlspecialchars($user['idNo']); ?>" class="readonly-field" readonly>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>First Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="fName" value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="lName" value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="mName" value="<?php echo htmlspecialchars($user['middleName']); ?>">
                </div>

                <div class="form-group">
                    <label>Course</label>
                    <i class="fas fa-graduation-cap"></i>
                    <select name="course" required>
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
                </div>

                <div class="form-group">
                    <label>Year Level</label>
                    <i class="fas fa-layer-group"></i>
                    <select name="yearLevel" required>
                        <option value="1st Year" <?php echo ($user['yearLevel'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2nd Year" <?php echo ($user['yearLevel'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3rd Year" <?php echo ($user['yearLevel'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4th Year" <?php echo ($user['yearLevel'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>

                <div class="btn-container">
                    <button type="submit" name="updateProfile" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="profile.php" class="btn cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>