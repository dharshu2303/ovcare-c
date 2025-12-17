<?php
// patient/history.php - Historical trends and temporal analysis
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

$patient_id = intval($_SESSION['patient_id']);

// Fetch all biomarker data
$stmt = $conn->prepare("SELECT CA125, HE4, heart_rate, temperature, sleep_hours, symptoms, recorded_at FROM biomarker_data WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 100");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}
$stmt->close();

// Fetch risk history
$stmt = $conn->prepare("SELECT risk_score, risk_tier, probability, ca125, he4, ca125_velocity, he4_velocity, calculated_at FROM risk_history WHERE patient_id = ? ORDER BY calculated_at DESC LIMIT 50");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$risk_history = [];
while ($row = $result->fetch_assoc()) {
    $risk_history[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Trends - OvCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg glass-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand gradient-text" href="../index.php">
                <i class="fas fa-heartbeat me-2"></i>OvCare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_entry.php"><i class="fas fa-notes-medical me-1"></i>Data Entry</a></li>
                    <li class="nav-item"><a class="nav-link active" href="history.php"><i class="fas fa-history me-1"></i>History</a></li>
                    <li class="nav-item"><a class="nav-link" href="alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="gradient-text mb-2"><i class="fas fa-history me-2"></i>Historical Trends</h1>
                    <p class="text-secondary">View your biomarker history and temporal analysis</p>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Time Range</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="glass-btn" onclick="filterData(30)">Last 30 Days</button>
                            <button type="button" class="glass-btn" onclick="filterData(90)">Last 90 Days</button>
                            <button type="button" class="glass-btn" onclick="filterData(365)">Last Year</button>
                            <button type="button" class="glass-btn" onclick="filterData(0)">All Time</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Axis Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Biomarker & Risk Trends</h5>
                        <div class="chart-container-responsive">
                            <canvas id="multiAxisChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Biomarker Charts -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-vial me-2"></i>CA125 Trend</h5>
                        <div class="chart-container-responsive" style="height: 300px;">
                            <canvas id="ca125Chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-vial me-2"></i>HE4 Trend</h5>
                        <div class="chart-container-responsive" style="height: 300px;">
                            <canvas id="he4Chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Velocity Indicators -->
            <?php if (!empty($risk_history) && isset($risk_history[0]['ca125_velocity'])): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>CA125 Velocity</h5>
                        <h2 class="<?php echo $risk_history[0]['ca125_velocity'] > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($risk_history[0]['ca125_velocity'], 2); ?> 
                            <i class="fas fa-arrow-<?php echo $risk_history[0]['ca125_velocity'] > 0 ? 'up' : 'down'; ?>"></i>
                        </h2>
                        <p class="text-muted">Units per day</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>HE4 Velocity</h5>
                        <h2 class="<?php echo $risk_history[0]['he4_velocity'] > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($risk_history[0]['he4_velocity'], 2); ?> 
                            <i class="fas fa-arrow-<?php echo $risk_history[0]['he4_velocity'] > 0 ? 'up' : 'down'; ?>"></i>
                        </h2>
                        <p class="text-muted">Units per day</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Data Table -->
            <div class="row">
                <div class="col-12">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-table me-2"></i>Record History</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>CA125</th>
                                        <th>HE4</th>
                                        <th>Heart Rate</th>
                                        <th>Temperature</th>
                                        <th>Sleep Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?php echo format_datetime($record['recorded_at']); ?></td>
                                        <td><?php echo number_format($record['CA125'], 2); ?></td>
                                        <td><?php echo number_format($record['HE4'], 2); ?></td>
                                        <td><?php echo number_format($record['heart_rate'], 0); ?></td>
                                        <td><?php echo number_format($record['temperature'], 1); ?>°F</td>
                                        <td><?php echo number_format($record['sleep_hours'], 1); ?>h</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Prepare data for charts
        const records = <?php echo json_encode(array_reverse($records)); ?>;
        const riskHistory = <?php echo json_encode(array_reverse($risk_history)); ?>;
        
        // Extract data for charts
        const dates = records.map(r => new Date(r.recorded_at).toLocaleDateString());
        const ca125Data = records.map(r => parseFloat(r.CA125));
        const he4Data = records.map(r => parseFloat(r.HE4));
        const riskData = riskHistory.map(r => parseFloat(r.probability) * 100);
        const riskDates = riskHistory.map(r => new Date(r.calculated_at).toLocaleDateString());
        
        // Create multi-axis chart
        createMultiAxisChart('multiAxisChart', dates, ca125Data, he4Data, riskData);
        
        // Create CA125 trend chart
        createTrendChart('ca125Chart', {
            labels: dates,
            datasets: [{
                label: 'CA125 Level',
                data: ca125Data,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.3,
                fill: true
            }]
        });
        
        // Create HE4 trend chart
        createTrendChart('he4Chart', {
            labels: dates,
            datasets: [{
                label: 'HE4 Level',
                data: he4Data,
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                tension: 0.3,
                fill: true
            }]
        });
        
        function filterData(days) {
            // Filter logic would be implemented here
            showToast(`Filtering data for last ${days} days`, 'info');
        }
    </script>
</body>
</html>
