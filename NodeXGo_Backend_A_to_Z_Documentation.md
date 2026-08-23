# NodeXGo Backend A–Z Documentation

**Document purpose.** This document explains the current NodeXGo backend from A to Z: what each backend directory and important file does, how requests travel through the single-entry router, how APIs connect to services and JSON storage, how user and administrator permissions are applied, how tenant websites and files are isolated, how Flutterwave payments are verified, and how future developers should extend the platform safely.

> **Important operating rule:** pages should assemble views and call services. Business logic, authentication, billing, filesystem authorization, and security checks must remain in reusable backend modules or APIs rather than being copied into HTML templates.

## 1. Platform overview

NodeXGo is a PHP application with a single public entry point, `index.php`. Apache rewrite rules direct requests into that entry point. The router chooses a public page, secure dashboard page, API endpoint, tenant website, editor, sitemap, or utility route. The project uses a file-backed JSON database, PHP service classes, Bootstrap/jQuery-based frontend shells, and filesystem-backed tenant websites.

### High-level connection diagram

```text
Browser
  |
  v
Apache /.htaccess
  |
  v
index.php  -----> public pages / secure pages / tenant host resolver
  |  \
  |   +---------> api/* controllers
  |                    |
  |                    v
  |              backend/auth + backend/services
  |                    |
  +--------------------+--------------------+
                       |                    |
                       v                    v
                 php/db.php          Database JSON files
                       |
          +------------+-------------+
          |            |             |
          v            v             v
     public/<tenant>  Flutterwave   logs/backups
```

The current system also contains compatibility PHP handlers under `php/`. Some are still required because they contain behavior not yet reproduced in the API/service layer. The modular rebuild adds `core/`, `components/`, and `modules/` as migration boundaries without silently deleting unique security or business logic.

## 2. A–Z backend responsibilities

| Letter | Area | Meaning in NodeXGo |
|---|---|---|
| A | Authentication and authorization | Login, registration, sessions, roles, permissions, staff checks, and tenant ownership. |
| B | Backups | Backup creation, verification, restoration, deletion, and backup storage paths. |
| C | Configuration and cPanel | Environment values, platform domains, filesystem roots, cPanel integration, and runtime constants. |
| D | Database | Locked JSON tables implemented by the custom `Database` class. |
| E | Editor | Dedicated editor route and GrapesJS/file-editor integration. |
| F | Files | File listing, reading, saving, uploading, ZIP extraction, rename, copy, move, and deletion within tenant boundaries. |
| G | Gateway responses | Consistent JSON responses for API clients and SweetAlert2 feedback in the frontend. |
| H | Hosting | Tenant website provisioning, subdomain links, custom domains, storage roots, and cPanel hooks. |
| I | Integrations | Flutterwave, cPanel, Bootstrap, jQuery, DataTables, Uppy, GrapesJS, SweetAlert2, and AOS. |
| J | JavaScript modules | Existing `app.js` compatibility client plus the extracted file-manager module boundary. |
| K | Keys and secrets | `.env` values for Flutterwave, cPanel, domains, and application configuration. |
| L | Logging | Error logs, activity logs, payment records, email outbox, and operational diagnostics. |
| M | Modules | `core/`, `components/`, `modules/user/`, and `modules/admin/` ownership boundaries. |
| N | Notifications | JSON feedback, SweetAlert2 toasts, support messages, and email-outbox records. |
| O | Operations | Admin controls, plans, users, support, analytics, financial reports, settings, and activity records. |
| P | Payments | Payment initialization, Flutterwave checkout, callback/webhook verification, and subscription activation. |
| Q | Query and request flow | HTTP method checks, POST/GET payloads, AJAX calls, JSON results, redirects, and route resolution. |
| R | Router | `index.php` is the central route and tenant dispatch entry point. |
| S | Security | Sessions, rate limiting, CSRF-style tokens where used, permission checks, traversal prevention, and live-payment guards. |
| T | Tenants | Subdomain-first tenant websites stored below `public/<subdomain>`. |
| U | User panel | Dashboard shell and feature tabs for websites, files, billing, support, databases, team, and settings. |
| V | Validation | Input cleaning, email/password checks, domain and subdomain validation, plan checks, and file-extension rules. |
| W | Websites | Website creation, templates, custom domains, suspension, traffic records, and deletion/audit behavior. |
| X | eXternal callbacks | Flutterwave return URLs and webhook verification. |
| Y | Yield and subscriptions | Plan records, subscription status, trial dates, paid upgrades, and feature access. |
| Z | ZIP safety | Archive upload and extraction protected against path traversal and workspace escape. |

