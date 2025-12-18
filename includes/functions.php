<?php
/**
 * Helper functions for OvCare application
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Hash password using bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['patient_id']) || isset($_SESSION['doctor_id']);
}

/**
 * Check if user is a patient
 */
function is_patient() {
    return isset($_SESSION['patient_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient';
}

/**
 * Check if user is a doctor
 */
function is_doctor() {
    return isset($_SESSION['doctor_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'doctor';
}

/**
 * Require patient login
 */
function require_patient_login() {
    if (!is_patient()) {
        header('Location: ../patient/login.php');
        exit;
    }
}

/**
 * Require doctor login
 */
function require_doctor_login() {
    if (!is_doctor()) {
        header('Location: ../doctor/login.php');
        exit;
    }
}

/**
 * Get risk tier from probability
 */
function get_risk_tier($probability) {
    if ($probability < RISK_TIER_LOW) {
        return 'Low';
    } elseif ($probability < RISK_TIER_MODERATE) {
        return 'Moderate';
    } elseif ($probability < RISK_TIER_HIGH) {
        return 'High';
    } else {
        return 'Critical';
    }
}

/**
 * Get risk tier color
 */
function get_risk_tier_color($tier) {
    switch ($tier) {
        case 'Low':
            return '#10b981'; // green
        case 'Moderate':
            return '#f59e0b'; // amber
        case 'High':
            return '#ef4444'; // red
        case 'Critical':
            return '#dc2626'; // dark red
        default:
            return '#6b7280'; // gray
    }
}


function get_patient_risk_summary($conn, $patient_id) {
    $stmt = $conn->prepare("
        SELECT probability, ca125, he4, ca125_velocity, he4_velocity, calculated_at 
        FROM risk_history 
        WHERE patient_id = ? 
        ORDER BY calculated_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();

    if (empty($records)) {
        return null;
    }

    $baseline_probability = 0;
    $baseline_ca125 = 0;
    $baseline_he4 = 0;
    $count = count($records);
    
    foreach ($records as $record) {
        $baseline_probability += floatval($record['probability']);
        $baseline_ca125 += floatval($record['ca125']);
        $baseline_he4 += floatval($record['he4']);
    }
    
    $baseline_probability /= $count;
    $baseline_ca125 /= $count;
    $baseline_he4 /= $count;
    
    // Get latest record for comparison
    $latest = $records[0];
    $latest_probability = floatval($latest['probability']);
    $latest_ca125 = floatval($latest['ca125']);
    $latest_he4 = floatval($latest['he4']);
    
    // Analyze trends (check if biomarkers are increasing)
    $is_ca125_increasing = $latest_ca125 > $baseline_ca125;
    $is_he4_increasing = $latest_he4 > $baseline_he4;
    $is_probability_increasing = $latest_probability > $baseline_probability;
    $trend_multiplier = 1.0;
    
    // If latest values are higher than historical average, increase risk
    if ($latest_ca125 > $baseline_ca125 * 1.1) {
        $trend_multiplier += 0.1;
    }
    if ($latest_he4 > $baseline_he4 * 1.1) {
        $trend_multiplier += 0.1;
    }
    
    // Factor in velocity (rate of change)
    $ca125_velocity = floatval($latest['ca125_velocity'] ?? 0);
    $he4_velocity = floatval($latest['he4_velocity'] ?? 0);
    
    if ($ca125_velocity > 5) {
        $trend_multiplier += 0.15; // Rapid CA125 increase
    }
    if ($he4_velocity > 10) {
        $trend_multiplier += 0.15; // Rapid HE4 increase
    }
    

    if ($latest_ca125 < $baseline_ca125 * 0.9 && $latest_he4 < $baseline_he4 * 0.9) {
        $trend_multiplier -= 0.1;
    }
    
    // Apply trend multiplier to baseline probability
    $adjusted_probability = $baseline_probability * $trend_multiplier;
    $adjusted_probability = max(0.0, min(1.0, $adjusted_probability)); // Clamp to [0, 1]
    
    // Blend adjusted probability with latest for stability (70% historical, 30% latest)
    $final_probability = ($adjusted_probability * 0.7) + ($latest_probability * 0.3);
    
    return [
        'probability' => $final_probability,
        'risk_tier' => get_risk_tier($final_probability),
        'calculated_at' => $latest['calculated_at'],
        'trend' => $is_probability_increasing ? 'increasing' : 'stable',
        'comparison' => [
            'baseline_ca125' => $baseline_ca125,
            'latest_ca125' => $latest_ca125,
            'baseline_he4' => $baseline_he4,
            'latest_he4' => $latest_he4,
            'ca125_change_percent' => $baseline_ca125 > 0 ? (($latest_ca125 - $baseline_ca125) / $baseline_ca125 * 100) : 0,
            'he4_change_percent' => $baseline_he4 > 0 ? (($latest_he4 - $baseline_he4) / $baseline_he4 * 100) : 0
        ]
    ];
}

/**
 * Format date
 */
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime, $format = 'M d, Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate velocity (rate of change)
 */
function calculate_velocity($current_value, $previous_value, $time_diff_days) {
    if ($time_diff_days == 0) {
        return 0;
    }
    return ($current_value - $previous_value) / $time_diff_days;
}

/**
 * Call ML prediction API
 */
function call_ml_api($endpoint, $data) {
    $url = ML_API_URL . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, ML_API_TIMEOUT);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return ['error' => "ML service unreachable: $error"];
    }
    
    if ($http_code !== 200) {
        return ['error' => "ML service returned error: HTTP $http_code"];
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        return ['error' => 'Invalid response from ML service'];
    }
    
    return $result;
}

/**
 * Create notification
 */
function create_notification($conn, $user_id, $user_type, $message, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $user_type, $message, $type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get unread notification count
 */
function get_unread_notification_count($conn, $user_id, $user_type) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = FALSE");
    $stmt->bind_param("is", $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] ?? 0;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}
?>
