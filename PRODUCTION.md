# Production Deployment Guide for OvCare

## Pre-Deployment Checklist

### 1. Security Configuration

#### Change Default Doctor Password
```sql
-- Connect to database
mysql -u root -p ovarian_cancer_db

-- Update doctor password with a strong password
UPDATE doctors 
SET password_hash = '$2y$10$YOUR_NEW_BCRYPT_HASH_HERE' 
WHERE email = 'doctor@ovcare.com';

-- Or create a new doctor account and delete the default one
DELETE FROM doctors WHERE email = 'doctor@ovcare.com';
```

To generate a new bcrypt hash in PHP:
```php
<?php
echo password_hash('your_strong_password_here', PASSWORD_BCRYPT);
?>
```

#### Update Configuration
Edit `includes/config.php`:

```php
// Set production environment
putenv('APP_ENV=production');

// Update database credentials
define('DB_HOST', 'your_production_host');
define('DB_USER', 'your_production_user');
define('DB_PASS', 'your_strong_password');
define('DB_NAME', 'ovarian_cancer_db');

// Update ML API URL if hosted separately
define('ML_API_URL', 'https://your-api-domain.com');
```

### 2. Database Setup

```bash
# Create production database
mysql -u root -p

CREATE DATABASE ovarian_cancer_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ovcare_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON ovarian_cancer_db.* TO 'ovcare_user'@'localhost';
FLUSH PRIVILEGES;

# Import schema
mysql -u ovcare_user -p ovarian_cancer_db < sql/schema.sql

# IMPORTANT: Change the default doctor password immediately!
```

### 3. Python ML API Setup

#### Option A: Systemd Service (Linux)
Create `/etc/systemd/system/ovcare-api.service`:

```ini
[Unit]
Description=OvCare ML API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/ovcare/backend
Environment="PATH=/var/www/ovcare/backend/venv/bin"
ExecStart=/var/www/ovcare/backend/venv/bin/python app.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Start the service:
```bash
sudo systemctl enable ovcare-api
sudo systemctl start ovcare-api
sudo systemctl status ovcare-api
```

#### Option B: Gunicorn (Recommended for Production)
```bash
cd backend
pip install gunicorn

# Run with Gunicorn
gunicorn -w 4 -b 127.0.0.1:5000 app:app
```

Create systemd service:
```ini
[Service]
ExecStart=/var/www/ovcare/backend/venv/bin/gunicorn -w 4 -b 127.0.0.1:5000 app:app
```

### 4. Web Server Configuration

#### Nginx Configuration
Create `/etc/nginx/sites-available/ovcare`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    root /var/www/ovcare;
    index index.php;
    
    # PHP-FPM Configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # ML API Proxy
    location /api/ {
        proxy_pass http://127.0.0.1:5000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.git {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/ovcare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache Configuration
Create `/etc/apache2/sites-available/ovcare.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/ovcare
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
    
    <Directory /var/www/ovcare>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # ML API Proxy
    ProxyPass /api/ http://127.0.0.1:5000/
    ProxyPassReverse /api/ http://127.0.0.1:5000/
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 5. SSL/TLS Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal (test)
sudo certbot renew --dry-run
```

### 6. File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/ovcare

# Set permissions
sudo find /var/www/ovcare -type f -exec chmod 644 {} \;
sudo find /var/www/ovcare -type d -exec chmod 755 {} \;

# Reports directory (writable)
sudo chmod 775 /var/www/ovcare/reports
sudo chown www-data:www-data /var/www/ovcare/reports

# Logs directory
sudo mkdir -p /var/www/ovcare/logs
sudo chmod 775 /var/www/ovcare/logs
sudo chown www-data:www-data /var/www/ovcare/logs
```

### 7. Firewall Configuration

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Ensure ML API is not exposed externally
# Only allow internal connections to port 5000
```

### 8. Monitoring & Logging

#### Setup Log Rotation
Create `/etc/logrotate.d/ovcare`:

```
/var/www/ovcare/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

#### Monitor Services
```bash
# Check API status
sudo systemctl status ovcare-api

# Check logs
sudo tail -f /var/www/ovcare/logs/error.log

# Check ML API logs
sudo journalctl -u ovcare-api -f
```

### 9. Backup Strategy

#### Database Backup Script
Create `/usr/local/bin/backup-ovcare-db.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/ovcare"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u ovcare_user -p'your_password' ovarian_cancer_db | gzip > $BACKUP_DIR/ovcare_db_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "ovcare_db_*.sql.gz" -mtime +30 -delete
```

Add to crontab:
```bash
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-ovcare-db.sh
```

### 10. Performance Optimization

#### PHP Configuration
Edit `/etc/php/7.4/fpm/php.ini`:

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
opcache.enable=1
opcache.memory_consumption=128
```

#### Database Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_recorded_at ON biomarker_data(recorded_at);
CREATE INDEX idx_calculated_at ON risk_history(calculated_at);
```

### 11. Testing Production Environment

```bash
# Test database connection
php -r "
require 'includes/config.php';
require 'db.php';
echo 'Database: ' . (\$conn->ping() ? 'Connected' : 'Failed') . PHP_EOL;
"

# Test ML API
curl http://127.0.0.1:5000/health

# Test SSL
curl -I https://your-domain.com
```

### 12. Security Hardening

1. **Disable directory listing** in web server config
2. **Remove .git directory** from web root
3. **Implement rate limiting** for API endpoints
4. **Setup fail2ban** for brute force protection
5. **Regular security updates**:
   ```bash
   sudo apt-get update
   sudo apt-get upgrade
   ```
6. **Database user should NOT have SUPER privileges**
7. **Enable MySQL slow query log** for monitoring

### 13. Post-Deployment Verification

- [ ] All pages load correctly
- [ ] SSL certificate is valid
- [ ] ML API responds correctly
- [ ] Database connections work
- [ ] File uploads work (if applicable)
- [ ] Error logging is working
- [ ] Backups are running
- [ ] Monitoring is active
- [ ] Default passwords changed
- [ ] Security headers present
- [ ] HTTPS enforced

### 14. Emergency Contacts

Document contact information for:
- System administrator
- Database administrator
- Developer team
- Hosting provider support

### 15. Rollback Plan

Keep previous version available:
```bash
# Before deployment
cp -r /var/www/ovcare /var/www/ovcare.backup

# If rollback needed
sudo systemctl stop ovcare-api
sudo systemctl stop nginx
mv /var/www/ovcare /var/www/ovcare.failed
mv /var/www/ovcare.backup /var/www/ovcare
sudo systemctl start nginx
sudo systemctl start ovcare-api
```

## Maintenance Schedule

- **Daily**: Check logs, backup database
- **Weekly**: Review security alerts, update packages
- **Monthly**: Performance review, database optimization
- **Quarterly**: Security audit, dependency updates
- **Annually**: SSL certificate renewal (automatic with certbot)

## Support

For production issues:
1. Check logs: `/var/www/ovcare/logs/error.log`
2. Check ML API: `sudo journalctl -u ovcare-api`
3. Check database: `mysql -u root -p`
4. Check web server: `sudo systemctl status nginx`
