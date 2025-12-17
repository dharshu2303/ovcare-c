<?php
/**
 * Configuration file for OvCare
 * Contains application-wide settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ovarian_cancer_db');

// Application settings
define('APP_NAME', 'OvCare');
define('APP_VERSION', '2.0.0');

// Session settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// Flask ML API settings
define('ML_API_URL', 'http://127.0.0.1:5000');
define('ML_API_TIMEOUT', 6);

// Risk tier thresholds
define('RISK_TIER_LOW', 0.25);
define('RISK_TIER_MODERATE', 0.50);
define('RISK_TIER_HIGH', 0.75);

// Security settings
define('BCRYPT_COST', 10);

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('REPORTS_PATH', ROOT_PATH . '/reports');

// Ensure reports directory exists
if (!file_exists(REPORTS_PATH)) {
    mkdir(REPORTS_PATH, 0755, true);
}

// Error reporting (for development only - DISABLE in production)
// For production, set display_errors to '0' and log errors to a file
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
} else {
    // Development mode
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
?>
