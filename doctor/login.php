<?php
// doctor/login.php - Doctor authentication
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $message = 'Email and password are required.';
    } else {
        $result = doctor_login($conn, $email, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - OvCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(99, 102, 241, 0.4);
            z-index: -2;
        }
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
            position: relative;
            z-index: 1;
            margin-left: 33%;
            margin-right: 5%;
        }
        .login-card {
            background: rgba(245, 242, 242, 0.19);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .login-header h2 {
            color: #1e293b;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.9rem;
        }
        .form-control {
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            color: #1e293b;
        }
        .form-control:focus {
            background: white;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 12px;
            padding: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #000000ff;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <video class="bg-video" autoplay muted loop>
        <source src="../assets/videos/bg1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-md"></i>
                <h2>Doctor Portal</h2>
                <p>Sign in to access patient records</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="doctor@ovcare.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>

            <div class="divider">
                <span>Not a doctor?</span>
            </div>

            <div class="text-center">
                <a href="../patient/login.php" style="color: #6366f1; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-user me-1"></i>Patient Login
                </a>
                <span class="mx-2">•</span>
                <a href="../index.php" style="color: #6366f1; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-home me-1"></i>Home
                </a>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Demo: doctor@ovcare.com / doctor123
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
