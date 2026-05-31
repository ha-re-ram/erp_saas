# Contributing to NexisERP

Thank you for your interest in contributing to NexisERP! We welcome community contributions to make this ERP SaaS platform even better, faster, and more beautiful.

Following these guidelines helps ensure a smooth, collaborative contribution process for everyone.

---

## 🗺️ How Can I Contribute?

### 🐛 Reporting Bugs
*   **Search Existing Issues**: Before opening a new issue, search to see if someone else has already reported the bug.
*   **Be Specific**: Detail your environment (PHP version, OS, browser).
*   **Steps to Reproduce**: Provide a step-by-step walk-through or query sample to reproduce the behavior.
*   **Include Logs**: Share relevant PHP error logs or console outputs.

### 💡 Proposing Enhancements
*   Explain **why** this feature is useful to merchants or storefront customers.
*   Detail the proposed implementation workflow.
*   Avoid adding extra bloated dependencies; keep the core footprint clean, light, and optimized!

---

## 💻 Local Development Workflow

1.  **Fork the Repository**: Create your own copy of the repository on GitHub.
2.  **Clone Locally**:
    ```bash
    git clone https://github.com/YOUR-USERNAME/erp_saas.git
    cd erp_saas
    ```
3.  **Create a Feature Branch**:
    ```bash
    git checkout -b feat/your-feature-name
    ```
4.  **Implement & Test**: Write clean, optimized PHP 8 code, configure your local database parameters, and test pages.

---

## 📜 Code Style Guidelines

To keep the codebase maintainable, NexisERP follows strict development standards:

### 1. PHP Style (PSR-12)
*   All PHP files must use `<?php` tags. Do not use short open tags `<?`.
*   Maintain clear, readable indentation (use 4 spaces, no tabs).
*   Follow clean naming patterns:
    *   **Variables/Functions**: Use camelCase (e.g. `$storeId`, `getCurrentStore()`).
    *   **SQL Tables**: Use lowercase plural strings with snake_case (e.g. `order_items`).

### 2. Multi-Tenant Scoping (Critical!)
*   Every query fetching store-specific data **must** be explicitly scoped by the active tenant ID:
    ```sql
    WHERE store_id = :store_id
    ```
*   **Never** execute raw, unparameterized user inputs. Use **PDO Prepared Statements** for all inputs to protect against SQL injections!

### 3. Visual Glassmorphic Design Code
*   All administrative pages must inherit the **dark luxury design layout** (`#0b0f19` background) using `Outfit` typography and semi-transparent panels.
*   Use inline semantic utilities and clean structural CSS. Do not inject bloated visual libraries unless approved.

---

## 📝 Commit Message Conventions

Commit messages must be clear, concise, and explain the context. We recommend using the **Angular Git Commit Convention**:

*   `feat: add category deletion controls`
*   `fix: resolve duplicate parameter binding in customer query`
*   `docs: update installation guidelines in README`
*   `style: format checkout form fields`

---

## 📬 Pull Request Process

1.  Submit your Pull Request pointing from your feature branch to our `master` branch.
2.  Explain the changes and reference any related open issues.
3.  Verify that your changes do not introduce php syntax errors or styling inconsistencies.
4.  Wait for review. We strive to review all PRs within 48 hours!
