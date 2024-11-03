<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        setMessage('Please fill in all fields', 'danger');
    } elseif ($password !== $confirm_password) {
        setMessage('Passwords do not match', 'danger');
    } else {
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                setMessage('Email already exists', 'danger');
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    setMessage('Registration successful! Please login.', 'success');
                    redirect('/pages/auth/login.php');
                }
            }
        } catch(PDOException $e) {
            setMessage('Registration failed', 'danger');
            error_log($e->getMessage());
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
    <title>Sign Up - Create Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="signup.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="signup-container">
                    <div class="card">
                        <div class="card-header">
                            <h3>Create Account</h3>
                        </div>
                        <div class="card-body">
                            <?php echo getMessage(); ?>
                            
                            <form method="POST" action="" id="signupForm" novalidate>
                                <div class="form-group mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="invalid-feedback">Please choose a username.</div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password is required.</div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn-signup">
                                        <span>Create Account</span>
                                        <i class="fas fa-user-plus ms-2"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="login-link">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('input');
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Real-time password match validation
            confirmPassword.addEventListener('input', function() {
                if (this.value !== password.value) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Check if passwords match
                if (password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('is-invalid');
                    e.preventDefault();
                    return;
                }

                // If form is valid, show loading state
                if (form.checkValidity()) {
                    const submitBtn = form.querySelector('.btn-signup');
                    const btnText = submitBtn.querySelector('span');
                    const btnIcon = submitBtn.querySelector('i');
                    
                    submitBtn.disabled = true;
                    btnText.textContent = 'Creating Account...';
                    btnIcon.className = 'fas fa-spinner fa-spin ms-2';
                }

                form.classList.add('was-validated');
            });

            // Reset validation on input
            form.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            });
        });
    </script>

    <!-- <?php require_once '../../includes/footer.php'; ?> -->
</body>
</html>