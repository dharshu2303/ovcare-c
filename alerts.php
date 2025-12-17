<?php
// alerts.php (simple information page)
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Alerts - OvarianDigitalTwin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; }
    body { 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding-bottom: 40px;
    }
    .navbar { 
      background: rgba(255,255,255,0.95) !important;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
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
      position: relative;
    }
    .nav-link:hover, .nav-link.active {
      color: #667eea !important;
    }
    .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 50%;
      transform: translateX(-50%);
      width: 30px;
      height: 3px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 10px;
    }
    .page-header {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 30px;
      margin-top: 30px;
      margin-bottom: 30px;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .alert-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      margin-bottom: 20px;
      border: 1px solid rgba(255,255,255,0.3);
    }
    .info-box {
      background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
      color: white;
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 10px 30px rgba(9,132,227,0.3);
    }
    .feature-box {
      background: white;
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      transition: all 0.3s;
    }
    .feature-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    }
    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 15px;
    }
    .icon-red { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; }
    .icon-orange { background: linear-gradient(135deg, #ffd93d 0%, #ffa940 100%); color: white; }
    .icon-blue { background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-heartbeat me-2"></i>OvarianDigitalTwin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="data_entry.php"><i class="fas fa-notes-medical me-1"></i>Data Entry</a></li>
        <li class="nav-item"><a class="nav-link active" href="alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <h1 class="section-title mb-0"><i class="fas fa-bell me-3"></i>Alerts & Notifications</h1>
    <p class="text-white mb-0 mt-2" style="font-size: 1rem;">Stay informed about your health status</p>
  </div>
  
  <div class="alert-card">
    <div class="info-box">
      <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>About Alert System</h5>
      <p class="mb-0">Our AI-powered alert system monitors your biomarker data in real-time. If elevated risk levels are detected, you will see immediate notifications on your dashboard. Always consult a qualified healthcare professional for medical advice and treatment decisions.</p>
    </div>
    
    <h5 class="mb-4" style="font-weight: 700; color: #333;"><i class="fas fa-shield-alt me-2" style="color: #667eea;"></i>Alert Categories</h5>
    
    <div class="row">
      <div class="col-md-4">
        <div class="feature-box text-center">
          <div class="feature-icon icon-red mx-auto">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <h6 style="font-weight: 700; color: #333;">High Risk</h6>
          <p style="color: #666; font-size: 0.9rem;">Immediate medical consultation recommended. System detects elevated biomarker levels.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box text-center">
          <div class="feature-icon icon-orange mx-auto">
            <i class="fas fa-exclamation-circle"></i>
          </div>
          <h6 style="font-weight: 700; color: #333;">Moderate Risk</h6>
          <p style="color: #666; font-size: 0.9rem;">Schedule a check-up with your physician. Monitor trends closely.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box text-center">
          <div class="feature-icon icon-blue mx-auto">
            <i class="fas fa-info-circle"></i>
          </div>
          <h6 style="font-weight: 700; color: #333;">Low Risk</h6>
          <p style="color: #666; font-size: 0.9rem;">Continue regular monitoring. Maintain healthy lifestyle habits.</p>
        </div>
      </div>
    </div>
    
    <div class="mt-4 p-4" style="background: #f8f9fa; border-radius: 16px; border-left: 4px solid #667eea;">
      <h6 style="font-weight: 700; color: #333;"><i class="fas fa-lightbulb me-2" style="color: #ffd93d;"></i>Important Notes</h6>
      <ul style="color: #666; margin-bottom: 0;">
        <li>Alerts are generated based on AI analysis and should not replace professional medical diagnosis</li>
        <li>Regular data entry ensures accurate risk assessment and timely alerts</li>
        <li>Review your dashboard daily for the latest health insights</li>
        <li>Contact your healthcare provider immediately if you receive a high-risk alert</li>
      </ul>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>