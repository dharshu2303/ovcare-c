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

// Get risk distribution - FIXED QUERY
$risk_query = "
    SELECT 
        COALESCE(rh.risk_tier, 'Unknown') as risk_tier, 
        COUNT(DISTINCT p.id) as count
    FROM patients p
    LEFT JOIN (
        SELECT patient_id, risk_tier,
            ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY calculated_at DESC) as rn
        FROM risk_history
    ) rh ON p.id = rh.patient_id AND rh.rn = 1
    WHERE p.user_type = 'patient'
    GROUP BY COALESCE(rh.risk_tier, 'Unknown')
    ORDER BY 
        CASE COALESCE(rh.risk_tier, 'Unknown')
            WHEN 'Critical' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Moderate' THEN 3
            WHEN 'Low' THEN 4
            ELSE 5
        END
";

// Alternative simpler query if your MySQL version doesn't support window functions:
// $risk_query = "
//     SELECT 
//         COALESCE(rh.risk_tier, 'Unknown') as risk_tier, 
//         COUNT(DISTINCT p.id) as count
//     FROM patients p
//     LEFT JOIN risk_history rh ON p.id = rh.patient_id
//     WHERE p.user_type = 'patient'
//     AND rh.calculated_at = (
//         SELECT MAX(calculated_at) 
//         FROM risk_history rh2 
//         WHERE rh2.patient_id = p.id
//     ) OR rh.calculated_at IS NULL
//     GROUP BY COALESCE(rh.risk_tier, 'Unknown')
// ";

// Even simpler approach: Get all patients and their latest risk like in dashboard.php
$patients_query = "SELECT id FROM patients WHERE user_type = 'patient'";
$patients_result = $conn->query($patients_query);

$risk_distribution = [
    'Low' => 0,
    'Moderate' => 0,
    'High' => 0,
    'Critical' => 0,
    'Unknown' => 0
];

