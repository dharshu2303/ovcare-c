<?php
session_start();
require_once '../db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $message = "Email & password required.";
    } else {
        $result = patient_login($conn, $email, $password);
        if ($result['success']) {
            header("Location: dashboard.php");
            exit;
        } else {
            $message = $result['message'];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - OvarianDigitalTwin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
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
      background: rgba(102, 126, 234, 0.4);
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
      max-width: 480px;
      width: 100%;
      position: relative;
      z-index: 1;
      margin-left: 55%;
      margin-right: 5%;
    }
    .login-card {
      background: rgba(247, 245, 245, 0.22);
      backdrop-filter: blur(20px);
      border-radius: 30px;
      padding: 50px 40px;
      box-shadow: 0 30px 90px rgba(0,0,0,0.25);
      border: 1px solid rgba(255,255,255,0.3);
      animation: slideUp 0.6s ease;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .brand-logo {
      text-align: center;
      margin-bottom: 40px;
    }
    .brand-logo i {
      font-size: 4rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: inline-block;
      animation: pulse 2s ease-in-out infinite;
    }
    .brand-title {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-top: 15px;
    }
    .brand-subtitle {
      color: #666;
      font-size: 0.95rem;
      margin-top: 8px;
    }
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      font-size: 0.9rem;
    }
    .form-control {
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 1rem;
      transition: all 0.3s;
      background: #f8f9fa;
    }
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
      background: white;
    }
    .input-icon {
      position: relative;
    }
    .input-icon i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      font-size: 1.1rem;
    }
    .input-icon .form-control {
      padding-left: 50px;
    }
    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 12px;
      padding: 14px;
      font-weight: 700;
      font-size: 1.05rem;
      color: white;
      width: 100%;
      transition: all 0.3s;
      box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(102,126,234,0.4);
    }
    .btn-register {
      background: transparent;
      border: 2px solid #667eea;
      border-radius: 12px;
      padding: 12px;
      font-weight: 600;
      font-size: 1rem;
      color: #667eea;
      width: 100%;
      transition: all 0.3s;
      margin-top: 15px;
    }
    .btn-register:hover {
      background: #667eea;
      color: white;
    }
    .alert {
      border-radius: 12px;
      border: none;
      padding: 15px 20px;
      font-weight: 500;
      animation: shake 0.5s;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }
    .divider {
      text-align: center;
      margin: 30px 0;
      position: relative;
    }
    .divider::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      width: 100%;
      height: 1px;
      background: #e0e0e0;
    }
    .divider span {
      background: rgba(255,255,255,0.95);
      padding: 0 15px;
      position: relative;
      color: #999;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <!-- Background Video -->
  <video class="bg-video" autoplay muted loop>
    <source src="../assets/videos/bg.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>

<div class="login-container">
  <div class="login-card">
    <div class="brand-logo">
      <i class="fas fa-heartbeat"></i>
      <h1 class="brand-title">OvarianDigitalTwin</h1>
      <p class="brand-subtitle">Advanced AI-Powered Health Monitoring</p>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlentities($message); ?>
      </div>
    <?php endif; ?>
    
    <form method="post" novalidate>
      <div class="mb-4">
        <label class="form-label"><i class="fas fa-envelope me-2"></i>Email Address</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input name="email" type="email" class="form-control" placeholder="Enter your email" required autofocus>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input name="password" type="password" class="form-control" placeholder="Enter your password" required>
        </div>
      </div>
      
      <button class="btn btn-login" type="submit">
        <i class="fas fa-sign-in-alt me-2"></i>Sign In
      </button>
      
      <div class="divider"><span>Don't have an account?</span></div>
      
      <a href="register.php" class="btn btn-register">
        <i class="fas fa-user-plus me-2"></i>Create New Account
      </a>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>