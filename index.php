<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-In Monitoring System</title>
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
    <link rel="stylesheet" href="css/spiral.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
            background: transparent;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            position: relative;
            z-index: 1;
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        /* Adjust logo sizes */
        .logo-container img {
            height: 60px;
            width: auto;
        }

        /* Adjust title spacing and size */
        .title-container {
            margin-bottom: 1.5rem;
        }

        .title-container h1 {
            font-size: 2rem;
            line-height: 1.2;
        }

        /* Updated cursor trail styles */
        .cursor-trail {
            width: 12px;
            height: 12px;
            background: #A67C52;
            border-radius: 50%;
            position: fixed;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 9999;
            mix-blend-mode: screen;
            transition: all 0.12s ease;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 15px #A67C52,
                       0 0 30px #A67C52,
                       0 0 45px #A67C52;
            opacity: 0.8;
        }

        /* Button and link styles */
        button[type="submit"] {
            background: linear-gradient(to right, #1D3B2A, #3C2E26);
            color: #A67C52;
            transition: all 0.3s ease;
        }

        button[type="submit"]:hover {
            background: linear-gradient(to right, #3C2E26, #1D3B2A);
            color: #A67C52;
            transform: translateY(-2px);
        }

        .account-link {
            color: #A67C52;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .account-link:hover {
            color: #5A6B4D;
            text-shadow: 0 0 10px rgba(166, 124, 82, 0.4);
        }

        .forgot-password {
            color: rgba(166, 124, 82, 0.8);
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: #A67C52;
            text-shadow: 0 0 10px rgba(166, 124, 82, 0.4);
        }

        .text-gray-600 {
            color: rgba(166, 124, 82, 0.8) !important;
        }

        /* Updated text colors for account prompts */
        .account-prompt {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
    </style>
</head>
<body class="font-sans text-gray-800 flex items-center justify-center p-6">
    <!-- Keep only one cursor trail -->
    <div class="cursor-trail"></div>

    <!-- UI Controls -->
    <div class="ui">
        <p class="zoom"><span class="zoom zoomin">+</span><span class="zoom zoomout">-</span></p>
        <p class="zoomlevel"><span class="percent">100</span> % - (<span class="width"></span>px)(<span class="height"></span>px)</p>
        <p>Dead: <span class="dead">0</span></p>
        <p>Alive: <span class="alive">0</span></p>
        <p>Drawn: <span class="drawn">0</span></p>
        <p><span class="fps">0</span> FPS</p>
        <a class="save" href="" download="capture.png">Save</a>
    </div>

    <div class="max-w-4xl w-full">

        <!-- Forms Container -->
        <div class="glass">
            <!-- Title Container -->
            <div class="title-container">
                <h1 class="text-2xl md:text-3xl font-bold text-center">
                    <span class="text-[#A67C52]">CCS</span> 
                    <span class="text-white/90">Sit-In</span><br>
                    <span class="text-[#5A6B4D]">Monitoring</span> 
                    <span class="text-white/90">System</span>
                </h1>
            </div>

            <!-- Logo Container -->
            <div class="logo-container flex justify-center gap-6 mb-6">
                <img src="logo/ccs.png" alt="CCS Logo" class="h-16 w-auto">
                <img src="logo/uc.png" alt="UC Logo" class="h-16 w-auto">
            </div>

            <!-- Register Form -->
            <div id="signup" class="hidden">
                <form method="post" action="register.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="input-group">
                        <input type="text" name="idNo" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="ID Number" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="lName" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Last Name" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="fName" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="First Name" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="mName" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Middle Name">
                    </div>
                    <div class="input-group">
                        <input list="courses" name="course" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Course" required>
                        <datalist id="courses">
                            <option value="BS in Information Technology">
                            <option value="BS in Computer Science">
                            <option value="BS in Information Systems">
                        </datalist>
                    </div>
                    <div class="input-group">
                        <input type="text" name="yearLevel" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Year Level" required>
                    </div>
                    <div class="input-group md:col-span-2">
                        <input type="email" name="email" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Email" required>
                    </div>
                    <div class="input-group md:col-span-2">
                        <input type="text" name="username" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Username" required>
                    </div>
                    <div class="input-group md:col-span-2">
                        <input type="password" name="password" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Password" required>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="signUp" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-2 rounded-lg hover:opacity-90 transition-opacity">
                            Create Account
                        </button>
                        <p class="text-center mt-4">
                            <span class="account-prompt">Already have an account?</span>
                            <a href="#" onclick="toggleForms()" class="account-link">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Login Form -->
            <div id="signIn">
                <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg mb-4">
                    <?php if($_GET['error'] == 1): ?>
                        Incorrect username or password
                    <?php elseif($_GET['error'] == 2): ?>
                        Username and Password are required
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="post" action="login.php" class="space-y-4">
                    <div class="input-group">
                        <input type="text" name="username" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" class="w-full px-4 py-2 rounded-lg focus:outline-none" placeholder="Password" required>
                    </div>
                    <div class="flex justify-end">
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>
                    <button type="submit" name="signIn" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-2 rounded-lg hover:opacity-90 transition-opacity">
                        Sign In
                    </button>

                    <p class="text-center mt-4">
                        <span class="account-prompt">Don't have an account?</span>
                        <a href="#" onclick="toggleForms()" class="account-link">Sign Up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
    <script src="js/spiral.js"></script>
    <script>
        // Updated cursor trail animation
        document.addEventListener('DOMContentLoaded', function() {
            const trail = document.querySelector('.cursor-trail');
            let mouseX = 0, mouseY = 0;
            let currentX = 0, currentY = 0;

            window.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            function updateTrail() {
                const ease = 0.2;
                
                currentX += (mouseX - currentX) * ease;
                currentY += (mouseY - currentY) * ease;

                trail.style.transform = `translate(${currentX}px, ${currentY}px)`;
                requestAnimationFrame(updateTrail);
            }

            updateTrail();
        });

        // Existing toggleForms function
        function toggleForms() {
            const signUp = document.getElementById('signup');
            const signIn = document.getElementById('signIn');
            if (signUp.classList.contains('hidden')) {
                signUp.classList.remove('hidden');
                signIn.classList.add('hidden');
            } else {
                signUp.classList.add('hidden');
                signIn.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>