while ($patient_row = $patients_result->fetch_assoc()) {
    $patient_id = $patient_row['id'];
    
    // Get latest risk tier for this patient
    $risk_stmt = $conn->prepare("
        SELECT risk_tier 
        FROM risk_history 
        WHERE patient_id = ? 
        ORDER BY calculated_at DESC 
        LIMIT 1
    ");
    $risk_stmt->bind_param("i", $patient_id);
    $risk_stmt->execute();
    $risk_result = $risk_stmt->get_result();
    $risk_data = $risk_result->fetch_assoc();
    $risk_stmt->close();
    
    $risk_tier = $risk_data['risk_tier'] ?? 'Unknown';
    
    if (isset($risk_distribution[$risk_tier])) {
        $risk_distribution[$risk_tier]++;
    } else {
        $risk_distribution[$risk_tier] = 1;
    }
}

// Alternative: Use a single query with subquery
$simple_risk_query = "
    SELECT 
        COALESCE((SELECT risk_tier 
                  FROM risk_history 
                  WHERE patient_id = p.id 
                  ORDER BY calculated_at DESC 
                  LIMIT 1), 'Unknown') as risk_tier,
        COUNT(*) as count
    FROM patients p
    WHERE p.user_type = 'patient'
    GROUP BY COALESCE((SELECT risk_tier 
                      FROM risk_history 
                      WHERE patient_id = p.id 
                      ORDER BY calculated_at DESC 
                      LIMIT 1), 'Unknown')
";

$simple_result = $conn->query($simple_risk_query);
$risk_distribution = [
    'Low' => 0,
    'Moderate' => 0,
    'High' => 0,
    'Critical' => 0,
    'Unknown' => 0
];

if ($simple_result) {
    while ($row = $simple_result->fetch_assoc()) {
        $risk_tier = $row['risk_tier'];
        $count = $row['count'];
        
        if (isset($risk_distribution[$risk_tier])) {
            $risk_distribution[$risk_tier] = $count;
        } else {
            $risk_distribution[$risk_tier] = $count;
        }
    }
}

// Get average biomarker levels
$avg_result = $conn->query("SELECT AVG(CA125) as avg_ca125, AVG(HE4) as avg_he4 FROM biomarker_data");
$avg_data = $avg_result->fetch_assoc();
// Ensure averages are numeric to avoid passing null to number_format()
$avg_ca125 = isset($avg_data['avg_ca125']) && $avg_data['avg_ca125'] !== null ? floatval($avg_data['avg_ca125']) : 0.0;
$avg_he4 = isset($avg_data['avg_he4']) && $avg_data['avg_he4'] !== null ? floatval($avg_data['avg_he4']) : 0.0;
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
        /* Ensure readable white text on dark analytics page */
        .glass-card,
        .glass-card h1, .glass-card h2, .glass-card h3, .glass-card h4, .glass-card h5, .glass-card h6,
        .glass-card p, .glass-card small, .glass-card .list-unstyled, .glass-card .list-unstyled li {
            color: #ffffff !important;
        }
        .glass-card .text-muted, .text-muted {
            color: rgba(255,255,255,0.75) !important;
        }
        .navbar .nav-link { color: #ffffff !important; }
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
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card">
                        <h6 class="text-muted">Total Patients</h6>
                        <h1 class="gradient-text"><?php echo $total_patients; ?></h1>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card glass-card-danger">
                        <h6 class="text-muted">Avg CA125</h6>
                        <h1 class="text-primary"><?php echo number_format($avg_data['avg_ca125'], 2); ?></h1>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card">
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
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Risk Statistics</h5>
                        <div class="mb-4">
                            <div class="risk-stat">
                                <div>
                                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                    <strong>Critical Risk</strong>
                                </div>
                                <div class="count"><?php echo $risk_distribution['Critical'] ?? 0; ?></div>
                            </div>
                            <div class="risk-stat">
                                <div>
                                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                    <strong>High Risk</strong>
                                </div>
                                <div class="count"><?php echo $risk_distribution['High'] ?? 0; ?></div>
                            </div>
                            <div class="risk-stat">
                                <div>
                                    <i class="fas fa-exclamation text-warning me-2"></i>
                                    <strong>Moderate Risk</strong>
                                </div>
                                <div class="count"><?php echo $risk_distribution['Moderate'] ?? 0; ?></div>
                            </div>
                            <div class="risk-stat">
                                <div>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Low Risk</strong>
                                </div>
                                <div class="count"><?php echo $risk_distribution['Low'] ?? 0; ?></div>
                            </div>
                            <?php if (($risk_distribution['Unknown'] ?? 0) > 0): ?>
                            <div class="risk-stat">
                                <div>
                                    <i class="fas fa-question-circle text-secondary me-2"></i>
                                    <strong>No Risk Data</strong>
                                </div>
                                <div class="count"><?php echo $risk_distribution['Unknown'] ?? 0; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4">
                            <h6>System Summary</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <strong>Total Patients:</strong> <?php echo $total_patients; ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-chart-line text-info me-2"></i>
                                    <strong>With Risk Assessment:</strong> <?php echo ($total_patients - ($risk_distribution['Unknown'] ?? 0)); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-thermometer-full text-danger me-2"></i>
                                    <strong>Requiring Attention:</strong> <?php echo ($risk_distribution['Critical'] ?? 0) + ($risk_distribution['High'] ?? 0); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script>
        // Prepare chart data excluding 'Unknown' if zero
        const riskLabels = [];
        const riskData = [];
        const riskColors = [];
        
        <?php
        // Create arrays for the chart
        $chart_labels = [];
        $chart_data = [];
        $chart_colors = [];
        
        $chart_config = [
            'Low' => ['color' => '#10b981'],
            'Moderate' => ['color' => '#f59e0b'],
            'High' => ['color' => '#ef4444'],
            'Critical' => ['color' => '#dc2626']
        ];
        
        foreach ($chart_config as $tier => $config) {
            $count = $risk_distribution[$tier] ?? 0;
            if ($count > 0) {
                $chart_labels[] = $tier;
                $chart_data[] = $count;
                $chart_colors[] = $config['color'];
            }
        }
        
        // Add Unknown if it exists
        if (($risk_distribution['Unknown'] ?? 0) > 0) {
            $chart_labels[] = 'No Data';
            $chart_data[] = $risk_distribution['Unknown'];
            $chart_colors[] = '#6b7280';
        }
        ?>
        
        const riskChartData = {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: <?php echo json_encode($chart_colors); ?>,
                borderColor: <?php echo json_encode($chart_colors); ?>,
                borderWidth: 2
            }]
        };
        
        createPieChart('riskChart', riskChartData);
    </script>
</body>
</html>