# Hottest Property Deals - PHP Web Application

A modern, professional real estate property deals management system built with pure PHP, MySQL/MariaDB, and vanilla JavaScript.

## Features

### Public Features
- **Property Listings**: Beautiful grid layout showcasing all active property deals
- **Advanced Search & Filters**: 
  - Search by agent name, area, or project name
  - Filter by property type
  - Filter by price range (min/max)
  - Sort by price (ascending/descending) or status
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **Modern UI**: Professional real estate theme with smooth animations

### Admin Features
- **Secure Authentication**: Admin login with session management
- **Full CRUD Operations**:
  - Add new property deals
  - Edit existing deals
  - Delete deals
  - Toggle active/inactive status
- **Data Export**:
  - Export to Excel (CSV format)
  - Export to PDF (print-friendly format)
- **Dashboard**: Complete overview of all deals with quick actions

## Technology Stack

- **Backend**: PHP 8.2
- **Database**: MariaDB 10.11
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: Apache 2.4
- **Fonts**: Inter (body), Playfair Display (headings)
- **Icons**: Font Awesome 6.5

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- MariaDB/MySQL
- Apache web server

### Database Setup
The database has been created and populated with sample data:
- Database name: `hrms`
- Table: `hottest_deals`
- Records: 17 sample property deals

### Access Information

**Public Website**: http://localhost:9000/
- View all active property deals
- Use search and filter features
- No login required

**Admin Panel**: http://localhost:9000/admin/login.php
- **Username**: admin
- **Password**: admin123

## Project Structure

```
/app/php/
├── config/
│   └── database.php          # Database connection config
├── includes/
│   ├── header.php            # Common header template
│   └── footer.php            # Common footer template
├── admin/
│   ├── login.php             # Admin authentication
│   ├── dashboard.php         # Admin dashboard
│   ├── add.php               # Add new deal
│   ├── edit.php              # Edit existing deal
│   ├── logout.php            # Logout functionality
│   ├── export_excel.php      # Excel export
│   └── export_pdf.php        # PDF export
├── assets/
│   ├── style.css             # Main stylesheet
│   └── script.js             # JavaScript functionality
└── index.php                 # Public homepage
```

## Database Schema

**Table: hottest_deals**
- id (Primary Key, Auto Increment)
- agent_name (Agent's name)
- area (Property area/location)
- project_name (Project name)
- unit (Unit type, e.g., "3 Bed")
- property_type (Apartment, Villa, Townhouse, etc.)
- op_text (Original price text, e.g., "1.5M")
- sp_text (Selling price text, e.g., "1.4M")
- op_amount (Original price amount)
- sp_amount (Selling price amount)
- payout (Payment terms)
- status_text (Status, e.g., "2027", "Ready")
- display_order (Display order number)
- is_active (Active status: 1 or 0)
- created_at (Timestamp)
- updated_at (Timestamp)

## Features in Detail

### Public Page Features
- Hero section with gradient background
- Real-time search across agent names, areas, and projects
- Property type filter dropdown
- Price range filters with number inputs
- Multiple sorting options
- Deal cards with:
  - Agent information
  - Status badge
  - Project name and location
  - Property details (bedrooms, type)
  - Original vs Selling price comparison
  - Payment terms
  - Hover animations

### Admin Panel Features
- **Dashboard**:
  - Comprehensive table view of all deals
  - Quick edit, toggle, and delete actions
  - Status indicators (active/inactive)
  - Sortable columns
  
- **Add/Edit Forms**:
  - All property fields
  - Dropdown for property types
  - Checkbox for active status
  - Form validation
  
- **Export Functionality**:
  - CSV/Excel export for spreadsheet analysis
  - PDF export for printable reports

## Security Notes

⚠️ **For Production Use**:
1. Change the default admin credentials
2. Use password hashing (bcrypt) instead of plain text comparison
3. Add CSRF protection to forms
4. Implement rate limiting on login attempts
5. Use prepared statements (already implemented)
6. Add input sanitization layers
7. Enable HTTPS

## Customization

### Change Admin Credentials
Edit `/app/php/admin/login.php` line 11-12:
```php
if ($username === 'your_username' && $password === 'your_secure_password') {
```

### Modify Database Connection
Edit `/app/php/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hrms');
```

### Styling
All styles are in `/app/php/assets/style.css`
- Colors are defined in CSS variables at the top
- Modify `:root` variables to change the theme

## Browser Compatibility
- Chrome/Edge (recommended)
- Firefox
- Safari
- Mobile browsers

## License
This project is provided as-is for educational and commercial use.

## Support
For issues or questions, please refer to the PHP error logs:
```bash
sudo tail -f /var/log/apache2/error.log
```

---
**Built with ❤️ using pure PHP, no frameworks needed!**
