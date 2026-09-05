# NodeX SaaS Platform - System Architecture Documentation

## 1. Overall Architecture
NodeX Platform is a modular, secure, and multi-tenant SaaS architecture written in PHP 8.2+. The platform enforces a single entry point (`/index.php`) and uses a custom filesystem-backed JSON Database engine (`JsonDatabase`). The platform is split strictly into two fundamental domains:
- `/main/`: Central infrastructure, self-discovery, platform routing, security, sessions, authentication, tenant management, and core services.
- `/public/`: Isolated tenant web applications and projects (`/public/cedar/`, `/public/customer1/`).

```
                         INTERNET
                            │
                            ▼
                       index.php
                  SINGLE ENTRY POINT
                            │
                            ▼
                       CONFIG LOAD
                            │
                            ▼
                    DomainConfig
                            │
                 domain_config.json
                            │
                            ▼
                    DOMAIN RESOLVER
                            │
             ┌──────────────┴──────────────┐
             │                             │
             ▼                             ▼
       MAIN DOMAIN /                  NOT MAIN
       MAIN SUBDOMAIN                     │
             │                            ▼
             ▼                    TENANT DOMAIN REGISTRY
       MAIN SYSTEM                         │
             │                     ┌───────┴───────┐
             ▼                     │               │
      MAIN SELF-DISCOVERY        FOUND          NOT FOUND
             │                     │               │
       ┌─────┼─────┐               ▼               ▼
       ▼     ▼     ▼             TENANT         UNKNOWN
    Modules Tools APIs             │
                                   ▼
                              /public/project/
```

## 2. MAIN vs PUBLIC
- **MAIN (`/main/`)**: Controls central functionality including domain configuration, database CRUD, tenant registration, universal authentication, and module discovery.
- **PUBLIC (`/public/`)**: Houses tenant application code. Folders inside `/public/` do NOT automatically become live websites unless registered in the JSON database.

## 3. MAIN Self-Discovery
The `MainDiscovery` engine (`/main/discovery/MainDiscovery.php`) dynamically scans `/main/modules/`, `/main/services/`, and `/main/routes/` for valid `module.json` or `config.json` configuration metadata and registers them automatically with the platform.

## 4. Tenant Registration & Managed Tenants
Unlike MAIN resources, `/public/` tenant folders are **never automatically self-discovered**. A folder inside `/public/` (e.g. `/public/random/`) will return a 404/Block error unless it is explicitly created, registered, connected, and set to `active` status by MAIN through the `TenantManager` and `DomainResolver` services.

## 5. Central Domain Configuration
Platform identity is managed via `/main/config/domain_config.json`:
```json
{
    "main_domain": "nodexplatform.com.ng",
    "main_subdomains": {
        "admin": "admin.nodexplatform.com.ng",
        "api": "api.nodexplatform.com.ng",
        "www": "www.nodexplatform.com.ng"
    },
    "environment": "production",
    "https": true
}
```
All components access domain info exclusively through the `DomainConfig` service (`DomainConfig::mainDomain()`, `DomainConfig::subdomain('admin')`).

## 6. Domain Resolution Pipeline
When an HTTP request arrives at `index.php`:
1. `DomainConfig::normalizeHost()` strips port numbers and standardizes the host string.
2. `DomainResolver` checks if the host matches `DomainConfig::isMainDomain()` or `DomainConfig::isMainSubdomain()`.
3. If not MAIN, `DomainResolver` queries `domains.json` in the JSON Database.
4. If an active domain record is found, `ProjectResolver` validates the corresponding `projects.json` entry and verifies that the status is `active`.
5. If valid, request execution is delegated to the tenant's entry point (`/public/{folder}/index.php`).
6. If the domain or project is missing/inactive/suspended, an appropriate HTTP status (403/404) is returned.

## 7. Tenant Custom Domains
Tenants can connect custom domains (e.g., `cedar.com`). Custom domains are verified and saved into `/main/storage/db/domains.json` with `type: "custom_domain"` and mapped to the project ID.

## 8. Tenant Subdomains
Tenant subdomains (e.g., `customer1.nodexplatform.com.ng`) are generated dynamically by deriving the base platform domain from `DomainConfig::mainDomain()` and saving to `domains.json` with `type: "tenant_subdomain"`.

## 9. JSON Database Engine
Stored in `/main/storage/db/`, the `JsonDatabase` class provides a fluent interface (`table()`, `where()`, `insert()`, `update()`, `delete()`, `find()`, `get()`, `first()`, `count()`, `paginate()`).
Key security and concurrency features:
- File locking using `flock()` (Shared locks for reads, exclusive locks for writes).
- Atomic file writes using temporary files and atomic `rename()`.
- Strict input sanitization against path traversal.

## 10. Universal Session System
The `Session` class (`/main/session/Session.php`) manages HTTP cookies (`NODEX_SESS_ID`), enforces `HttpOnly`, `Strict`, and `SameSite` flags, handles session ID regeneration, and provides CSRF token creation and verification (`Session::csrfToken()`, `Session::verifyCsrfToken()`).

## 11. Authentication
The `Auth` service (`/main/auth/Auth.php`) connects to `users.json`, verifies password hashes via `password_verify()`, manages logged-in session state (`Auth::check()`, `Auth::user()`), and enforces role checks (`Auth::hasRole('superadmin')`).

## 12. Authorization & RBAC
Role-Based Access Control restricts administrative routes and tenant management capabilities to authorized accounts.

## 13. Security Boundaries
Apache `.htaccess` blocks direct HTTP access to `/main/config/`, `/main/core/`, `/main/storage/`, `/main/database/`, `/main/discovery/`, `/main/logs/`, and all `.json` files.

## 14. Tenant Isolation
Tenant execution occurs within isolated file boundaries (`/public/{folder}/`). Tenants cannot modify central platform configurations or read other tenant directories.

## 15. Adding MAIN Modules
To add a new MAIN module:
1. Create a directory under `/main/modules/your-module/`.
2. Add a `module.json` file with `slug`, `name`, `version`, `description`, and `enabled`.
3. `MainDiscovery` will detect and register the module automatically.

## 16. Creating Tenants
Admins create tenants via the Admin Console (`/admin`) or `TenantManager::createTenant()` and `TenantManager::createProject()`.

## 17. Connecting Domains
Admins or users register domains via `TenantManager::registerDomain($domain, $projectId, $tenantId, $type)`.

## 18. Changing the MAIN Domain
To change the platform domain from `nodexplatform.com.ng` to `exampleplatform.com`:
1. Update `/main/config/domain_config.json`.
2. The entire application automatically inherits the new domain with ZERO PHP source code modifications.

## 19. Project Lifecycle States
Projects support 7 distinct lifecycle states: `CREATED`, `REGISTERED`, `CONNECTED`, `ACTIVE`, `SUSPENDED`, `DISABLED`, `DELETED`. Non-active states immediately block public access.

## 20. Apache URL Rewriting
All incoming requests pass through `/index.php` using standard Apache mod_rewrite rules while protecting internal `/main/` subdirectories.
