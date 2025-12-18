<?php
// doctor/dashboard.php - Multi-patient dashboard for doctors
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

$doctor_id = intval($_SESSION['doctor_id']);
$doctor_name = $_SESSION['doctor_name'];
$message = '';
$message_type = 'success';

// Handle doctor-driven biomarker data entry for any patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $patient_id = intval($_POST['patient_id']);
    $CA125 = floatval($_POST['CA125'] ?? 0);
    $HE4 = floatval($_POST['HE4'] ?? 0);
    $heart_rate = floatval($_POST['heart_rate'] ?? 0);
    $temperature = floatval($_POST['temperature'] ?? 0);
    $sleep_hours = floatval($_POST['sleep_hours'] ?? 0);
    $symptoms = trim($_POST['symptoms'] ?? '');

    // Verify patient exists and is a patient account
    $verify_stmt = $conn->prepare("SELECT id FROM patients WHERE id = ? AND user_type = 'patient'");
    $verify_stmt->bind_param("i", $patient_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_stmt->close();

    if ($verify_result->num_rows !== 1) {
        $message = 'Invalid patient selected.';
        $message_type = 'danger';
    } else {
        $recorded_at = date('Y-m-d H:i:s');
        $insert_stmt = $conn->prepare("INSERT INTO biomarker_data (patient_id, CA125, HE4, heart_rate, temperature, sleep_hours, symptoms, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("idddddss", $patient_id, $CA125, $HE4, $heart_rate, $temperature, $sleep_hours, $symptoms, $recorded_at);

        if ($insert_stmt->execute()) {
            $message = 'Biomarker data saved for patient.';
            $message_type = 'success';

            // Handle optional scan/report upload
            $scan_upload_path = null;
            if (isset($_FILES['scan_report']) && isset($_FILES['scan_report']['error'])) {
                if ($_FILES['scan_report']['error'] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['scan_report']['tmp_name'];
                    $orig = basename($_FILES['scan_report']['name']);
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','pdf'];
                    if (in_array($ext, $allowed)) {
                        $upload_dir = __DIR__ . '/../uploads/scans';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $newname = uniqid('scan_') . '.' . $ext;
                        $dest = $upload_dir . '/' . $newname;
                        if (move_uploaded_file($tmp, $dest)) {
                            $scan_upload_path = 'uploads/scans/' . $newname;
                            // Attempt to record in scan_reports table if present
                            $stmt = @$conn->prepare("INSERT INTO scan_reports (patient_id, file_path, uploaded_at) VALUES (?, ?, ?)");
                            if ($stmt) {
                                $uploaded_at = date('Y-m-d H:i:s');
                                $stmt->bind_param("iss", $patient_id, $scan_upload_path, $uploaded_at);
                                $stmt->execute();
                                $stmt->close();
                            }
                        } else {
                            $message = 'Biomarker saved but failed to move uploaded scan.';
                            $message_type = 'warning';
                        }
                    } elseif ($_FILES['scan_report']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $message = 'Invalid scan file type. Allowed: jpg, jpeg, png, gif, pdf.';
                        $message_type = 'danger';
                    }
                } elseif ($_FILES['scan_report']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $message = 'Error uploading scan file.';
                    $message_type = 'danger';
                }
            }

            // Calculate risk automatically after saving biomarker data
            // Fetch previous biomarker data to calculate velocity
            $prev_stmt = $conn->prepare("SELECT CA125, HE4, recorded_at FROM biomarker_data WHERE patient_id = ? AND recorded_at < ? ORDER BY recorded_at DESC LIMIT 1");
            $prev_stmt->bind_param("is", $patient_id, $recorded_at);
            $prev_stmt->execute();
            $prev_result = $prev_stmt->get_result();
            $prev_data = $prev_result->fetch_assoc();
            $prev_stmt->close();
            
            $ca125_velocity = 0;
            $he4_velocity = 0;
            
            if ($prev_data) {
                $time_diff = (strtotime($recorded_at) - strtotime($prev_data['recorded_at'])) / 86400; // days
                if ($time_diff > 0) {
                    $ca125_velocity = ($CA125 - $prev_data['CA125']) / $time_diff;
                    $he4_velocity = ($HE4 - $prev_data['HE4']) / $time_diff;
                }
            }
            
            // Simple risk calculation (you can enhance this with ML API call)
            $risk_score = 0;
            if ($CA125 > 35) $risk_score += 0.3;
            if ($HE4 > 140) $risk_score += 0.3;
            if ($ca125_velocity > 5) $risk_score += 0.2;
            if ($he4_velocity > 10) $risk_score += 0.2;
            
            $probability = min($risk_score, 1.0);
            $risk_tier = get_risk_tier($probability);
            
            // Insert into risk_history
            $risk_stmt = $conn->prepare("INSERT INTO risk_history (patient_id, risk_score, risk_tier, probability, ca125, he4, ca125_velocity, he4_velocity, calculated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $risk_stmt->bind_param("idssdddds", $patient_id, $risk_score, $risk_tier, $probability, $CA125, $HE4, $ca125_velocity, $he4_velocity, $recorded_at);
            $risk_stmt->execute();
            $risk_stmt->close();
        } else {
            $message = 'Failed to save biomarker data.';
            $message_type = 'danger';
        }
        $insert_stmt->close();
    }
}

// Fetch all patients with their latest risk assessment
$query = "SELECT id, name, email, age FROM patients WHERE user_type = 'patient' ORDER BY name ASC";
$result = $conn->query($query);
$patients = [];
$risk_stats = ['Low' => 0, 'Moderate' => 0, 'High' => 0, 'Critical' => 0];

while ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
    
    // Get latest biomarker data (include vitals and symptoms for ML payload)
    $bio_stmt = $conn->prepare("SELECT CA125, HE4, heart_rate, temperature, sleep_hours, symptoms, recorded_at FROM biomarker_data WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $bio_stmt->bind_param("i", $patient_id);
    $bio_stmt->execute();
    $bio_result = $bio_stmt->get_result();
    $bio_data = $bio_result->fetch_assoc();
    $bio_stmt->close();
    
    $row['CA125'] = $bio_data['CA125'] ?? null;
    $row['HE4'] = $bio_data['HE4'] ?? null;
    $row['recorded_at'] = $bio_data['recorded_at'] ?? null;
    
    // Default values before prediction
    $row['risk_tier'] = null;
    $row['probability'] = null;
    $row['calculated_at'] = null;

    // First, try to get latest risk from risk_history table
    $risk_stmt = $conn->prepare("SELECT risk_tier, probability, calculated_at FROM risk_history WHERE patient_id = ? ORDER BY calculated_at DESC LIMIT 1");
    $risk_stmt->bind_param("i", $patient_id);
    $risk_stmt->execute();
    $risk_result = $risk_stmt->get_result();
    $risk_data = $risk_result->fetch_assoc();
    $risk_stmt->close();

    if ($risk_data) {
        $row['risk_tier'] = $risk_data['risk_tier'];
        $row['probability'] = isset($risk_data['probability']) ? floatval($risk_data['probability']) : null;
        $row['calculated_at'] = $risk_data['calculated_at'];
    } else {
        // If no risk_history, try ML prediction as fallback
        if ($bio_data) {
            $patient_age = intval($row['age']);
            $payload = [
                "Age" => $patient_age,
                "CA125_Level" => floatval($bio_data['CA125']),
                "HE4_Level" => floatval($bio_data['HE4']),
                "LDH_Level" => 180.0,
                "Hemoglobin" => 13.0,
                "WBC" => isset($bio_data['heart_rate']) ? floatval($bio_data['heart_rate']) * 100.0 : 7000.0,
                "Platelets" => 250000.0,
                "Ovary_Size" => 3.5,
                "Fatigue_Level" => 5,
                "Pelvic_Pain" => 0,
                "Abdominal_Bloating" => 0,
                "Early_Satiety" => 0,
                "Menstrual_Irregularities" => 0,
                "Weight_Change" => 0.0
            ];

            $ml_result = call_ml_api('/predict', $payload);
            if (is_array($ml_result) && empty($ml_result['error'])) {
                if (isset($ml_result['probability'])) {
                    $row['probability'] = floatval($ml_result['probability']);
                    $row['risk_tier'] = get_risk_tier($row['probability']);
                    $row['calculated_at'] = $row['recorded_at'];
                } elseif (isset($ml_result['risk'])) {
                    $row['probability'] = $ml_result['risk'] ? 0.75 : 0.25;
                    $row['risk_tier'] = $ml_result['risk'] ? 'High' : 'Low';
                    $row['calculated_at'] = $row['recorded_at'];
                }
            }
        }
    }
    
    $patients[] = $row;
    $tier = $row['risk_tier'];
    if ($tier && isset($risk_stats[$tier])) {
        $risk_stats[$tier]++;
    } elseif (!$tier) {
        // If no risk tier, count as Low for statistics
        $risk_stats['Low']++;
    }
}

$total_patients = count($patients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - OvCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        .stat-card {
            text-align: center;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .nav-item .nav-link {
            color: white!important;
        }
        .patient-table {
            background: transparent !important;
            color: var(--text-primary) !important;
        }
        .patient-table thead {
            background: rgba(255, 255, 255, 0.05);
        }
        .patient-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .patient-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>
<body class="dark-theme">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg glass-navbar sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand gradient-text" href="../index.php">
                <i class="fas fa-heartbeat me-2"></i>OvCare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-th-large me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-1"></i>Analytics</a></li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($doctor_name); ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="gradient-text mb-2"><i class="fas fa-th-large me-2"></i>Doctor Dashboard</h1>
                    <p class="text-secondary">Monitor and manage all patients</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> glass-card mb-4">
                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card">
                        <div class="stat-value text-primary"><?php echo $total_patients; ?></div>
                        <div class="stat-label">Total Patients</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card glass-card-danger">
                        <div class="stat-value" style="color: var(--critical);"><?php echo $risk_stats['Critical']; ?></div>
                        <div class="stat-label">Critical Risk</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card glass-card-warning">
                        <div class="stat-value" style="color: var(--danger);"><?php echo $risk_stats['High']; ?></div>
                        <div class="stat-label">High Risk</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card stat-card glass-card-success">
                        <div class="stat-value" style="color: var(--success);"><?php echo $risk_stats['Low']; ?></div>
                        <div class="stat-label">Low Risk</div>
                    </div>
                </div>
            </div>


            <div class="row mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-notes-medical me-2"></i>Add Biomarker Entry</h5>
                        <form method="POST" class="row g-3" enctype="multipart/form-data">
                            <div class="col-md-4">
                                <label class="form-label">Patient</label>
                                <select name="patient_id" class="form-select" required>
                                    <option value="" disabled selected>Select patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['name'] . ' (' . $patient['email'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CA125 (U/mL)</label>
                                <input type="number" step="any" name="CA125" class="form-control" placeholder="e.g., 35.5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">HE4 (pmol/L)</label>
                                <input type="number" step="any" name="HE4" class="form-control" placeholder="e.g., 100" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Heart Rate (bpm)</label>
                                <input type="number" step="any" name="heart_rate" class="form-control" placeholder="e.g., 72" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Temperature (°C)</label>
                                <input type="number" step="any" name="temperature" class="form-control" placeholder="e.g., 36.5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sleep Hours</label>
                                <input type="number" step="any" name="sleep_hours" class="form-control" placeholder="e.g., 7.5" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Symptoms / Notes</label>
                                <textarea name="symptoms" class="form-control" rows="2" placeholder="fatigue, abdominal pain"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload Scan / Report (image or PDF)</label>
                                <input type="file" name="scan_report" class="form-control" accept="image/*,application/pdf">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn glass-btn"><i class="fas fa-save me-2"></i>Save Entry</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Risk Distribution Chart -->
            <div class="row mb-4">
                <div class="col-lg-4">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Risk Distribution</h5>
                        <div class="chart-container-responsive" style="height: 300px;">
                            <canvas id="riskDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-bell me-2"></i>Recent Alerts</h5>
                        <div class="alert alert-white glass-card-danger mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Critical:</strong> 2 patients require immediate attention
                        </div>
                        <div class="alert alert-white glass-card-warning mb-2">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>High Risk:</strong> 3 patients showing elevated biomarkers
                        </div>
                        <div class="alert alert-white glass-card-primary mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Update:</strong> 5 new data entries recorded today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient List Table -->
            <div class="row">
                <div class="col-12">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-users me-2"></i>Patient List</h5>
                        <div class="table-responsive">
                            <table id="patientTable" class="table patient-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Email</th>
                                        <th>Risk Tier</th>
                                        <th>Probability</th>
                                        <th>CA125</th>
                                        <th>HE4</th>
                                        <th>Last Update</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['age']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td>
                                            <?php
                                            $tier = $patient['risk_tier'] ?? 'Unknown';
                                            $color = get_risk_tier_color($tier);
                                            ?>
                                            <span class="glass-badge" style="background-color: <?php echo $color; ?>20; border-color: <?php echo $color; ?>; color: <?php echo $color; ?>;">
                                                <?php echo htmlspecialchars($tier); ?>
                                            </span>
                                        </td>
                                        <td><?php echo isset($patient['probability']) && $patient['probability'] !== null ? number_format($patient['probability'] * 100, 1) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $patient['CA125'] ? number_format($patient['CA125'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $patient['HE4'] ? number_format($patient['HE4'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $patient['recorded_at'] ? format_datetime($patient['recorded_at']) : 'N/A'; ?></td>
                                        <td>
                                            <a href="patient_view.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm glass-btn-black">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#patientTable').DataTable({
                order: [[4, 'desc']], // Sort by probability
                pageLength: 25,
                language: {
                    search: '<i class="fas fa-search me-2"></i>',
                    searchPlaceholder: 'Search patients...'
                }
            });
        });

        // Risk distribution chart
        const riskData = {
            labels: ['Low', 'Moderate', 'High', 'Critical'],
            datasets: [{
                data: [
                    <?php echo $risk_stats['Low']; ?>,
                    <?php echo $risk_stats['Moderate']; ?>,
                    <?php echo $risk_stats['High']; ?>,
                    <?php echo $risk_stats['Critical']; ?>
                ],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(220, 38, 38, 0.8)'
                ],
                borderColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#dc2626'
                ],
                borderWidth: 2
            }]
        };

        createPieChart('riskDistributionChart', riskData);
    </script>
</body>
</html>
