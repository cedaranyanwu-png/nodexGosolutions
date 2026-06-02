# nodexGosolutions - Multi-Tenant PHP Boilerplate

Welcome to the **nodexGosolutions** project. This is a clean, highly modular, and extensively documented boilerplate for a multi-tenant website built entirely in pure, native PHP.

## 🚀 Overview

The architecture follows a **Single Entry Point** design, where all incoming traffic is routed through a single `index.php` file. The system detects the tenant based on the `HTTP_HOST` of the incoming request and serves customized content and styles accordingly.

## 🏗️ Architecture

- **Single Entry Point**: Managed by `.htaccess` (Apache) which routes all requests to `index.php`.
- **Tenant Management**: The `TenantManager` class handles domain parsing and retrieves tenant configurations from a mock data structure.
- **Data Separation**: Tenant data is currently stored in an associative array within `TenantManager.php`, allowing for easy migration to a database in the future.
- **Dynamic Templating**: `template.php` renders the UI based on the detected tenant's name, theme color, logo, and unique content.

## 📁 File Structure

- `.htaccess`: URL rewrite rules for routing.
- `index.php`: The main entry point and router.
- `TenantManager.php`: Logic for tenant detection and mock data storage.
- `template.php`: The dynamic presentation layer.

## 🛠️ How to Run

To run this project locally, you can use the built-in PHP development server:

```bash
php -S localhost:8000
```

Once the server is running, you can access the default tenant at `http://localhost:8000`.

To test different tenants, you can modify your `hosts` file or use tools like `curl` to set the `Host` header:

```bash
curl -H "Host: tenant1.nodexgosolutions.com" http://localhost:8000
```

## 📝 Coding Standards

- **Modern PHP**: Built using PHP 8+ standards (strict types, type hinting).
- **Security**: All output is properly escaped using `htmlspecialchars()`.
- **Documentation**: Every single file and function is line-by-line commented for maximum clarity and future scalability.

## 🔮 Future Scalability

This boilerplate is designed to be easily expanded. The `TenantManager`'s internal data retrieval logic can be replaced with database queries (e.g., MySQL/PDO) without altering the rest of the application structure.
