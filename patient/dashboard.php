<?php
// dashboard.php - shows trends, calculates risk via Flask ML (server-side cURL)
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

$patient_id = intval($_SESSION['patient_id']);

// Fetch patient age
$patient_age = 50; // default
$stmt_age = $conn->prepare("SELECT age FROM patients WHERE id = ?");
$stmt_age->bind_param("i", $patient_id);
$stmt_age->execute();
$res_age = $stmt_age->get_result();
if ($res_age && $row_age = $res_age->fetch_assoc()) {
    $patient_age = intval($row_age['age']);
}
$stmt_age->close();

$rows = [];
// Fetch all biomarker records
$stmt = $conn->prepare("SELECT CA125, HE4, heart_rate, temperature, sleep_hours, symptoms, recorded_at FROM biomarker_data WHERE patient_id = ? ORDER BY recorded_at ASC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

// Fetch latest record for ML prediction
$latest = null;
$stmt2 = $conn->prepare("SELECT CA125, HE4, heart_rate, temperature, sleep_hours, symptoms FROM biomarker_data WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
$stmt2->bind_param("i", $patient_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2 && $row = $res2->fetch_assoc()) $latest = $row;
$stmt2->close();

$risk = null;
$probability = null;
$risk_tier = null;
$explanation = [];

// Prefer averaged risk across full history to avoid single-record bias
$risk_summary = get_patient_risk_summary($conn, $patient_id);

if ($risk_summary) {
  $risk_tier = $risk_summary['risk_tier'];
  $probability = $risk_summary['probability'];
  $risk = ($risk_tier === 'High' || $risk_tier === 'Critical') ? 1 : 0;
} elseif ($latest) {
    // Fallback: Call ML API if no risk_history exists
    $payload = [
        "Age" => $patient_age,
        "CA125_Level" => floatval($latest['CA125']),
        "HE4_Level" => floatval($latest['HE4']),
        "LDH_Level" => 180.0,
        "Hemoglobin" => 13.0,
        "WBC" => floatval($latest['heart_rate']) * 100,
        "Platelets" => 250000.0,
        "Ovary_Size" => 3.5,
        "Fatigue_Level" => 5,
        "Pelvic_Pain" => 0,
        "Abdominal_Bloating" => 0,
        "Early_Satiety" => 0,
        "Menstrual_Irregularities" => 0,
        "Weight_Change" => 0.0
    ];
    
    $ch = curl_init(ML_API_URL . '/predict');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, ML_API_TIMEOUT);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        $risk = null;
        $explanation = ["error" => "ML service unreachable: $err. Please ensure Flask server is running on port 5000."];
    } else {
        $py = json_decode($response, true);
        if ($py && isset($py['risk'])) {
            $risk = intval($py['risk']);
            $probability = isset($py['probability']) ? floatval($py['probability']) : null;
            $risk_tier = get_risk_tier($probability ?? ($risk ? 0.75 : 0.25));
        } else {
            $explanation = ["error" => "Invalid response from ML service: " . ($response ?? 'null')];
        }
    }
}

