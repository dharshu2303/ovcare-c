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