## 3. Root files and entry points

| File | Responsibility | Connection |
|---|---|---|
| `index.php` | Central single-entry router. It loads configuration/database helpers, recognizes routes, redirects generic authenticated URLs, serves public and secure pages, and resolves tenant hosts. | Connects Apache requests to almost every page, API, service, and tenant directory. |
| `.htaccess` | Apache rewrite and access-hardening rules. | Sends clean URLs to `index.php` and protects private paths. |
| `.env.example` | Safe configuration template. | Documents `APP_BASE_URL`, domain, Flutterwave, webhook, and cPanel values without real secrets. |
| `server.js` | Local development helper that starts PHP’s built-in server. | Development-only launcher; it does not contain business logic. |
| `template.php` | Shared or legacy template support. | Used by older page/template flows and retained until its callers migrate. |
| `TenantManager.php` | Tenant retrieval and tenant configuration support. | Loaded by `index.php` during tenant-host resolution. |
| `robots.txt` | Search-engine crawler policy. | Static public deployment asset. |
| `PRODUCTION_REPAIR_NOTES.md` | Historical repair and compatibility notes. | Documents previous fixes and should be updated when route/security contracts change. |

## 4. `backend/config/`

### `backend/config/config.php`

This is the centralized configuration layer. It reads a local root `.env` when the hosting environment has not already injected a variable. It defines application constants such as `APP_NAME`, `MAIN_DOMAIN`, `WEBSITE_SUBDOMAIN`, `APP_BASE_URL`, `DB_BASE_DIR`, and `TENANT_PUBLIC_DIR`. It also defines cPanel values and Flutterwave credentials.

The file exposes `getConfig()`, which returns grouped application, cPanel, and Flutterwave settings. New code should consume this configuration or environment variables through a documented service boundary. Secrets must never be hardcoded in templates, JavaScript, Git, or public tenant folders.

## 5. `backend/database/`

### `backend/database/Database.php`

This file is a compatibility bridge to the actual `Database` implementation in `php/database.php`. It exists so newer backend code can require a backend-oriented path while preserving the proven legacy class.

### `backend/database/db.php`

This file bridges to the shared database/security helper layer in `php/db.php`. It is used by services and APIs that need the existing `$conn` connection, session helpers, response helpers, subscription checks, or common utilities.

## 6. `backend/auth/`

### `backend/auth/AuthService.php`

`AuthService` is the API-oriented authentication service. Its `login()` method finds a user, verifies the password, checks suspension, sets session fields, and returns a structured response. Its `register()` method creates a trial account and can log the user into the new session.

The legacy `php/login.php` and `php/register.php` still contain additional behavior, including rate limits, email-verification tokens, verification URLs, and email-outbox records. They must not be removed until those behaviors are moved into `AuthService` and validated against the existing contracts.

## 7. `backend/services/`

| Service | Responsibility | Main consumers |
|---|---|---|
| `WebsiteService.php` | Website creation, deletion, suspension, tenant directory work, and website records. | Website APIs and dashboard flows. |
| `DomainService.php` | Custom-domain connection/removal and domain ownership logic. | Domain APIs and user dashboard. |
| `FileManagerService.php` | Workspace-bound file operations, path checks, uploads, ZIP extraction safety, and file manipulation. | `/api/files/*`, user Manage Files UI, and editor flows. |
| `PaymentService.php` | Flutterwave initialization, transaction verification, payment records, and subscription activation. | Payment APIs and subscription dashboard. |
| `SubscriptionService.php` | Plan lookup, current subscription state, trials, and plan-related checks. | Website creation, billing, and dashboard access. |
| `CpanelService.php` | cPanel integration boundary. | Hosting/domain operations where cPanel is configured. |

Services are the correct place for business operations. A page should validate presentation input, call the service, and render the result. A service should enforce authorization and resource boundaries independently of the page.

## 8. `core/` modular layer

The rebuild adds a shared core migration layer.