// Build explanation based on latest biomarker data
if ($latest) {
    $explanation = [
        "CA125" => (floatval($latest['CA125']) >= 35) ? "High (≥35)" : "Normal (<35)",
        "HE4" => (floatval($latest['HE4']) >= 140) ? "High (≥140)" : "Normal (<140)",
        "Age" => $patient_age . " years",
        "Symptoms" => !empty($latest['symptoms']) ? $latest['symptoms'] : 'None reported'
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard - OvarianDigitalTwin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      border: 1px solid rgba(255,255,255,0.3);
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      padding: 30px;
      transition: all 0.3s ease;
    }
    .glass-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 70px rgba(0,0,0,0.2);
    }
    .risk-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 20px 60px rgba(102,126,234,0.4);
      position: relative;
      overflow: hidden;
    }
    .risk-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.5; }
      50% { transform: scale(1.1); opacity: 0.8; }
    }
    .risk-score {
      font-size: 4rem;
      font-weight: 800;
      text-shadow: 0 4px 20px rgba(0,0,0,0.2);
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .metric-card {
      background: white;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      border: 1px solid rgba(0,0,0,0.05);
      transition: all 0.3s;
      height: 100%;
    }
    .metric-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    }
    .metric-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 15px;
    }
    .icon-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .icon-blue { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
    .icon-pink { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
    .icon-orange { background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%); color: white; }
    
    .alert-modern {
      border: none;
      border-radius: 16px;
      padding: 20px 25px;
      font-weight: 500;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      animation: slideIn 0.5s ease;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-20px); }
      to { opacity: 1; transform: translateX(0); }
    }
    .alert-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; }
    .alert-success { background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%); color: white; }
    .alert-warning { background: linear-gradient(135deg, #ffd93d 0%, #ffa940 100%); color: white; }
    .alert-info { background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; }
    
    .chart-container {
      background: white;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      margin-bottom: 20px;
      transition: all 0.3s;
    }
    .chart-container:hover {
      box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    }
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      margin-bottom: 30px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .factor-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 12px;
      margin: 5px;
      font-weight: 600;
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
    }
    .page-header {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 30px;
      margin-bottom: 30px;
      border: 1px solid rgba(255,255,255,0.2);
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-heartbeat me-2"></i>OVCare</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-chart-line me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="page-header">
    <h1 class="section-title mb-2"><i class="fas fa-user-md me-3"></i>Digital Twin Dashboard</h1>
    <p class="text-white mb-0" style="font-size: 1.1rem;">Welcome back, <strong><?php echo htmlentities($_SESSION['patient_name']); ?></strong>! Here's your health overview.</p>
  </div>

  <div class="risk-card mb-4">
    <div class="row align-items-center" style="position: relative; z-index: 1;">
      <div class="col-md-8">
        <h5 class="text-white-50 mb-3"><i class="fas fa-shield-alt me-2"></i>Current Risk Assessment</h5>
        <div class="risk-score">
          <?php 
            if ($risk === null) {
              echo '<span style="font-size: 2.5rem;">N/A</span>';
            } elseif ($risk === 1) {
              echo '<i class="fas fa-exclamation-triangle me-3"></i>HIGH RISK';
            } else {
              echo '<i class="fas fa-check-circle me-3"></i>LOW RISK';
            }
          ?>
        </div>
        <?php if ($probability !== null): ?>
          <div class="mt-3" style="font-size: 1.3rem; opacity: 0.9;">
            Probability: <strong><?php echo round($probability * 100, 1); ?>%</strong>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-4 text-center">
        <div style="font-size: 8rem; opacity: 0.2;">
          <?php echo $risk === 1 ? '<i class="fas fa-exclamation-circle"></i>' : ($risk === 0 ? '<i class="fas fa-smile"></i>' : '<i class="fas fa-question-circle"></i>'); ?>
        </div>
      </div>
    </div>
  </div>

  <div id="alertArea" class="mb-4">
    <?php if ($risk === 1): ?>
      <div class="alert-modern alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i><strong>High Risk Detected</strong> — Please consult your physician immediately for further evaluation.
      </div>
    <?php elseif ($risk === 0): ?>
      <div class="alert-modern alert-success">
        <i class="fas fa-check-circle me-2"></i><strong>Low Risk Status</strong> — Continue regular monitoring and maintain healthy lifestyle habits.
      </div>
    <?php elseif (!empty($explanation['error'])): ?>
      <div class="alert-modern alert-warning">
        <i class="fas fa-info-circle me-2"></i><strong>Note:</strong> <?php echo htmlentities($explanation['error']); ?>
      </div>
    <?php else: ?>
      <div class="alert-modern alert-info">
        <i class="fas fa-clipboard-list me-2"></i>No data available. Please enter biomarker data to get your risk assessment.
      </div>
    <?php endif; ?>
  </div>

  <div class="glass-card mb-4">
    <h5 class="mb-4" style="font-weight: 700; color: #333;"><i class="fas fa-brain me-2" style="color: #667eea;"></i>Key Influencing Factors</h5>
    <div class="row">
      <?php 
      $icons = ['CA125' => 'fa-vial', 'HE4' => 'fa-flask', 'Age' => 'fa-birthday-cake', 'Symptoms' => 'fa-notes-medical'];
      $colors = ['CA125' => 'icon-purple', 'HE4' => 'icon-blue', 'Age' => 'icon-pink', 'Symptoms' => 'icon-orange'];
      $index = 0;
      foreach ($explanation as $k => $v): 
        if ($k !== 'error'):
          $iconClass = isset($icons[$k]) ? $icons[$k] : 'fa-heartbeat';
          $colorClass = isset($colors[$k]) ? $colors[$k] : 'icon-purple';
      ?>
        <div class="col-md-3 mb-3">
          <div class="metric-card text-center">
            <div class="metric-icon <?php echo $colorClass; ?> mx-auto">
              <i class="fas <?php echo $iconClass; ?>"></i>
            </div>
            <h6 style="font-weight: 600; color: #555;"><?php echo htmlentities($k); ?></h6>
            <p style="font-size: 1.1rem; font-weight: 700; color: #333; margin: 0;"><?php echo htmlentities($v); ?></p>
          </div>
        </div>
      <?php 
        $index++;
        endif;
      endforeach; 
      ?>
    </div>
  </div>

  <h4 class="section-title mb-4"><i class="fas fa-chart-area me-3"></i>Biomarker Trends</h4>
  
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="chart-container">
        <h6 style="font-weight: 700; color: #333; margin-bottom: 20px;"><i class="fas fa-vial me-2" style="color: #667eea;"></i>CA125 Level Over Time</h6>
        <canvas id="ca125Chart"></canvas>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="chart-container">
        <h6 style="font-weight: 700; color: #333; margin-bottom: 20px;"><i class="fas fa-flask me-2" style="color: #38f9d7;"></i>HE4 Level Over Time</h6>
        <canvas id="he4Chart"></canvas>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="chart-container">
        <h6 style="font-weight: 700; color: #333; margin-bottom: 20px;"><i class="fas fa-heartbeat me-2" style="color: #ff6a88;"></i>Heart Rate</h6>
        <canvas id="hrChart"></canvas>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="chart-container">
        <h6 style="font-weight: 700; color: #333; margin-bottom: 20px;"><i class="fas fa-moon me-2" style="color: #764ba2;"></i>Sleep Hours</h6>
        <canvas id="sleepChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Prepare data for charts from PHP $rows
const rows = <?php echo json_encode($rows); ?>;
const labels = rows.map(r => r.recorded_at);
function numbers(key){ return rows.map(r => r[key] === null ? null : parseFloat(r[key])); }

function drawLine(id, label, data, gradient1, gradient2){
  const ctx = document.getElementById(id).getContext('2d');
  const gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
  gradientFill.addColorStop(0, gradient1);
  gradientFill.addColorStop(1, gradient2);
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label,
        data,
        borderColor: gradient1,
        backgroundColor: gradientFill,
        tension: 0.4,
        fill: true,
        borderWidth: 3,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointBackgroundColor: '#fff',
        pointBorderColor: gradient1,
        pointBorderWidth: 3,
        pointHoverBackgroundColor: gradient1,
        pointHoverBorderColor: '#fff',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#666' }
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { font: { size: 11 }, color: '#666' }
        }
      }
    }
  });
}

drawLine('ca125Chart', 'CA125', numbers('CA125'), 'rgba(102,126,234,1)', 'rgba(102,126,234,0.1)');
drawLine('he4Chart', 'HE4', numbers('HE4'), 'rgba(56,249,215,1)', 'rgba(56,249,215,0.1)');
drawLine('hrChart', 'Heart Rate', numbers('heart_rate'), 'rgba(255,106,136,1)', 'rgba(255,106,136,0.1)');
drawLine('sleepChart', 'Sleep Hours', numbers('sleep_hours'), 'rgba(118,75,162,1)', 'rgba(118,75,162,0.1)');
</script>
</body>
</html>