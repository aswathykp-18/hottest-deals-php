# INSTALLATION GUIDE - Hottest Property Deals PHP Application
=============================================================

## 📦 Package Contents:
- Complete PHP application
- Database SQL file
- Configuration files
- Assets (CSS, JavaScript)
- Documentation

## 🖥️ Local Installation Instructions

### OPTION 1: Using XAMPP (Windows/Mac/Linux)

#### Prerequisites:
1. Download and install XAMPP from: https://www.apachefriends.org/
2. XAMPP includes: Apache, PHP, MySQL (MariaDB)

#### Installation Steps:

**Step 1: Extract Files**
```
1. Download the application package
2. Extract to: C:\xampp\htdocs\hottest-deals\ (Windows)
              /Applications/XAMPP/htdocs/hottest-deals/ (Mac)
              /opt/lampp/htdocs/hottest-deals/ (Linux)
```

**Step 2: Start XAMPP Services**
```
1. Open XAMPP Control Panel
2. Start "Apache" service
3. Start "MySQL" service
```

**Step 3: Create Database**
```
1. Open browser: http://localhost/phpmyadmin
2. Click "New" to create database
3. Database name: hrms
4. Collation: utf8mb4_general_ci
5. Click "Create"
```

**Step 4: Import Database**
```
1. In phpMyAdmin, select "hrms" database
2. Click "Import" tab
3. Choose file: hottest_deals_full.sql
4. Click "Go"
5. Wait for "Import has been successfully finished"
```

**Step 5: Configure Database Connection**
```
Edit: hottest-deals/config/database.php

Change if needed:
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Empty for XAMPP default
define('DB_NAME', 'hrms');
```

**Step 6: Access Application**
```
Public Page: http://localhost/hottest-deals/
Admin Panel: http://localhost/hottest-deals/admin/login.php
Credentials: admin / admin123
```

---

### OPTION 2: Using WAMP (Windows)

#### Prerequisites:
Download WAMP from: https://www.wampserver.com/

#### Installation Steps:
```
1. Install WAMP Server
2. Extract files to: C:\wamp64\www\hottest-deals\
3. Start WAMP (green icon in system tray)
4. Click WAMP icon → phpMyAdmin
5. Create database: hrms
6. Import: hottest_deals_full.sql
7. Access: http://localhost/hottest-deals/
```

---

### OPTION 3: Using MAMP (Mac)

#### Prerequisites:
Download MAMP from: https://www.mamp.info/

#### Installation Steps:
```
1. Install MAMP
2. Extract files to: /Applications/MAMP/htdocs/hottest-deals/
3. Start MAMP servers
4. Open: http://localhost:8888/MAMP/
5. Click "Tools" → "phpMyAdmin"
6. Create database: hrms
7. Import: hottest_deals_full.sql
8. Access: http://localhost:8888/hottest-deals/
```

---

### OPTION 4: Using Docker (All Platforms)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  web:
    image: php:8.2-apache
    ports:
      - "8080:80"
    volumes:
      - ./php:/var/www/html
    depends_on:
      - db
    
  db:
    image: mariadb:10.11
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: hrms
    volumes:
      - ./hottest_deals_full.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"
```

Run:
```bash
docker-compose up -d
Access: http://localhost:8080/
```

---

### OPTION 5: Native PHP (Linux/Mac)

#### Prerequisites:
```bash
# Install PHP and MySQL
sudo apt install php8.2 php8.2-mysqli mariadb-server  # Ubuntu/Debian
brew install php mariadb                                # Mac (Homebrew)
```

#### Installation:
```bash
# 1. Extract files to any directory
cd /path/to/hottest-deals/

# 2. Import database
mysql -u root -p -e "CREATE DATABASE hrms;"
mysql -u root -p hrms < hottest_deals_full.sql

# 3. Start PHP server
php -S localhost:8080

# 4. Access
# http://localhost:8080/
```

---

## 🔧 Configuration Files to Check

### config/database.php
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');              // Set your MySQL password
define('DB_NAME', 'hrms');
```

### Common MySQL Passwords:
- XAMPP: (empty)
- WAMP: (empty)
- MAMP: root
- Custom: (your password)

---

## 📂 File Structure After Installation

```
hottest-deals/
├── index.php                 # Homepage
├── config/
│   └── database.php         # DB config
├── includes/
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── add.php
│   ├── edit.php
│   ├── export_excel.php
│   └── export_pdf.php
├── assets/
│   ├── style.css
│   └── script.js
├── hottest_deals_full.sql   # Database import file
└── README.md
```

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Apache/Web server is running
- [ ] MySQL/MariaDB is running
- [ ] Database 'hrms' exists
- [ ] Table 'hottest_deals' has 17 records
- [ ] Public page loads: http://localhost/hottest-deals/
- [ ] Can see property listings
- [ ] Search and filters work
- [ ] Admin login works: admin / admin123
- [ ] Can view dashboard
- [ ] Can add/edit/delete deals
- [ ] Export functions work

---

## 🐛 Troubleshooting

### Error: "Connection failed"
```
- Check MySQL is running
- Verify DB credentials in config/database.php
- Test: mysql -u root -p
```

### Error: "Access denied for user 'root'"
```
- Set password in config/database.php
- Or reset MySQL root password
```

### Error: "Table 'hrms.hottest_deals' doesn't exist"
```
- Import database again
- Check phpMyAdmin if table exists
```

### Page shows PHP code instead of running
```
- Ensure Apache is running
- PHP module must be enabled
- Check file has .php extension
```

### CSS not loading (no styles)
```
- Check assets folder exists
- Verify path in includes/header.php
- Clear browser cache
```

---

## 🎯 Quick Start Commands

### XAMPP (Windows):
```
C:\xampp\xampp-control.exe
http://localhost/hottest-deals/
```

### MAMP (Mac):
```
Open MAMP application
http://localhost:8888/hottest-deals/
```

### Linux Native:
```bash
sudo service mysql start
php -S localhost:8080
http://localhost:8080/
```

---

## 🔐 Security for Production

Before going live:
1. Change admin password
2. Use password_hash() for authentication
3. Add CSRF protection
4. Enable HTTPS
5. Restrict file permissions
6. Add input validation
7. Enable error logging (not display)

---

## 📞 Support

For issues:
1. Check Apache error log
2. Check PHP error log
3. Check MySQL log
4. Enable error display: ini_set('display_errors', 1);

---

## 📝 Next Steps

After successful installation:
1. Login to admin panel
2. Add your own property deals
3. Customize colors in assets/style.css
4. Update agent names
5. Modify property types
6. Add more features as needed

---

**Installation complete! Your real estate platform is ready.** 🏠✨