| File/directory | Responsibility |
|---|---|
| `core/bootstrap.php` | Loads configuration, JSON storage, JSON response, and authorization facades for new modular controllers. |
| `core/JsonStorage/JsonStorage.php` | A small facade over the existing locked `Database` engine. It provides `all`, `first`, `insert`, `update`, `delete`, and `legacy` access without duplicating JSON I/O. |
| `core/Response/JsonResponse.php` | Provides `success()` and `error()` methods with a consistent `{success,message,data}` JSON shape. |
| `core/Authorization/AuthorizationGateway.php` | Delegates session, role, admin-page, and tenant-ownership decisions to the existing security functions. |
| `core/Auth/`, `core/Session/`, `core/Database/`, `core/Security/`, `core/Validation/`, `core/FileSystem/`, `core/Hosting/`, `core/Billing/`, `core/Backups/`, `core/Logging/`, `core/Notifications/` | Reserved ownership boundaries for future extraction. Existing services remain the compatibility source until each feature is migrated and tested. |

The facades are migration seams. They do not replace unique legacy behavior by assumption.

## 9. `php/` legacy compatibility and infrastructure

The `php/` directory contains both reusable infrastructure and legacy request handlers. It should be reduced gradually, not deleted wholesale.

| File | Current purpose and status |
|---|---|
| `php/db.php` | Shared connection, environment discovery, session security, rate limiting, JSON responses, permissions, subscriptions, SEO/template helpers, and common utilities. **Required.** |
| `php/database.php` | Locked JSON database engine. **Required by existing services and APIs.** |
| `php/BackupManager.php` | Backup creation, listing, verification, restoration, and deletion. **Unique infrastructure.** |
| `php/ToolManager.php` | Tool discovery, configuration validation, enablement, resolution, and access checks. **Required by tool routes.** |
| `php/seo_helper.php` | SEO metadata, absolute URLs, schema objects, image extraction, and public-page head rendering. **Required by public pages.** |
| `php/generate_sitemap.php` | Generates the XML sitemap and filters internal files. **Public utility route.** |
| `php/login.php` | Legacy login handler with validation, rate limiting, suspension and verification checks, and role redirect data. **Keep until AuthService fully matches it.** |
| `php/register.php` | Legacy registration with verification token and email-outbox behavior. **Keep until AuthService fully matches it.** |
| `php/create_website_action.php` | Legacy website provisioning with templates, ZIP deployment, reserved subdomains, traffic bootstrap, and subscription gating. **Keep until WebsiteService matches it.** |
| `php/delete_website_action.php` | Legacy deletion/suspension with ownership, directory deletion, traffic cleanup, and activity audit. **Keep until service/API matches it.** |
| `php/manage_files_action.php` | Legacy combined file-manager endpoint. **Keep until all unique builder operations are migrated to FileManagerService/APIs.** |
| `php/user_database_action.php` | User database actions. **Keep while no equivalent API fully covers it.** |
| `php/user_support_action.php` | User support actions. **Keep while no equivalent API fully covers it.** |
| `php/admin_action.php`, `admin_backup_action.php`, `admin_pricing_action.php`, `admin_rbac_action.php`, `admin_settings_action.php`, `admin_support_action.php`, `admin_team_action.php` | Admin operations and permission-sensitive actions. **Keep until each is migrated to a tested admin API/module.** |
| `php/initialize_payment.php` | Legacy production payment initialization with live-key guards and callback preparation. **Keep until PaymentService is behavior-equivalent.** |
| `php/verify_payment.php` | Legacy production verifier with webhook hash, idempotency lock, amount/currency/email/transaction matching, audit records, and plan-duration logic. **Required until fully migrated.** |
| `php/flutterwave_checkout.php` | Legacy checkout page/flow. **Keep while existing checkout links depend on it.** |
| `php/save_page.php` | CMS page save endpoint. **Required by CMS route.** |
| `php/track_metrics.php` | Public tenant traffic/metrics endpoint. **Required by tenant scripts.** |
| `php/server.php` | Legacy dispatcher/compatibility server handler. **Keep until route references are fully migrated.** |
| `php/verify.php` | Verification route support. **Required by verification flow.** |
| `php/workspace_action.php` | Workspace operations. **Keep while dashboard actions depend on it.** |
| `php/process_payment.php` | Removed obsolete simulated-payment stub. Supported payment operations now use `/api/payments/*`. |

## 10. API directory

API controllers are thin HTTP adapters. They should check the method, read request data, obtain the current user, call a service, and return JSON. They should not render dashboard HTML.

### Authentication APIs

