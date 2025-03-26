<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .or {
            text-align: center;
            margin: 15px 0;
            color: #666;
        }
        .icons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
        }
        .icons i {
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
            padding: 10px;
        }
        .icons .fa-google:hover {
            color: #DB4437;
        }
        .icons .fa-facebook:hover {
            color: #4267B2;
        }
        .recover {
            text-align: right;
            margin: 10px 0;
        }
        .recover a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        .recover a:hover {
            color: #4CAF50;
            text-decoration: underline;
        }
        .toggle-text {
            text-align: center;
            margin-top: 15px;
            color: #666;
        }
        .toggle-text a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        .toggle-text a:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1 class="page-title">
        <span class="highlight c1">CCS</span> Sit-In <span class="highlight c2">Monitoring</span> System
    </h1>
    <div class="container" id="signup" style="display:none;">
        <div class="logo-container">
            <img src="logo/ccs.png" alt="Logo 2" class="logo-left">
            <img src="logo/uc.png" alt="Logo 1" class="logo-right">
        </div>
        <h1 class="form-title">Register</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="idNo" id="idNo" placeholder="ID Number" required>
                <label for="idNo">ID Number</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" id="lName" placeholder="Last Name" required>
                <label for="lName">Last Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" id="fName" placeholder="First Name" required>
                <label for="fName">First Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="mName" id="mName" placeholder="Middle Name">
                <label for="mName">Middle Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-book"></i>
                <input list="courses" name="course" id="course" placeholder="Course" required>
                <datalist id="courses">
                    <option value="BS in Information Technology">
                    <option value="BS in Computer Science">
                    <option value="BS in Information Systems">
                </datalist>
                <label for="course">Course</label>
            </div>
            <div class="input-group">
                <i class="fas fa-layer-group"></i>
                <input type="text" name="yearLevel" id="yearLevel" placeholder="Year Level" required>
                <label for="yearLevel">Year Level</label>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" name="signUp" class="btn">Sign Up</button>
            <p class="toggle-text">Already have an account? <a href="#" onclick="toggleForms()">Sign In</a></p>
        </form>
    </div>

    <div class="container" id="signIn">
        <div class="logo-container">
            <img src="logo/ccs.png" alt="Logo 2" class="logo-left">
            <img src="logo/uc.png" alt="Logo 1" class="logo-right">
        </div>
        <h1 class="form-title">Sign In</h1>
        <?php if(isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="error">Incorrect username or password</div>
        <?php elseif(isset($_GET['error']) && $_GET['error'] == 2): ?>
        <div class="error">Username and Password are required</div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="login_username" placeholder="Username" required>
                <label for="login_username">Username</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="login_password" placeholder="Password" required>
                <label for="login_password">Password</label>
            </div>
            <p class="recover">
                <a href="#">Recover Password</a>
            </p>
            <button type="submit" name="signIn" class="btn">Sign In</button>
            <p class="or">
                ----------or--------
            </p>
            <div class="icons">
                <i class="fab fa-google"></i>
                <i class="fab fa-facebook"></i>
            </div>
            <p class="toggle-text">Don't have an account? <a href="#" onclick="toggleForms()">Sign Up</a></p>
        </form>
    </div>

    <script>
    function toggleForms() {
        var signUp = document.getElementById('signup');
        var signIn = document.getElementById('signIn');
        if (signUp.style.display === 'none') {
            signUp.style.display = 'block';
            signIn.style.display = 'none';
        } else {
            signUp.style.display = 'none';
            signIn.style.display = 'block';
        }
    }
    </script>
</body>
</html>