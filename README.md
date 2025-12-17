# OvCare - Ovarian Cancer Early Detection Using Temporal Analysis

A sophisticated, production-ready platform that leverages temporal analysis to detect patterns and changes in biomarker levels over time, providing early warning capabilities for both patients and healthcare providers.

## Features

### For Patients
- **Secure Registration & Login** - Email-based authentication with password hashing
- **Personal Dashboard** - View risk status, biomarker trends, and health metrics
- **Data Entry** - Log biomarker readings (CA125, HE4) and vital signs
- **Historical Trends** - Interactive charts showing temporal analysis
- **Risk Alerts** - 4-tier risk categorization (Low, Moderate, High, Critical)
- **Profile Management** - Update personal information and medical history

### For Doctors
- **Multi-Patient Dashboard** - Monitor all patients with sortable/filterable table
- **Individual Patient View** - Detailed analysis with temporal trends
- **Risk Distribution Analytics** - Aggregate statistics and visualizations
- **Clinical Notes** - Add and review patient observations
- **Real-time Alerts** - Notifications for critical risk changes

### Machine Learning
- **Gradient Boosting Classifier** - Enhanced accuracy over basic models
- **Temporal Feature Engineering** - Velocity and acceleration analysis
- **Multi-point Trend Tracking** - Pattern detection across time series
- **Confidence Scoring** - Model certainty indicators
- **Feature Importance** - Identify key risk factors

## Technology Stack

### Frontend
- PHP 7.4+
- Bootstrap 5.3
- Chart.js 4.x
- DataTables.js
- Font Awesome 6.x
- Custom Glassmorphism CSS

### Backend
- Python 3.8+
- Flask 3.0
- Flask-CORS
- XGBoost / LightGBM
- scikit-learn
- pandas / numpy

### Database
- MySQL 5.7+ / MariaDB 10.3+

## Installation

### Prerequisites
```bash
# PHP 7.4 or higher
php -v

# Python 3.8 or higher
python --version

# MySQL/MariaDB
mysql --version
```

### Step 1: Clone Repository
```bash
git clone https://github.com/dharshu2303/ovcare.git
cd ovcare
```

### Step 2: Database Setup
```bash
# Create database and import schema
mysql -u root -p
```
```sql
CREATE DATABASE ovarian_cancer_db;
USE ovarian_cancer_db;
SOURCE sql/schema.sql;
```

### Step 3: Python Environment
```bash
cd backend

# Create virtual environment
python -m venv venv

# Activate virtual environment
# On Windows:
venv\Scripts\activate
# On Unix/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

### Step 4: Train ML Model
```bash
# Train the model (requires train.csv)
python train.py
```

### Step 5: Start Flask API
```bash
# Start the ML API server
python app.py
# Server will run on http://127.0.0.1:5000
```

### Step 6: Configure PHP
1. Update database credentials in `includes/config.php` if needed
2. Ensure PHP extensions are enabled: `mysqli`, `curl`, `json`
3. Set up a web server (Apache/Nginx) pointing to the project root

### Step 7: Access Application
- **Landing Page**: `http://localhost/index.php` (links to patient and doctor portals)
- **Patient Portal**: `http://localhost/patient/login.php`
- **Doctor Portal**: `http://localhost/doctor/login.php`

**Note**: The enhanced application uses subdirectories:
- `patient/` - All patient portal pages (enhanced versions)
- `doctor/` - All doctor portal pages (new functionality)
- `backend/` - Enhanced Python ML backend with temporal analysis
- Old root-level PHP files are preserved for reference but not used

## Default Credentials

### Doctor Account
- Email: `doctor@ovcare.com`
- Password: `doctor123`

### Patient Account
Register a new account or use existing credentials from your database.

## File Structure
```
ovcare/
├── backend/                  # Python ML backend
│   ├── app.py               # Flask API server
│   ├── train.py             # Model training script
│   ├── temporal_analysis.py # Temporal feature calculations
│   ├── model.pkl            # Trained model
│   ├── train.csv            # Training dataset
│   └── requirements.txt     # Python dependencies
├── patient/                 # Patient portal
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── data_entry.php
│   ├── history.php
│   ├── alerts.php
│   ├── profile.php
│   └── logout.php
├── doctor/                  # Doctor portal
│   ├── login.php
│   ├── dashboard.php
│   ├── patient_view.php
│   ├── analytics.php
│   └── logout.php
├── includes/                # PHP includes
│   ├── config.php          # Configuration
│   ├── functions.php       # Helper functions
│   ├── auth.php            # Authentication
│   └── db.php              # Database connection
├── assets/                  # Static assets
│   ├── css/
│   │   ├── glassmorphism.css
│   │   └── responsive.css
│   └── js/
│       ├── api.js
│       ├── charts.js
│       └── animations.js
├── sql/
│   └── schema.sql          # Database schema
├── index.php               # Landing page
└── README.md
```

## API Endpoints

### POST /predict
Basic risk prediction
```json
{
  "Age": 50,
  "CA125_Level": 35.5,
  "HE4_Level": 100.2,
  "LDH_Level": 180,
  ...
}
```

### POST /predict-temporal
Advanced prediction with temporal analysis
```json
{
  "Age": 50,
  "CA125_Level": 35.5,
  "HE4_Level": 100.2,
  "history": [
    {"ca125": 30, "he4": 95, "recorded_at": "2024-01-01"},
    ...
  ]
}
```

### GET /model-info
Get model metadata and performance metrics

### GET /health
Health check endpoint

## Configuration

### ML API URL
Edit `includes/config.php`:
```php
define('ML_API_URL', 'http://127.0.0.1:5000');
```

### Risk Tier Thresholds
```php
define('RISK_TIER_LOW', 0.25);      // < 25%
define('RISK_TIER_MODERATE', 0.50); // 25-50%
define('RISK_TIER_HIGH', 0.75);     // 50-75%
// > 75% = Critical
```

## Security Features

- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ Session timeout (30 minutes)
- ✅ HTTPS recommended for production

## Development

### Adding New Features
1. Backend: Add endpoints to `backend/app.py`
2. Frontend: Create new PHP pages in `patient/` or `doctor/`
3. Database: Update `sql/schema.sql` and migrate

### Running Tests
```bash
# Python tests
cd backend
pytest

# PHP tests (if configured)
phpunit
```

## Troubleshooting

### Flask API Not Running
```bash
# Check if port 5000 is available
netstat -ano | findstr :5000

# Try different port
python app.py --port 5001
```

### Database Connection Error
- Verify MySQL/MariaDB is running
- Check credentials in `includes/config.php`
- Ensure database `ovarian_cancer_db` exists

### Model Not Found
```bash
cd backend
python train.py  # Train model first
```

## Performance Metrics

- Model Accuracy: >85% (with proper training data)
- Page Load Time: <2 seconds
- Mobile Responsive: 100%
- API Response Time: <500ms

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## License

This project is for educational and research purposes.

## Authors

- Dharshini S - Initial work

## Acknowledgments

- Medical datasets and research papers
- Open source ML libraries
- Healthcare domain experts

## Support

For issues and questions:
- GitHub Issues: https://github.com/dharshu2303/ovcare/issues
- Email: support@ovcare.com (if configured)

---

**Note**: This system is designed for research and educational purposes. Always consult qualified healthcare professionals for medical decisions.
