#!/bin/bash
# Quick Setup Script for Local Installation

echo "======================================"
echo "  Hottest Deals - Local Setup"
echo "======================================"
echo ""

# Check if MySQL is installed
if command -v mysql &> /dev/null; then
    echo "✓ MySQL found"
else
    echo "✗ MySQL not found. Please install XAMPP/WAMP/MAMP first"
    exit 1
fi

# Check if PHP is installed
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo "✓ PHP found: $PHP_VERSION"
else
    echo "✗ PHP not found. Please install PHP 8.x"
    exit 1
fi

echo ""
echo "Setup Options:"
echo "1. Import database only"
echo "2. Full setup (database + start server)"
echo "3. Start PHP built-in server only"
echo ""
read -p "Choose option (1-3): " option

case $option in
    1)
        read -p "MySQL root password (press Enter if empty): " -s password
        echo ""
        mysql -u root -p$password -e "CREATE DATABASE IF NOT EXISTS hrms;"
        mysql -u root -p$password hrms < hottest_deals_full.sql
        echo "✓ Database imported successfully!"
        ;;
    2)
        read -p "MySQL root password (press Enter if empty): " -s password
        echo ""
        mysql -u root -p$password -e "CREATE DATABASE IF NOT EXISTS hrms;"
        mysql -u root -p$password hrms < hottest_deals_full.sql
        echo "✓ Database imported!"
        echo "✓ Starting PHP server on port 8080..."
        php -S localhost:8080
        ;;
    3)
        echo "✓ Starting PHP server on port 8080..."
        php -S localhost:8080
        ;;
    *)
        echo "Invalid option"
        exit 1
        ;;
esac
