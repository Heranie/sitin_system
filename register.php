<?php 

include 'connect.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_POST['signUp'])){
    // Validate required fields
    $required_fields = ['idNo', 'fName', 'lName', 'course', 'yearLevel', 'username', 'email', 'password'];
    $errors = [];
    
    foreach($required_fields as $field) {
        if(!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    
    if(empty($errors)) {
        $idNo = trim($_POST['idNo']);
        $firstName = trim($_POST['fName']);
        $lastName = trim($_POST['lName']);
        $middleName = isset($_POST['mName']) ? trim($_POST['mName']) : '';
        $course = trim($_POST['course']);
        $yearLevel = trim($_POST['yearLevel']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $password = md5($password);

        // Check if ID Number already exists
        $checkId = "SELECT * FROM users WHERE idNo='$idNo'";
        $idResult = $conn->query($checkId);
        if($idResult->num_rows > 0){
            echo "<div class='error'>ID Number Already Exists!</div>";
            exit();
        }

        // Check if username already exists
        $checkUsername = "SELECT * FROM users WHERE username='$username'";
        $result = $conn->query($checkUsername);
        if($result->num_rows > 0){
            echo "<div class='error'>Username Already Exists!</div>";
            exit();
        }

        // Check if email already exists
        $checkEmail = "SELECT * FROM users WHERE email='$email'";
        $emailResult = $conn->query($checkEmail);
        if($emailResult->num_rows > 0){
            echo "<div class='error'>Email Already Exists!</div>";
            exit();
        }

        $insertQuery = "INSERT INTO users (idNo, firstName, lastName, middleName, course, yearLevel, username, email, password, session)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 30)";
                
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssssssss", $idNo, $firstName, $lastName, $middleName, $course, $yearLevel, $username, $email, $password);
        
        if($stmt->execute()){
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['idNo'] = $idNo;
            $_SESSION['success_message'] = "You have successfully registered!";
            header("location: dashboard.php");
            exit();
        } else {
            echo "<div class='error'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        foreach($errors as $error) {
            echo "<div class='error'>$error</div>";
        }
    }
}

if(isset($_POST['signIn'])){
    if(isset($_POST['username']) && isset($_POST['password'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = md5($_POST['password']);
        
        // First check if it's a regular user
        $sql = "SELECT * FROM users WHERE username=? AND password=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result && $result->num_rows > 0){
            session_start();
            $row = $result->fetch_assoc();
            $_SESSION['username'] = $row['username'];
            $_SESSION['idNo'] = $row['idNo'];
            $_SESSION['user_type'] = 'user';
            header("Location: dashboard.php");
            exit();
        } else {
            // If not a regular user, check if admin
            $stmt->close();
            
            // Check if admin table exists
            $tableExists = false;
            $checkTable = "SHOW TABLES LIKE 'admins'";
            $tableResult = $conn->query($checkTable);
            if($tableResult && $tableResult->num_rows > 0) {
                $tableExists = true;
            }
            
            if($tableExists) {
                // Special case for admin login
                if($username == 'admin' && $password == md5('admin123')) {
                    // Create session for admin
                    session_start();
                    $_SESSION['username'] = 'admin';
                    $_SESSION['user_type'] = 'admin';
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    echo "<div class='error'>Incorrect username or password</div>";
                }
            } else {
                // Admin table doesn't exist
                if($username == 'admin') {
                    // Username is admin, redirect to create admin table
                    header("Location: insert_admin.php");
                    exit();
                } else {
                    echo "<div class='error'>Incorrect username or password</div>";
                }
            }
        }
    } else {
        echo "<div class='error'>Username and Password are required.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login/Register</title>
    <style>
        .error {
            color: #ff0000;
            background-color: #ffe6e6;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ff0000;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            display: block;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Register</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="idNo">ID Number *</label>
                <input type="text" name="idNo" id="idNo" required>
            </div>
            <div class="form-group">
                <label for="fName">First Name *</label>
                <input type="text" name="fName" id="fName" required>
            </div>
            <div class="form-group">
                <label for="lName">Last Name *</label>
                <input type="text" name="lName" id="lName" required>
            </div>
            <div class="form-group">
                <label for="mName">Middle Name</label>
                <input type="text" name="mName" id="mName">
            </div>
            <div class="form-group">
                <label for="course">Course *</label>
                <select name="course" id="course" required>
                    <option value="">Select Course</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                    <option value="BSIS">BSIS</option>
                </select>
            </div>
            <div class="form-group">
                <label for="yearLevel">Year Level *</label>
                <select name="yearLevel" id="yearLevel" required>
                    <option value="" disabled selected>Select Year Level</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="signUp">Sign Up</button>
        </form>
    </div>

    <div class="form-container">
        <h2>Login</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="login_username">Username</label>
                <input type="text" name="username" id="login_username" required>
            </div>
            <div class="form-group">
                <label for="login_password">Password</label>
                <input type="password" name="password" id="login_password" required>
            </div>
            <button type="submit" name="signIn">Sign In</button>
        </form>
    </div>
</body>
</html>