| Endpoint | Purpose | Main dependency |
|---|---|---|
| `/api/auth/login` | Authenticates a user and creates a session. | `AuthService`. |
| `/api/auth/register` | Creates a user/trial workspace account. | `AuthService`. |
| `/api/auth/logout` | Ends the secure session. | Session helpers. |
| `/api/auth/status` | Returns current authentication/session state. | `AuthService` and session. |

### Website/domain APIs

`/api/websites/create` creates a tenant website through `WebsiteService`; `/api/websites/list` returns the current user’s authorized websites; `/api/websites/delete` deletes an authorized website; `/api/domains/connect` and `/api/domains/remove` manage custom-domain mappings through `DomainService`.

### File APIs

| Endpoint | Operation |
|---|---|
| `/api/files/list` | Lists authorized tenant directory contents. |
| `/api/files/read` | Reads a permitted file. |
| `/api/files/save` | Saves permitted text/code content. |
| `/api/files/create` | Creates a file or folder inside the workspace. |
| `/api/files/upload` | Uploads files and ZIP archives with safe extraction. |
| `/api/files/delete` | Deletes an authorized file or folder. |
| `/api/files/rename` | Renames an authorized item. |
| `/api/files/copy` | Copies an authorized item inside the workspace. |
| `/api/files/move` | Moves an authorized item inside the workspace. |

### Billing/subscription APIs

`/api/subscriptions/current` returns the active plan/trial state. `/api/subscriptions/plans` returns available plans. `/api/payments/initialize` creates a pending payment and obtains a Flutterwave checkout link. `/api/payments/verify` verifies the transaction before activation. `/api/payments/history` returns authorized payment history.

## 11. Request lifecycle

### Public page request

1. Apache receives a clean URL and routes it to `index.php`.
2. `index.php` loads the central configuration, database, security helpers, and tenant manager.
3. The request path is checked against the route map.
4. The selected public PHP template renders SEO metadata, shared navigation, content, and assets.
5. No public template should perform privileged database mutation.

### API request

1. The browser sends a jQuery AJAX request to an `/api/*` URL.
2. The API validates the HTTP method and request fields.
3. The API establishes the secure session and loads the current user.
4. The API calls an authentication, hosting, file, domain, subscription, or payment service.
5. The service re-checks authorization and resource boundaries.
6. The API returns JSON for SweetAlert2, DataTables, or the feature module.

### Secure dashboard request

1. `index.php` resolves `/admin/*` or `/user/dashboard` to a secure page.
2. The secure header calls `secureSession()` and loads the current user.
3. Admin pages validate the active role and page permission.
4. User pages load only owned websites/workspaces.
5. The dashboard assembles tabs and feature views; actions go to APIs or compatibility handlers.

## 12. JSON database

The custom `Database` class stores table records as JSON files below `databases/`. It provides table creation, insert, select, select-one, update, delete, search, and locking behavior. The database is not a public web directory and should be protected by server rules.

Common tables referenced by the backend include `users`, `websites`, `domains`, `plans`, `payments`, `traffic`, `activity_logs`, `settings`, and support/email-outbox records. Table names are strings passed to the database engine. Data migrations should be additive, validated, backed up before destructive changes, and tested against empty-table behavior.

## 13. Authentication and authorization

Authentication identifies the current user through a secure session. The session stores fields such as user ID, email, role, and name. Authorization is separate from authentication: a logged-in user may still be denied an admin page, another tenant’s workspace, a premium feature, or a suspended account.

The current permission functions are in `php/db.php`, including `secureSession()`, `enforceAuth()`, `enforcePermission()`, `checkAdminPermission()`, `hasPermission()`, `isStaff()`, `getRolePagePermissions()`, and `hasAdminPagePermission()`. `AuthorizationGateway` provides a modular façade over these functions.

The super-admin protection is a business/security rule. No admin UI or API should expose a deletion path for the immutable super-admin account. Any new user-management module must enforce that rule in the service layer, not only by hiding a button.

## 14. Tenant hosting and subdomains

A website record contains a subdomain and ownership information. The physical tenant site is stored below `public/<subdomain>`. The public URL is generated in the form:

```text
https://<subdomain>.nodexplatform.com.ng/
```

`index.php` inspects the host and resolves tenant requests through `TenantManager`. The host must be validated before a tenant directory is served. User APIs must compare the requested subdomain with an owned website record before reading or mutating tenant files.

Website provisioning may also copy templates, create default files, establish custom-domain mappings, initialize traffic records, and enforce plan/trial limits. These behaviors currently exist across legacy actions and services and must remain intact during modular migration.

## 15. File manager and ZIP security

