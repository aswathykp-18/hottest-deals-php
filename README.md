# WhatsApp Marketing Platform (WATI-like)

A complete WhatsApp Marketing API platform built with pure PHP + MySQL.

## Quick Setup (XAMPP)

### Step 1: Download
Download this repo as ZIP or clone it:
```
git clone -b whatsapp-platform https://github.com/aswathykp-18/hottest-deals-php.git wa-platform
```

### Step 2: Copy to XAMPP
Copy the folder to `C:\xampp\htdocs\wa-platform\`

### Step 3: Start XAMPP
Open XAMPP Control Panel and start **Apache** and **MySQL** (both must be green).

### Step 4: Run Installer
Open browser: **http://localhost/wa-platform/install.php**

Click **"Install Now"** - this automatically creates the database, tables, and sample data.

### Step 5: Login
Go to: **http://localhost/wa-platform/login.php**

- Username: **admin**
- Password: **admin123**

## Features

- **Dashboard** - Stats, analytics chart, recent conversations
- **Chat Inbox** - WhatsApp-style conversation view with reply
- **Contact Management** - CRUD, CSV import, tags, search/filter
- **Contact Groups** - Organize contacts for targeted messaging
- **Message Templates** - Create WhatsApp message templates with variables
- **Broadcast Campaigns** - Send bulk messages to all contacts or groups
- **Chatbot Flow Builder** - Visual drag-and-drop flowchart with 7 node types
- **Chatbot Auto-Reply** - Keyword-triggered automated responses
- **Webhook Endpoint** - Receive messages from Meta WhatsApp Cloud API
- **Webhook Logs** - Monitor all incoming events with test tool
- **Analytics** - Message stats, delivery rates, campaign performance
- **Settings** - API configuration (Mock/Live mode)

## Connecting Real WhatsApp API

1. Get Meta WhatsApp Business API credentials from [developers.facebook.com](https://developers.facebook.com)
2. Edit `config/database.php`:
   ```php
   define('WA_API_MODE', 'live');
   define('WA_PHONE_NUMBER_ID', 'your_phone_number_id');
   define('WA_ACCESS_TOKEN', 'your_access_token');
   ```
3. Set webhook URL in Meta Console: `https://yourdomain.com/wa-platform/api/webhook.php`
4. Set Verify Token: `billionaire_homes_wa_verify_2026`

## Tech Stack
- PHP 8.2
- MySQL / MariaDB
- Vanilla JavaScript
- Chart.js
- Font Awesome
