# 🌌 NexisERP: Multi-Tenant E-Commerce & ERP SaaS Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1.svg?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)
[![Cloudflare](https://img.shields.io/badge/Cloudflare-DNS%20Proxied-F38020.svg?style=for-the-badge&logo=cloudflare)](https://www.cloudflare.com/)

NexisERP is a high-performance, dynamic, and state-of-the-art **Multi-Tenant E-commerce and ERP SaaS platform** designed for modern digital commerce. Powered by a dynamic PHP 8 core, MySQL, and a luxury dark glassmorphic design system using the Outfit typography, NexisERP delivers an enterprise-grade experience for storefront shoppers and merchant administrators alike.

---

## 📸 Platform Showcases & Aesthetics

The platform features a **premium glassmorphic theme** characterized by semi-transparent backdrops (`backdrop-filter: blur()`), vibrant linear gradients, responsive grids, and subtle micro-animations that respond seamlessly to user interaction.

```mermaid
graph TD
    subgraph Storefront [🛍️ High-End Storefronts]
        index["index.php (Category Filtering & Active Searches)"] --> cart["cart.php (Smooth Basket Summary)"]
        cart --> checkout["checkout.php (Encrypted Single-page transactions)"]
    end
    
    subgraph Backoffice [📊 Business ERP Dashboards]
        dash["admin/dashboard/index.php (Real-time dynamic Chart.js stats)"]
        dash --> prod["products.php (Inventory alert centers & Threshold overrides)"]
        dash --> cat["categories.php (Slugified catalog hierarchies)"]
        dash --> orders["orders.php (Order fulfillment state controllers)"]
        dash --> cust["customers.php (Direct manual customer creation forms)"]
        dash --> reports["reports.php (Advanced Month-over-Month Chart analytics)"]
    end
    
    database[(MySQL Data Tier)]
    
    Storefront -->|Writes Orders & customer updates| database
    database -->|Reads Metrics & Aggregations| Backoffice
```

---

## ✨ Features & Capabilities

### 🛍️ Public E-Commerce Storefront
*   **Active Subdomain Routing**: Dynamic merchant routing powered by subdomain hostname parsing (`getCurrentStore()`), enabling fully isolated online store presence.
*   **Keyword Catalog Searches**: Secure prepared-statement query matching over product names and descriptions.
*   **Taxonomy Filters**: Dynamic side-bar navigation with category-specific collections matching custom slug URLs.
*   **Stock Status Controls**: Automated indicators alerting shoppers of low-stock thresholds in real-time.
*   **Transactional checkout**: Single-page secure orders executing atomic inventory deductions and customer mapping.

### 📊 Back-Office Merchant ERP
*   **Dynamic Interactive KPI Metrics**: Automated revenue tracking, order tallies, and inventory alert tallies.
*   **Chart.js Business Intelligence**:
    *   **Revenue Velocity MoM**: Beautiful linear trend chart of monthly billing.
    *   **Distribution Shares**: Doughnut chart charting sales distribution share per product category.
*   **Invoicing & Fulfillment Control**: Comprehensive order state controllers (Pending, Processing, Shipped, Delivered, Cancelled) and payment status updates (Unpaid, Paid, Refunded).
*   **Unified Customer Directory**: Profiles lifetime purchases, delivery addresses, registration dates, and handles manual back-office registrations.

---

## 📂 Architecture and Directory Layout

```text
/erp_saas
│   database.sql             <- High-fidelity MySQL database schema & rich seed data
│   index.php                <- Dynamic Multi-tenant Storefront landing page
│   cart.php                 <- Shopping cart collection
│   checkout.php             <- Atomic transaction order engine
│   saas_landing.php         <- Core platform overview & merchant registration
│   register.php             <- Secure new merchant signups
│
├───admin                    <- Back-office entry routes
│   │   index.php            <- Luxury dark glassmorphic Admin login page
│   └───dashboard            <- ERP Merchant modules
│           index.php        <- Main KPIs & Sales velocity charts
│           products.php     <- Complete inventory catalog list
│           product_add.php  <- Create item specification form
│           product_edit.php <- Update item pricing, thresholds, & metadata
│           categories.php   <- Category taxonomy overview
│           category_add.php <- Create slugified collection form
│           category_edit.php<- Modify category properties
│           orders.php       <- Fulfillment shipping queue
│           order_details.php<- Invoicing & shipping profile panel
│           customers.php    <- Unified client profile folder & direct signups
│           reports.php      <- MoM sales charts & category share distributions
│
└───includes                 <- System engine & core files
        config.php           <- Environment config parameters
        db.php               <- Dynamic DB connection loader (supports environmental overrides)
        functions.php        <- Shared domain parsing, helpers, & sanitize filters
```

---

## 🛠️ Installation & Setup Instructions

### 1. Database Provisioning
Create a new MySQL database instance on your local environment (e.g. phpMyAdmin, XAMPP, or MariaDB) and import the seed schema:
```bash
mysql -u your_username -p your_database_name < database.sql
```

### 2. Environment Configuration
Open `includes/config.php` and fill in your connection variables:
```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp_saas');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL configuration (without trailing slash)
define('BASE_URL', 'http://localhost/erp_saas');
```

> NexisERP features an **Environmental Database Loader**. It will automatically detect server-level environment variables (like those on Clever Cloud, Railway, or Render) and prioritize them over constants.

### 3. Start Local Development Server
Execute a local PHP dev server directly from the workspace root:
```bash
php -S localhost:8000
```

---

## 🧪 Testing and Sandboxing

### A. Simulated Hostnames (Localhost)
Access individual storefronts using local tenant parameters:
*   **Electronics Storefront**: `http://localhost:8000/?store=electronics`
*   **Boutique Storefront**: `http://localhost:8000/?store=clothing`

### B. Sandbox Login Credentials

| Business Domain | Admin Email Address | Secure Password | back-office Entrance |
| :--- | :--- | :--- | :--- |
| **Electronics Hub** | `admin@electronics.com` | `password` | `http://localhost:8000/admin/index.php?store=electronics` |
| **Clothing Boutique** | `admin@clothing.com` | `password` | `http://localhost:8000/admin/index.php?store=clothing` |

---

## ☁️ Cloudflare & Subdomain Hosting Setup

To host NexisERP under a custom domain (e.g., `yourdomain.com.np`) for **100% free** using **InfinityFree** or any standard VPS:

1. **DNS Wildcards**: In your **Cloudflare DNS Panel**, add a CNAME record pointing your wildcards (`*`) directly to your web host:
   * **Type**: `CNAME` | **Name**: `*` | **Target**: `yourdomain.com.np` | **Proxy status**: `Proxied (Orange)`
2. **Server Configuration**: Ensure your Nginx or Apache server block matches wildcard aliases:
   * **Nginx**: `server_name yourdomain.com.np *.yourdomain.com.np;`
   * **Apache**: `ServerAlias yourdomain.com.np *.yourdomain.com.np`

---

## 📄 License & Standards

This project is open-source software licensed under the [MIT License](LICENSE). 
Contributions must follow the guidelines outlined in [CONTRIBUTING.md](CONTRIBUTING.md).
For vulnerability reports, please review our [Security Policy](SECURITY.md).