`FileManagerService` and the current file APIs are the security boundary for tenant workspaces. A request must establish the selected website, resolve its root, normalize the relative path, canonicalize existing targets with `realpath()`, validate parent directories for new targets, and reject any path outside the tenant root.

ZIP extraction must validate each archive entry before writing. An archive entry such as `../../outside.php`, an absolute path, or a symlink escape must not be allowed to leave the authorized tenant directory. Uploads must be size-limited and stored below the intended workspace.

The Manage Files UI is now modularized as:

```text
main/public/user/tabs/manage-files.php       # thin compatibility adapter
modules/user/file-manager/view.php           # workspace presentation
modules/user/file-manager/file-manager.js    # module lifecycle boundary
api/files/*.php                              # HTTP operations
backend/services/FileManagerService.php      # security and file business logic
```

The desktop workspace uses a left workspace-tools rail, a left file tree, and a right code/GrapesJS canvas. The module preserves the existing IDs and AJAX URLs.

## 16. Billing and Flutterwave

The supported payment flow is:

```text
User selects plan
  -> /api/payments/initialize
  -> PaymentService creates pending payment
  -> Flutterwave checkout
  -> callback/webhook
  -> server-side verification
  -> subscription activation
```

The server must never activate a plan because the browser claims success. The verifier must check the transaction against the stored payment reference and expected plan data. Live credentials are loaded from environment configuration: `FLW_PUBLIC_KEY`, `FLW_SECRET_KEY`, `FLW_ENCRYPTION_KEY`, and `FLW_WEBHOOK_HASH`. `APP_BASE_URL` must be a valid HTTPS production URL for real callbacks.

The legacy `php/verify_payment.php` currently contains stronger callback/webhook behavior than the slimmer `PaymentService` path, including webhook hash validation, idempotency locking, transaction reference checks, amount/currency/email matching, activity logging, and plan duration. It must remain until those checks are migrated and independently tested.

## 17. Admin backend

The admin surface is rendered by `main/public/admin/` pages and guarded by `admin_header.php`. The header loads the current user, validates staff roles, enforces page permission, loads the sidebar, and applies secure visual assets. Admin actions are distributed between page-specific PHP handlers and API/service layers.

Admin features include dashboard analytics, users, websites, files, pages, templates, categories, payments, revenue, financial reports, analytics, marketing, support, moderation, teams, activity logs, settings, profile, and backups. The modular target is `modules/admin/<feature>/`; each feature should eventually have a controller, view, API, assets, and service boundary.

## 18. User backend

The user dashboard is a secure shell that assembles feature tabs. Current tabs include overview, website builder, Manage Files, subscriptions, support, team, user database manager, workspace settings, analytics, monetization, and profile. The modular target is `modules/user/<feature>/`.

User pages should not trust hidden fields for ownership. The server must look up the current session user, verify the website belongs to that user, and only then perform an operation. Plan-based features such as custom domains, storage, or premium security must be checked from the plan record rather than hardcoded in many views.

## 19. CMS and visual editor

`cms/admin.php` provides the protected CMS administration surface. `/cms/save_page` maps to `php/save_page.php`, which saves approved page content according to the existing CMS rules. `/editor` maps to `main/public/editor.php`, which opens the full editor for a selected tenant file.

GrapesJS uses the `gjs-file-container` hook in the Manage Files workspace and the existing editor route for full-screen editing. A visual editor must continue to use the same file and tenant authorization boundaries as the code editor; it must never write arbitrary paths received from the browser.

## 20. Security checklist

| Boundary | Required control |
|---|---|
| Sessions | Secure cookie/session setup, login checks, logout, timeout policy, and role context. |
| Authorization | Centralized role, page, permission, ownership, and plan checks. |
| Input | Method checks, normalization, validation, length limits, allowed characters, and output escaping. |
| Filesystem | Canonical path checks, root containment, safe parent checks, extension allowlists, and bounded writes. |
| ZIP files | Entry normalization, traversal rejection, symlink safety, size limits, and extraction inside the tenant root only. |
| Payments | Server-side verification, live-key checks, amount/currency/reference matching, webhook hash validation, and idempotency. |
| Secrets | `.env` outside public directories, restricted permissions, rotation, and no secrets in archives or logs. |
| Admin | Immutable super-admin rule, role-based page access, audit logs, and safe destructive-operation confirmation. |
| Storage | File locking, atomic writes where supported, backups before destructive changes, and private database directories. |
| Logs | Avoid passwords, secrets, full payment credentials, or sensitive tenant content in logs. |

