<?php
// register.php (renders form + handles registration POST)
session_start();
require_once '../db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$age || !$email || !$password) {
        $message = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $result = patient_register($conn, $name, $email, $password, $age);
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
  <title>Register - OvarianDigitalTwin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; }
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .register-container {
      max-width: 550px;
      width: 100%;
    }
    .register-card {
      background: rgba(255, 255, 255, 0.95);
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
      font-size: 3.5rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: inline-block;
      animation: pulse 2s ease-in-out infinite;
    }
    .brand-title {
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-top: 15px;
    }
    .brand-subtitle {
      color: #666;
      font-size: 0.9rem;
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
    .btn-register {
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
    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(102,126,234,0.4);
    }
    .btn-login {
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
      text-decoration: none;
      display: block;
      text-align: center;
    }
    .btn-login:hover {
      background: #667eea;
      color: white;
    }
    .alert {
      border-radius: 12px;
      border: none;
      padding: 15px 20px;
      font-weight: 500;
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
<div class="register-container">
  <div class="register-card">
    <div class="brand-logo">
      <i class="fas fa-heartbeat"></i>
      <h1 class="brand-title">Create Account</h1>
      <p class="brand-subtitle">Join OvarianDigitalTwin Platform</p>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i><?php echo htmlentities($message); ?>
      </div>
    <?php endif; ?>
    
    <form method="post" novalidate>
      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="form-label"><i class="fas fa-user me-2"></i>Full Name</label>
          <div class="input-icon">
            <i class="fas fa-user"></i>
            <input name="name" class="form-control" placeholder="John Doe" required>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><i class="fas fa-calendar me-2"></i>Age</label>
          <div class="input-icon">
            <i class="fas fa-calendar"></i>
            <input name="age" type="number" class="form-control" placeholder="25" required>
          </div>
        </div>
      </div>
      
      <div class="mb-3">
        <label class="form-label"><i class="fas fa-envelope me-2"></i>Email Address</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input name="email" type="email" class="form-control" placeholder="your.email@example.com" required>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input name="password" type="password" class="form-control" placeholder="Create a strong password" required>
        </div>
      </div>
      
      <button class="btn btn-register" type="submit">
        <i class="fas fa-user-plus me-2"></i>Create Account
      </button>
      
      <div class="divider"><span>Already have an account?</span></div>
      
      <a href="login.php" class="btn btn-login">
        <i class="fas fa-sign-in-alt me-2"></i>Sign In
      </a>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>