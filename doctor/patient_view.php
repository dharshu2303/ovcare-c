<?php
// doctor/patient_view.php - Individual patient detailed view
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

$doctor_id = intval($_SESSION['doctor_id']);
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$patient_id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch patient information
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? AND user_type = 'patient'");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    header('Location: dashboard.php');
    exit;
}

// Fetch biomarker history
$stmt = $conn->prepare("SELECT * FROM biomarker_data WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 50");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$biomarker_history = [];
while ($row = $result->fetch_assoc()) {
    $biomarker_history[] = $row;
}
$stmt->close();

// Fetch risk history
$stmt = $conn->prepare("SELECT * FROM risk_history WHERE patient_id = ? ORDER BY calculated_at DESC LIMIT 30");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$risk_history = [];
while ($row = $result->fetch_assoc()) {
    $risk_history[] = $row;
}
$stmt->close();

// Fetch symptoms
$stmt = $conn->prepare("SELECT * FROM symptoms WHERE patient_id = ? ORDER BY logged_at DESC LIMIT 20");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$symptoms = [];
while ($row = $result->fetch_assoc()) {
    $symptoms[] = $row;
}
$stmt->close();

// Fetch doctor notes
$stmt = $conn->prepare("SELECT * FROM doctor_notes WHERE patient_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();

// Handle note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note_content = sanitize_input($_POST['note_content']);
    $stmt = $conn->prepare("INSERT INTO doctor_notes (patient_id, doctor_id, note_content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $patient_id, $doctor_id, $note_content);
    $stmt->execute();
    $stmt->close();
    header("Location: patient_view.php?id=$patient_id");
    exit;
}

$latest_biomarker = $biomarker_history[0] ?? null;
$latest_risk = $risk_history[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patient['name']); ?> - Patient View</title>
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Patient Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="glass-card">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="gradient-text mb-2">
                                    <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($patient['name']); ?>
                                </h2>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($patient['email']); ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-calendar me-2"></i>Age: <?php echo $patient['age']; ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <?php if ($latest_risk): ?>
                                <h3 class="mb-0">
                                    <span class="glass-badge glass-badge-<?php echo strtolower($latest_risk['risk_tier']); ?>" style="font-size: 1.2rem;">
                                        Risk: <?php echo htmlspecialchars($latest_risk['risk_tier']); ?>
                                        (<?php echo number_format($latest_risk['probability'] * 100, 1); ?>%)
                                    </span>
                                </h3>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Biomarkers & Risk -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">CA125 Level</h6>
                        <h2 class="mb-0"><?php echo $latest_biomarker ? number_format($latest_biomarker['CA125'], 2) : 'N/A'; ?></h2>
                        <?php if ($latest_risk && $latest_risk['ca125_velocity']): ?>
                        <small class="<?php echo $latest_risk['ca125_velocity'] > 0 ? 'text-danger' : 'text-success'; ?>">
                            <i class="fas fa-arrow-<?php echo $latest_risk['ca125_velocity'] > 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo number_format(abs($latest_risk['ca125_velocity']), 2); ?>/day
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">HE4 Level</h6>
                        <h2 class="mb-0"><?php echo $latest_biomarker ? number_format($latest_biomarker['HE4'], 2) : 'N/A'; ?></h2>
                        <?php if ($latest_risk && $latest_risk['he4_velocity']): ?>
                        <small class="<?php echo $latest_risk['he4_velocity'] > 0 ? 'text-danger' : 'text-success'; ?>">
                            <i class="fas fa-arrow-<?php echo $latest_risk['he4_velocity'] > 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo number_format(abs($latest_risk['he4_velocity']), 2); ?>/day
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">Heart Rate</h6>
                        <h2 class="mb-0"><?php echo $latest_biomarker ? number_format($latest_biomarker['heart_rate'], 0) : 'N/A'; ?></h2>
                        <small class="text-muted">bpm</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="glass-card text-center">
                        <h6 class="text-muted">Temperature</h6>
                        <h2 class="mb-0"><?php echo $latest_biomarker ? number_format($latest_biomarker['temperature'], 1) : 'N/A'; ?></h2>
                        <small class="text-muted">°F</small>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Temporal Trends</h5>
                        <div class="chart-container-responsive">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-notes-medical me-2"></i>Medical History</h5>
                        <p><?php echo nl2br(htmlspecialchars($patient['medical_history'] ?? 'No medical history recorded.')); ?></p>
                    </div>
                </div>
            </div>

            <!-- Symptoms & Notes -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-stethoscope me-2"></i>Recent Symptoms</h5>
                        <?php if (!empty($symptoms)): ?>
                        <div class="list-group">
                            <?php foreach (array_slice($symptoms, 0, 5) as $symptom): ?>
                            <div class="list-group-item bg-transparent border-bottom border-secondary">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($symptom['symptom_type']); ?></strong>
                                    <span class="glass-badge glass-badge-warning">Severity: <?php echo $symptom['severity']; ?>/10</span>
                                </div>
                                <small class="text-muted"><?php echo format_datetime($symptom['logged_at']); ?></small>
                                <p class="mb-0 mt-2"><?php echo htmlspecialchars($symptom['description']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No symptoms recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-clipboard me-2"></i>Doctor Notes</h5>
                        <form method="POST" class="mb-3">
                            <textarea name="note_content" class="glass-input" rows="3" placeholder="Add a note..." required></textarea>
                            <button type="submit" name="add_note" class="glass-btn glass-btn-primary mt-2">
                                <i class="fas fa-plus me-2"></i>Add Note
                            </button>
                        </form>
                        <?php if (!empty($notes)): ?>
                        <div class="list-group">
                            <?php foreach ($notes as $note): ?>
                            <div class="list-group-item bg-transparent border-bottom border-secondary">
                                <small class="text-muted"><?php echo format_datetime($note['created_at']); ?></small>
                                <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($note['note_content'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No notes yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script>
        // Prepare chart data
        const biomarkers = <?php echo json_encode(array_reverse($biomarker_history)); ?>;
        const dates = biomarkers.map(b => new Date(b.recorded_at).toLocaleDateString());
        const ca125 = biomarkers.map(b => parseFloat(b.CA125));
        const he4 = biomarkers.map(b => parseFloat(b.HE4));
        
        // Create trends chart
        createTrendChart('trendsChart', {
            labels: dates,
            datasets: [
                {
                    label: 'CA125',
                    data: ca125,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'HE4',
                    data: he4,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.3
                }
            ]
        });
    </script>
</body>
</html>
