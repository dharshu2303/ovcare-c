<?php
// doctor/analytics.php - Aggregate analytics and statistics
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

$doctor_name = $_SESSION['doctor_name'];

// Get total patients
$total_result = $conn->query("SELECT COUNT(*) as count FROM patients WHERE user_type = 'patient'");
$total_patients = $total_result->fetch_assoc()['count'];

// Get risk distribution (latest risk per patient without window functions)
$risk_query = "
    SELECT rh.risk_tier, COUNT(*) AS count
    FROM risk_history rh
    INNER JOIN (
        SELECT patient_id, MAX(calculated_at) AS max_calculated_at
        FROM risk_history
        GROUP BY patient_id
    ) lr ON rh.patient_id = lr.patient_id AND rh.calculated_at = lr.max_calculated_at
    GROUP BY rh.risk_tier
";
$risk_result = $conn->query($risk_query);
$risk_distribution = [];
while ($row = $risk_result->fetch_assoc()) {
    $risk_distribution[$row['risk_tier']] = $row['count'];
}

// Get average biomarker levels
$avg_result = $conn->query("SELECT AVG(CA125) as avg_ca125, AVG(HE4) as avg_he4 FROM biomarker_data");
$avg_data = $avg_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - OvCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <style>
        body {
            background: var(--background);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }
        .main-content {
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }
    </style>
</head>
<body class="dark-theme">
    <nav class="navbar navbar-expand-lg glass-navbar sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand gradient-text" href="../index.php">
                <i class="fas fa-heartbeat me-2"></i>OvCare
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-th-large me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="analytics.php"><i class="fas fa-chart-bar me-1"></i>Analytics</a></li>
                    <li class="nav-item"><span class="nav-link"><i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($doctor_name); ?></span></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="gradient-text mb-2"><i class="fas fa-chart-bar me-2"></i>Analytics Dashboard</h1>
                    <p class="text-secondary">Aggregate statistics and insights</p>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-4">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">Total Patients</h6>
                        <h1 class="gradient-text"><?php echo $total_patients; ?></h1>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">Avg CA125</h6>
                        <h1 class="text-primary"><?php echo number_format($avg_data['avg_ca125'], 2); ?></h1>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">Avg HE4</h6>
                        <h1 class="text-secondary"><?php echo number_format($avg_data['avg_he4'], 2); ?></h1>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Risk Distribution</h5>
                        <div class="chart-container-responsive" style="height: 400px;">
                            <canvas id="riskChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>System Summary</h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>Total Patients:</strong> <?php echo $total_patients; ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                <strong>Critical Risk:</strong> <?php echo $risk_distribution['Critical'] ?? 0; ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                <strong>High Risk:</strong> <?php echo $risk_distribution['High'] ?? 0; ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Low Risk:</strong> <?php echo $risk_distribution['Low'] ?? 0; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script>
        const riskData = {
            labels: ['Low', 'Moderate', 'High', 'Critical'],
            datasets: [{
                data: [
                    <?php echo $risk_distribution['Low'] ?? 0; ?>,
                    <?php echo $risk_distribution['Moderate'] ?? 0; ?>,
                    <?php echo $risk_distribution['High'] ?? 0; ?>,
                    <?php echo $risk_distribution['Critical'] ?? 0; ?>
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#dc2626'],
                borderColor: ['#10b981', '#f59e0b', '#ef4444', '#dc2626'],
                borderWidth: 2
            }]
        };
        createPieChart('riskChart', riskData);
    </script>
</body>
</html>
