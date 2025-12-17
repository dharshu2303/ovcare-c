<?php
// patient/profile.php - User profile management
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

$patient_id = intval($_SESSION['patient_id']);
$message = '';
$error = '';

// Fetch patient data
$stmt = $conn->prepare("SELECT name, email, age, phone, date_of_birth, medical_history FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $dob = sanitize_input($_POST['date_of_birth']);
        $medical_history = sanitize_input($_POST['medical_history']);
        
        $stmt = $conn->prepare("UPDATE patients SET name = ?, email = ?, phone = ?, date_of_birth = ?, medical_history = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $dob, $medical_history, $patient_id);
        
        if ($stmt->execute()) {
            $message = 'Profile updated successfully!';
            $_SESSION['patient_name'] = $name;
            // Refresh patient data
            $patient['name'] = $name;
            $patient['email'] = $email;
            $patient['phone'] = $phone;
            $patient['date_of_birth'] = $dob;
            $patient['medical_history'] = $medical_history;
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM patients WHERE id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $password_valid = false;
        if (password_get_info($row['password'])['algo'] !== null) {
            $password_valid = verify_password($current_password, $row['password']);
        } else {
            $password_valid = ($current_password === $row['password']);
        }
        
        if (!$password_valid) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashed = hash_password($new_password);
            $stmt = $conn->prepare("UPDATE patients SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $patient_id);
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - OvCare</title>
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
                    <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i>History</a></li>
                    <li class="nav-item"><a class="nav-link" href="alerts.php"><i class="fas fa-bell me-1"></i>Alerts</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="gradient-text mb-2"><i class="fas fa-user me-2"></i>Profile Settings</h1>
                    <p class="text-secondary">Manage your account information</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Profile Information -->
                    <div class="glass-card mb-4">
                        <h5 class="mb-4"><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="glass-input" value="<?php echo htmlspecialchars($patient['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="glass-input" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="glass-input" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="glass-input" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Medical History</label>
                                <textarea name="medical_history" class="glass-input" rows="4" placeholder="Enter relevant medical history..."><?php echo htmlspecialchars($patient['medical_history'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_profile" class="glass-btn glass-btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="glass-card">
                        <h5 class="mb-4"><i class="fas fa-key me-2"></i>Change Password</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="glass-input" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="glass-input" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="glass-input" minlength="6" required>
                            </div>
                            <button type="submit" name="change_password" class="glass-btn glass-btn-primary">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Account Summary -->
                    <div class="glass-card mb-4">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Account Summary</h5>
                        <div class="mb-3">
                            <small class="text-muted">Name</small>
                            <p class="mb-0"><?php echo htmlspecialchars($patient['name']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Email</small>
                            <p class="mb-0"><?php echo htmlspecialchars($patient['email']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Age</small>
                            <p class="mb-0"><?php echo htmlspecialchars($patient['age']); ?> years</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="glass-card">
                        <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        <a href="dashboard.php" class="glass-btn w-100 mb-2">
                            <i class="fas fa-chart-line me-2"></i>View Dashboard
                        </a>
                        <a href="data_entry.php" class="glass-btn w-100 mb-2">
                            <i class="fas fa-notes-medical me-2"></i>Enter Data
                        </a>
                        <a href="history.php" class="glass-btn w-100">
                            <i class="fas fa-history me-2"></i>View History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/animations.js"></script>
</body>
</html>
