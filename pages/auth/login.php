<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        setMessage('Please fill in all fields', 'danger');
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('/pages/index.php');
        } else {
            setMessage('Invalid credentials', 'danger');
        }
    }
}

require_once '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elegant Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="login.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-container">
                    <div class="login-header">
                        <h3>Welcome Back</h3>
                    </div>
                    
                    <div class="login-body">
                        <?php echo getMessage(); ?>
                        
                        <form method="POST" action="" id="loginForm">
                            <div class="form-floating">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email">Email Address</label>
                            </div>
                            
                            <div class="form-floating">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <span>Sign In</span>
                                <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </form>
                        
                        <div class="signup-link">
                            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                            <a href="forgot-password.php">Forgot your password?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation and animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                const button = document.querySelector('.btn-login');
                button.classList.add('shake');
                setTimeout(() => button.classList.remove('shake'), 500);
            }
        });

        // Floating label animation
        document.querySelectorAll('.form-floating input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
            
            // Check on page load for pre-filled values
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    </script>

    <!-- <?php require_once '../../includes/footer.php'; ?> -->
</body>
</html>