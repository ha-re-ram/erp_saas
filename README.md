# Local Business ERP + E-commerce SaaS Platform

This repository contains a full-stack web application designed for multi-tenant SaaS. It allows business owners to manage their stores (ERP) and sell products online.

## 🚀 Current Progress (As of March 21, 2026)
- **Multi-tenancy**: Core structure for handling multiple stores via subdomains/store IDs is implemented.
- **Admin Dashboard**:
    - **Authentication**: Secure login system with role-based access (Owner/Admin).
    - **Category Management**: Full CRUD (Create, Read, Update, Delete) functionality for product categories.
    - **Product Management**: Full CRUD functionality including stock tracking, low-stock thresholds, and price formatting.
- **Storefront**: Basic customer-facing portal (`index.php`) with cart and checkout integration.
- **Infrastructure**: Centralized database connection and utility functions in the `includes/` directory.

## 🏗️ Next Steps
- Implement **Order Management** in the admin dashboard.
- Develop **Customer Management** and basic reporting/analytics.
- Add support for **Physical Image Uploads** (currently using URL placeholders).
- Enhance the **Saas Landing Page** (`saas_landing.php`) to handle actual tenant registrations.

## Technologies Used
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript (ES6+), Chart.js
- **Backend:** PHP 8+
- **Database:** MySQL
- **Architecture:** MVC-inspired structured PHP app

## Architecture and Structure
```text
/erp_saas
│   database.sql          <- Master database schema and initial dump
│   index.php             <- Main storefront (E-commerce portal)
│   README.md
│
├───admin                 <- Admin entry routes
│   │   index.php         <- Admin login page
│   └───dashboard         <- ERP dashboard area
│           index.php     <- Dashboard Overview 
│
├───includes              <- Common/Core files
│       config.php        <- Central application configuration
│       db.php            <- Database PDO connection layer
│       functions.php     <- Utility and generic functions (routing, formatting)
│
├───assets                <- Publicly accessible static assets
│   ├───css
│   ├───js
│   └───images
```

## Security Best Practices Built-In
- **Session Authentication:** Ensures safe logins for tenants.
- **Password Hashing:** Implemented `password_hash()` and `password_verify()` via PHP defaults for security.
- **SQL Injection protection:** All database queries utilize PDO and Prepared Statements.
- **XSS Protection:** Outputs are cleaned using `htmlspecialchars(strip_tags())`.

## Setup Instructions for Local Development / Testing
1. **Database:**
   - Create a MySQL database (e.g., `erp_saas`).
   - Import the `database.sql` script into the database. Make sure you run this on an empty schema.
2. **Environment configuration:**
   - Open `/includes/config.php` and configure:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_mysql_user');
     define('DB_PASS', 'your_mysql_password');
     define('DB_NAME', 'erp_saas');
     ```
3. **Web Server configuration:**
   - Ensure the `erp_saas` folder is running via XAMPP / MAMP / LAMP or NGINX.
   - Set up your `.htaccess` (optional but recommended for SEO URLs).

### Testing the Platform locally
Since this is a multi-tenant platform (e.g. `store1.mysite.com`, `store2.mysite.com`) powered via subdomains:
- You can simulate the "Electronics Store" customer site by visiting: `http://localhost/erp_saas/index.php?store=electronics`
- You can simulate the "Clothing Boutique" customer site by visiting: `http://localhost/erp_saas/index.php?store=clothing`

### Accessing the Dashboard
Go to `http://localhost/erp_saas/admin/index.php?store=electronics` 
- **User:** `admin@electronics.com`
- **Password:** `password`

## Deployment Details
For shared hosting environments via cPanel or custom VPS:
1. Ensure your host supports Wildcard Subdomains (`*.yourdomain.com`).
2. Point the Document Root of your wildcard subdomain to the public directory of these PHP files.
3. Setup the live MySQL Database and apply `config.php` credentials.
4. Set error reporting in `config.php` to false. (`ini_set('display_errors', 0)`)
