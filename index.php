<?php
// index.php - Home (no processing)
session_start();
$logged_in = isset($_SESSION['patient_id']);
$name = $_SESSION['patient_name'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>OVCare</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; }
    body {
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }
    .navbar { 
      background: rgba(255,255,255,0.95) !important;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .navbar-brand {
      font-weight: 800;
      font-size: 1.5rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .nav-link {
      color: #333 !important;
      font-weight: 500;
      margin: 0 10px;
      transition: all 0.3s;
    }
    .nav-link:hover {
      color: #667eea !important;
    }
    .hero-section {
      min-height: 90vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      text-align: center;
      background-image: url("bg.jpeg");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
    }
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(102,126,234,0.7) 0%, rgba(118,75,162,0.7) 100%);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
    }
    .hero-content {
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .hero-icon {
      font-size: 6rem;
      color: white;
      margin-bottom: 30px;
      display: inline-block;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    .hero-title {
      font-size: 3.5rem;
      font-weight: 800;
      color: white;
      margin-bottom: 20px;
      text-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .hero-subtitle {
      font-size: 1.3rem;
      color: rgba(255,255,255,0.9);
      margin-bottom: 40px;
      font-weight: 300;
    }
    .btn-hero-primary {
      background: white;
      color: #667eea;
      border: none;
      border-radius: 12px;
      padding: 16px 40px;
      font-weight: 700;
      font-size: 1.1rem;
      margin: 0 10px;
      transition: all 0.3s;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .btn-hero-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    }
    .btn-hero-secondary {
      background: transparent;
      border: 2px solid white;
      color: white;
      border-radius: 12px;
      padding: 14px 38px;
      font-weight: 700;
      font-size: 1.1rem;
      margin: 0 10px;
      transition: all 0.3s;
    }
    .btn-hero-secondary:hover {
      background: white;
      color: #667eea;
    }
    .features-section {
      padding: 80px 20px;
      background: white;
    }
    .section-title {
      font-size: 2.5rem;
      font-weight: 800;
      text-align: center;
      margin-bottom: 60px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .feature-card {
      background: rgba(255,255,255,0.95);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      text-align: center;
      transition: all 0.3s;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.12);
    }
    .feature-icon {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin: 0 auto 20px;
    }
    .icon-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .icon-2 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
    .icon-3 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
    .feature-title {
      font-weight: 700;
      font-size: 1.3rem;
      color: #333;
      margin-bottom: 12px;
    }
    .feature-text {
      color: #666;
      font-size: 0.95rem;
      line-height: 1.6;
    }
    .cta-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 60px 20px;
      text-align: center;
    }
    .cta-title {
      font-size: 2rem;
      font-weight: 800;
      color: white;
      margin-bottom: 30px;
    }
    .footer {
      background: rgba(0,0,0,0.95);
      color: white;
      padding: 30px 20px;
      text-align: center;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-heartbeat me-2"></i>OVCare</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if ($logged_in): ?>
          <li class="nav-item"><a class="nav-link" href="patient/dashboard.php"><i class="fas fa-chart-line me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="patient/data_entry.php"><i class="fas fa-notes-medical me-1"></i>Data Entry</a></li>
          <li class="nav-item"><a class="nav-link" href="patient/alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="patient/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout (<?php echo htmlentities($name); ?>)</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="patient/register.php"><i class="fas fa-user-plus me-1"></i>Patient Register</a></li>
          <li class="nav-item"><a class="nav-link" href="patient/login.php"><i class="fas fa-sign-in-alt me-1"></i>Patient Login</a></li>
          <li class="nav-item"><a class="nav-link" href="doctor/login.php"><i class="fas fa-user-md me-1"></i>Doctor Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="hero-section">
  <div class="hero-content">
    <div class="hero-icon">
      <i class="fas fa-heart"></i>
    </div>
    <h1 class="hero-title">OVCare</h1>
    <p class="hero-subtitle">AI-Powered Early Detection & Health Monitoring System</p>
    <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem; margin-bottom: 40px;">Advanced machine learning analysis of your biomarkers for proactive health management</p>
    
    <?php if (!$logged_in): ?>
      <a href="patient/register.php" class="btn btn-hero-primary">Patient Sign Up</a>
      <a href="patient/login.php" class="btn btn-hero-secondary">Patient Sign In</a>
      <div style="margin-top: 20px;">
        <a href="doctor/login.php" class="btn btn-hero-secondary"><i class="fas fa-user-md me-2"></i>Doctor Portal</a>
      </div>
    <?php else: ?>
      <a href="patient/dashboard.php" class="btn btn-hero-primary"><i class="fas fa-chart-line me-2"></i>Open Dashboard</a>
      <a href="patient/data_entry.php" class="btn btn-hero-secondary"><i class="fas fa-notes-medical me-2"></i>Enter Data</a>
    <?php endif; ?>
  </div>
</div>

<div class="features-section">
  <div class="container">
    <h2 class="section-title">Key Features</h2>
    
    <div class="row">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-1">
            <i class="fas fa-chart-line"></i>
          </div>
          <h5 class="feature-title">Real-Time Monitoring</h5>
          <p class="feature-text">Track your biomarker levels continuously with interactive visualizations and trend analysis.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-2">
            <i class="fas fa-brain"></i>
          </div>
          <h5 class="feature-title">AI Risk Assessment</h5>
          <p class="feature-text">Advanced machine learning algorithms provide accurate cancer risk predictions based on your medical data.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-3">
            <i class="fas fa-bell"></i>
          </div>
          <h5 class="feature-title">Smart Alerts</h5>
          <p class="feature-text">Receive immediate notifications when risk levels change, enabling early intervention.</p>
        </div>
      </div>
    </div>
    
    <div class="row mt-5">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-1">
            <i class="fas fa-microscope"></i>
          </div>
          <h5 class="feature-title">Multi-Biomarker Analysis</h5>
          <p class="feature-text">Monitor CA125, HE4, and other critical biomarkers in one unified dashboard.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-2">
            <i class="fas fa-lock"></i>
          </div>
          <h5 class="feature-title">Secure & Private</h5>
          <p class="feature-text">Your health data is encrypted and stored securely with the highest privacy standards.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon icon-3">
            <i class="fas fa-user-md"></i>
          </div>
          <h5 class="feature-title">Medical Support</h5>
          <p class="feature-text">Access insights and consult with healthcare professionals for personalized recommendations.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="cta-section">
  <div class="container">
    <h2 class="cta-title">Ready to Take Control of Your Health?</h2>
    <?php if (!$logged_in): ?>
      <a href="patient/register.php" class="btn btn-hero-primary" style="margin-bottom: 20px;">Create Your Account Now</a>
      <p style="color: rgba(255,255,255,0.8); margin-top: 20px;">Already have an account? <a href="patient/login.php" style="color: white; font-weight: 700; text-decoration: underline;">Sign in here</a></p>
      <p style="color: rgba(255,255,255,0.8); margin-top: 10px;">Healthcare provider? <a href="doctor/login.php" style="color: white; font-weight: 700; text-decoration: underline;">Doctor login</a></p>
    <?php else: ?>
      <a href="patient/dashboard.php" class="btn btn-hero-primary">Go to Dashboard</a>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>