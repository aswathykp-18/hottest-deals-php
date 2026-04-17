#!/bin/bash

# Hottest Deals PHP Application - Startup Script

echo "=========================================="
echo "  Hottest Property Deals - Starting...   "
echo "=========================================="
echo ""

# Start MariaDB
echo "Starting MariaDB..."
sudo service mariadb start
sleep 2

# Start Apache
echo "Starting Apache Web Server..."
sudo service apache2 start
sleep 2

# Check services
echo ""
echo "Checking services status..."
echo ""

MARIADB_STATUS=$(sudo service mariadb status | grep -o "Active" | head -1)
APACHE_STATUS=$(sudo service apache2 status | grep -o "Active" | head -1)

if [ "$MARIADB_STATUS" = "Active" ]; then
    echo "✓ MariaDB is running"
else
    echo "✗ MariaDB is not running"
fi

if [ "$APACHE_STATUS" = "Active" ]; then
    echo "✓ Apache is running"
else
    echo "✗ Apache is not running"
fi

echo ""
echo "=========================================="
echo "  Application is ready!                  "
echo "=========================================="
echo ""
echo "📍 Public Website:"
echo "   http://localhost:9000/"
echo ""
echo "🔐 Admin Panel:"
echo "   http://localhost:9000/admin/login.php"
echo "   Username: admin"
echo "   Password: admin123"
echo ""
echo "📊 Database:"
echo "   Name: hrms"
echo "   Table: hottest_deals"
echo "   Records: $(sudo mysql -se 'SELECT COUNT(*) FROM hrms.hottest_deals')"
echo ""
echo "=========================================="