## 21. Modular development rules

1. A page should assemble components and call services.
2. An API endpoint should return JSON and should not contain a complete dashboard view.
3. A service should own business rules and should be callable by both user and admin modules where appropriate.
4. A module should have a clear responsibility and its own view, JavaScript, styles, and API boundary when needed.
5. Shared UI components belong in `components/` and should not duplicate navigation, modal, toast, table, or form markup.
6. New JavaScript belongs in `assets/js/core`, `assets/js/user`, or `assets/js/admin`; do not grow one universal script indefinitely.
7. New styles belong in `assets/css/core`, `assets/css/components`, `assets/css/user`, `assets/css/admin`, or `assets/css/responsive`.
8. Compatibility adapters must remain thin and must not silently duplicate old business logic.
9. Every extraction must pass PHP linting, JavaScript syntax checks, route checks, authorization checks, and feature smoke tests before the next extraction.
10. Deleting a legacy PHP file is safe only after every route, include, AJAX call, callback, and unique security behavior has an equivalent tested replacement.

## 22. Local and production deployment

For local development, use the same codebase with a development `.env`, local JSON data, and Flutterwave test credentials. The PHP server helper can run the application locally. For production, use Apache with HTTPS, a real `APP_BASE_URL`, correct main-domain and wildcard-subdomain DNS, protected storage directories, restricted filesystem permissions, and live Flutterwave credentials stored only in the hosting environment.

The production deployment sequence is:

1. Copy the project to a clean server directory.
2. Configure Apache rewrite and the document root.
3. Create `.env` from `.env.example` and set secrets privately.
4. Set secure permissions on `.env`, `databases/`, tenant storage, uploads, and backups.
5. Confirm the PHP version and required extensions, including cURL and ZIP.
6. Verify public routes and secure redirects.
7. Verify login, registration, role protection, tenant isolation, and file operations.
8. Run a controlled Flutterwave test transaction and verify the callback/webhook.
9. Monitor logs and back up JSON data before enabling destructive admin tools.

## 23. Troubleshooting guide

| Symptom | Likely cause | First checks |
|---|---|---|
| Public page returns the wrong content | Route map or rewrite issue. | Inspect `index.php`, `.htaccess`, request path, and host. |
| Dashboard redirects to login | Missing/expired session or wrong route. | Check session cookie, `secureSession()`, and auth status API. |
| User sees another tenant’s files | Ownership check regression. | Verify `FileManagerService`, website lookup, subdomain, and realpath boundary. |
| ZIP uploads but files do not appear | Extraction path, permissions, or archive safety rejection. | Check API JSON response, tenant directory permissions, and safe-entry validation. |
| GrapesJS saves the wrong file | Selected-file query or workspace contract changed. | Check `gjs-file-container`, editor URL, selected file, and save API payload. |
| Payment initializes but plan does not activate | Callback/webhook or verification mismatch. | Check `APP_BASE_URL`, webhook hash, transaction reference, amount, currency, and logs. |
| Admin action is forbidden | Role/page permission mismatch. | Check `admin_header.php`, active role, and `hasAdminPagePermission()`. |
| JSON database data disappears | Unsafe write or permission failure. | Check locks, atomic-write behavior, backups, and server write permissions. |

## 24. Current migration status

The project has a 23-section deep-space public experience, a secure dashboard visual rebuild, a side-by-side Manage Files workspace, and the first modular extraction. The modular core facades, module directories, and documentation provide the migration foundation. The remaining backend work is to migrate each unique legacy action into tested services/APIs before deleting its compatibility PHP file.

> **Safe conclusion:** the old `php/` directory cannot be removed wholesale yet. It still contains the actual database engine, security helpers, unique payment verifier, tenant provisioning logic, audit behavior, and other compatibility handlers. Remove files only when a tested API/core replacement is demonstrably behavior-equivalent.

## References inside the repository

- `REBUILD_ARCHITECTURE.md` — public, secure, and modular rebuild architecture.
- `PHP_OVERLAP_AUDIT.md` — legacy PHP/API overlap and reference audit.
- `CLEANUP_REPORT.md` — confirmed cleanup actions and preserved files.
- `modules/README.md` — module ownership and compatibility-adapter rules.
- `NodeXGo_Refactor_Developer_Guide.docx` — broader developer guide and deployment notes.
