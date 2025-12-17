# Migration Guide - OvCare Enhancement

## Directory Structure Changes

The OvCare system has been reorganized with the following new structure:

### New Directories

```
ovcare/
├── patient/              # Enhanced patient portal (NEW)
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php     # Enhanced with temporal analysis
│   ├── data_entry.php
│   ├── history.php       # NEW - Temporal trend analysis
│   ├── profile.php       # NEW - User management
│   ├── alerts.php
│   └── logout.php
│
├── doctor/               # Doctor portal (NEW)
│   ├── login.php
│   ├── dashboard.php     # Multi-patient view
│   ├── patient_view.php  # Individual patient analysis
│   ├── analytics.php     # Aggregate statistics
│   └── logout.php
│
├── backend/              # Enhanced ML backend (NEW)
│   ├── app.py            # Flask API with temporal endpoints
│   ├── train.py          # Enhanced training with temporal features
│   ├── temporal_analysis.py
│   ├── requirements.txt
│   ├── model.pkl
│   └── train.csv
│
├── includes/             # Shared PHP libraries (NEW)
│   ├── config.php        # Configuration management
│   ├── functions.php     # Helper functions
│   ├── auth.php          # Authentication system
│   └── (db.php moved here conceptually, but kept at root for compatibility)
│
├── assets/               # Frontend assets (NEW)
│   ├── css/
│   │   ├── glassmorphism.css  # Dark theme
│   │   └── responsive.css
│   └── js/
│       ├── api.js        # API communication
│       ├── charts.js     # Chart.js visualizations
│       └── animations.js
│
└── sql/                  # Database schema (NEW)
    └── schema.sql
```

### Old Files (Preserved for Reference)

The following files remain at the root level but are **NOT** used by the enhanced system:

- `login.php` → Use `patient/login.php`
- `register.php` → Use `patient/register.php`
- `dashboard.php` → Use `patient/dashboard.php`
- `data_entry.php` → Use `patient/data_entry.php`
- `alerts.php` → Use `patient/alerts.php`
- `logout.php` → Use `patient/logout.php`
- `python-ml/` → Use `backend/` instead

These old files are preserved for backward compatibility and reference but should not be used.

## How to Use the Enhanced System

### For End Users

1. **Access the landing page**: `http://localhost/index.php`
2. **Patient access**: Click "Patient Sign Up" or "Patient Sign In"
3. **Doctor access**: Click "Doctor Portal"

All navigation from index.php now points to the correct enhanced pages.

### For Developers

#### File Organization

- **Patient features**: Add to `patient/` directory
- **Doctor features**: Add to `doctor/` directory
- **ML backend**: Modify files in `backend/` directory
- **Shared code**: Add to `includes/` directory
- **Frontend assets**: Add to `assets/` directory

#### Path References

When creating new pages:

```php
// Patient pages - use relative paths to parent directory
require_once '../db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Doctor pages - use relative paths to parent directory
require_once '../db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
```

#### Database Changes

Run the new schema:

```bash
mysql -u root -p ovarian_cancer_db < sql/schema.sql
```

This creates the enhanced tables while preserving existing data.

## Benefits of New Structure

1. **Separation of Concerns**: Patient and doctor functionality is clearly separated
2. **Enhanced ML Backend**: New backend/ directory contains temporal analysis capabilities
3. **Shared Libraries**: includes/ directory provides reusable components
4. **Better Organization**: Assets and SQL schemas have dedicated directories
5. **Security**: Enhanced authentication and authorization system
6. **Scalability**: Easier to add new features to specific user types

## What Changed

### Patient Portal

- ✅ Enhanced with temporal analysis
- ✅ New history page with trend visualization
- ✅ Profile management
- ✅ Glassmorphism dark theme
- ✅ Chart.js visualizations
- ✅ Secure authentication with bcrypt

### Doctor Portal (New)

- ✅ Multi-patient dashboard
- ✅ Individual patient detailed view
- ✅ Temporal velocity/acceleration indicators
- ✅ Clinical notes system
- ✅ Analytics and statistics

### ML Backend

- ✅ Temporal feature engineering
- ✅ Velocity and acceleration analysis
- ✅ 4-tier risk categorization
- ✅ Enhanced Flask API with new endpoints
- ✅ XGBoost/LightGBM support

## Backward Compatibility

The old root-level files are preserved to maintain backward compatibility. However, they:

- Do **NOT** include temporal analysis features
- Do **NOT** have the enhanced UI
- Do **NOT** have the doctor portal functionality
- Do **NOT** include security enhancements

**Recommendation**: Use the new `patient/` and `doctor/` directories for all functionality.

## Cleanup (Optional)

After verifying the enhanced system works correctly, you may optionally remove old files:

```bash
# WARNING: Only do this after testing the new system!
rm -f login.php register.php dashboard.php data_entry.php alerts.php logout.php predict_risk.php
rm -rf python-ml/
```

However, keeping them doesn't hurt and provides a fallback option.

## Questions?

If you encounter any issues:

1. Ensure you're accessing pages through `patient/` or `doctor/` directories
2. Check that index.php points to the new directories (it should after this update)
3. Verify the Flask API is running: `python backend/app.py`
4. Check database schema is updated: `mysql -u root -p ovarian_cancer_db < sql/schema.sql`

For detailed setup instructions, see README.md
For production deployment, see PRODUCTION.md
