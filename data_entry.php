<?php
// data_entry.php - page + insertion handling
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_SESSION['patient_id']);
    $CA125 = floatval($_POST['CA125'] ?? 0);
    $HE4 = floatval($_POST['HE4'] ?? 0);
    $heart_rate = floatval($_POST['heart_rate'] ?? 0);
    $temperature = floatval($_POST['temperature'] ?? 0);
    $sleep_hours = floatval($_POST['sleep_hours'] ?? 0);
    $symptoms = trim($_POST['symptoms'] ?? '');
    $recorded_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO biomarker_data (patient_id, CA125, HE4, heart_rate, temperature, sleep_hours, symptoms, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idddddss", $patient_id, $CA125, $HE4, $heart_rate, $temperature, $sleep_hours, $symptoms, $recorded_at);
    if ($stmt->execute()) {
        $message = "Data saved successfully.";
    } else {
        $message = "Failed to save data.";
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Data Entry - OvarianDigitalTwin</title>
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
      border-bottom: 1px solid rgba(255,255,255,0.3);
    }
    .navbar-brand {
      font-weight: 800;
      font-size: 1.5rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
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
    .entry-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      border: 1px solid rgba(255,255,255,0.3);
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      padding: 40px;
      margin-top: 30px;
    }
    .page-header {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 30px;
      margin-top: 30px;
      margin-bottom: 0;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      text-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
      font-size: 1rem;
    }
    .input-icon .form-control {
      padding-left: 50px;
    }
    .btn-save {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 12px;
      padding: 14px 40px;
      font-weight: 700;
      font-size: 1.05rem;
      color: white;
      transition: all 0.3s;
      box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(102,126,234,0.4);
    }
    .alert {
      border-radius: 12px;
      border: none;
      padding: 15px 20px;
      font-weight: 500;
    }
    .input-group-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .card-subtitle {
      font-weight: 700;
      color: #667eea;
      font-size: 1.1rem;
      margin-bottom: 20px;
    }
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
        <li class="nav-item"><a class="nav-link active" href="data_entry.php"><i class="fas fa-notes-medical me-1"></i>Data Entry</a></li>
        <li class="nav-item"><a class="nav-link" href="alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <h1 class="section-title mb-0"><i class="fas fa-notes-medical me-3"></i>Biomarker Data Entry</h1>
    <p class="text-white mb-0 mt-2" style="font-size: 1rem;">Enter your latest biomarker measurements for AI analysis</p>
  </div>
  
  <div class="entry-card">
    <?php if ($message): ?>
      <div class="alert alert-success mb-4">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlentities($message); ?>
      </div>
    <?php endif; ?>
    
    <form method="post" novalidate>
      <div class="input-group-card">
        <div class="card-subtitle"><i class="fas fa-flask me-2"></i>Biomarker Levels</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-vial me-2"></i>CA125 Level</label>
            <div class="input-icon">
              <i class="fas fa-vial"></i>
              <input name="CA125" type="number" step="any" class="form-control" placeholder="e.g., 35.5" required>
            </div>
            <small class="text-muted">Normal range: &lt;35 U/mL</small>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><i class="fas fa-flask me-2"></i>HE4 Level</label>
            <div class="input-icon">
              <i class="fas fa-flask"></i>
              <input name="HE4" type="number" step="any" class="form-control" placeholder="e.g., 100.0" required>
            </div>
            <small class="text-muted">Normal range: &lt;140 pmol/L</small>
          </div>
        </div>
      </div>
      
      <div class="input-group-card">
        <div class="card-subtitle"><i class="fas fa-heartbeat me-2"></i>Vital Signs</div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><i class="fas fa-heartbeat me-2"></i>Heart Rate (bpm)</label>
            <div class="input-icon">
              <i class="fas fa-heartbeat"></i>
              <input name="heart_rate" type="number" step="any" class="form-control" placeholder="e.g., 72" required>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><i class="fas fa-thermometer-half me-2"></i>Temperature (°C)</label>
            <div class="input-icon">
              <i class="fas fa-thermometer-half"></i>
              <input name="temperature" type="number" step="any" class="form-control" placeholder="e.g., 36.5" required>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><i class="fas fa-moon me-2"></i>Sleep Hours</label>
            <div class="input-icon">
              <i class="fas fa-moon"></i>
              <input name="sleep_hours" type="number" step="any" class="form-control" placeholder="e.g., 7.5" required>
            </div>
          </div>
        </div>
      </div>
      
      <div class="input-group-card">
        <div class="card-subtitle"><i class="fas fa-clipboard-list me-2"></i>Symptoms & Notes</div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-list me-2"></i>Current Symptoms</label>
          <textarea name="symptoms" class="form-control" rows="3" placeholder="Enter any symptoms you're experiencing (comma separated)"></textarea>
          <small class="text-muted">e.g., fatigue, abdominal pain, bloating</small>
        </div>
      </div>
      
      <div class="text-center mt-4">
        <button class="btn btn-save" type="submit">
          <i class="fas fa-save me-2"></i>Save Biomarker Data
        </button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>