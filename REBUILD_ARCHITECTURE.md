# NodeXGo Production Rebuild Architecture

## Rebuild principle

The rebuild changes presentation, markup organization, shared styling, and frontend composition while preserving the existing PHP router, route names, authentication/session behavior, role checks, tenant isolation, file-manager APIs, GrapesJS editor route, Flutterwave server-side payment verification, and JSON/database contracts.

## Preserved logic boundaries

| Boundary | Existing contract to preserve | Rebuild treatment |
|---|---|---|
| Central router | `index.php` resolves public pages, secure pages, APIs, tenant hostnames, assets, and editor routes | Keep route keys and handlers stable; add only missing visual shell references |
| Authentication | Existing login/register form IDs, AJAX endpoints, session redirects, and validation | Keep element IDs and endpoint payloads; replace only markup and styling |
| Authorization | Admin roles, immutable super-admin behavior, tenant ownership, and page permission matrix | Keep backend checks; expose clearer responsive controls |
| File management | List/read/save/upload/delete/copy/move/rename/ZIP operations | Keep API contracts; rebuild workspace UI around the current endpoints |
| GrapesJS | Dedicated editor route and selected-file query contract | Keep route and file query behavior; rebuild the editor shell and tool layout |
| Payments | Flutterwave initialization, callback, verification, subscription upgrade behavior, and `.env` configuration | Keep server-side verification and safe secret handling; rebuild checkout/status UI |
| Tenant sites | Subdomain-first links and single-entry host routing | Keep generated URL and host resolution logic |
| Profiles | User/admin profile forms, avatar upload, password flow, and session context | Keep form names and backend actions; rebuild profile cards and responsive forms |

## Public 23-section map

1. Hero / orbital introduction. 2. Platform metrics. 3. About NodeXGo. 4. Cedar founder mandate. 5. Company principles. 6. Portfolio systems. 7. Corporate roadmap. 8. Platform traction. 9. Corporate gallery. 10. Workspace builder. 11. Security and trust. 12. Tenant websites. 13. File manager. 14. GrapesJS editor. 15. Subscription plans. 16. Flutterwave upgrades. 17. User/admin profiles. 18. Cedar and company story links. 19. FAQ. 20. Newsletter/contact CTA. 21. Partner/enterprise CTA. 22. Final launch CTA. 23. Footer and public navigation.

## Shared rebuild layers

The new presentation uses Bootstrap 5, the existing NodeXGo logo, the generated deep-space 3D assets, SweetAlert2, DataTables where tables are present, Uppy where uploads are present, GrapesJS in the editor, AOS for public motion, and a shared CSS variable system. Backend code remains commented at the boundary where a controller or service is invoked; new frontend code uses section comments and clear naming.

## Production validation

The release must pass PHP linting, JavaScript syntax checks, route-target checks, public asset MIME checks, protected API checks, authentication form-contract checks, role and tenant-isolation checks, file-operation tests, ZIP traversal tests, payment guard tests, responsive browser checks, and clean-runtime-log checks. Test data and private environment files are excluded from the final archive.

## Mandatory modularization addendum

The attached requirements make modularity a release requirement rather than an optional cleanup. The target architecture is organized into `core/`, `components/`, `modules/user/`, `modules/admin/`, `api/`, `assets/js/`, `assets/css/`, and `security/python/`. Pages should assemble views and call services; they should not contain database logic, authentication, payment processing, filesystem operations, JavaScript, CSS, and security logic in one file.

The current codebase already contains reusable backend services under `backend/services/`, API endpoints under `api/`, tab-level user views under `main/public/user/tabs/`, and shared secure headers. The migration therefore uses compatibility adapters: legacy route paths remain stable while new module directories own implementation responsibilities. Manage Files is the first concrete extraction: `main/public/user/tabs/manage-files.php` is now a thin adapter to `modules/user/file-manager/view.php`, and its existing IDs and JavaScript contracts remain unchanged.

### Initial module ownership

| Area | New owner | Compatibility boundary |
|---|---|---|
| JSON storage | `core/JsonStorage/JsonStorage.php` | Delegates to the existing locked `Database` engine. |
| JSON responses | `core/Response/JsonResponse.php` | New APIs can use `{success,message,data}` without changing old endpoints. |
| Authorization | `core/Authorization/AuthorizationGateway.php` | Delegates to existing secure session, admin permission, and tenant ownership checks. |
| User file manager | `modules/user/file-manager/` | Existing dashboard tab path remains an adapter. |
| User modules | `modules/user/<feature>/` | Existing dashboard tab paths remain compatibility views during migration. |
| Admin modules | `modules/admin/<feature>/` | Existing admin page routes remain stable while controllers/views are extracted. |
| Shared UI | `components/<feature>/` | Existing headers/sidebar/footer can be migrated one component at a time. |
| JavaScript | `assets/js/core`, `assets/js/user`, `assets/js/admin` | Existing `app.js` remains stable until each feature is extracted. |
| CSS | `assets/css/core`, `assets/css/components`, `assets/css/user`, `assets/css/admin`, `assets/css/responsive` | Existing stylesheets remain compatibility assets during migration. |
| Billing | `backend/services/PaymentService.php` plus future `core/Billing/` adapters | Flutterwave logic remains isolated from page rendering. |
| Hosting | `backend/services/WebsiteService.php`, `DomainService.php`, `CpanelService.php` plus future `core/Hosting/` adapters | Tenant and domain routes remain unchanged. |
| Security | `backend/services/FileManagerService.php` plus future `security/python/` modules | Workspace safety and ZIP traversal protections remain server-side. |

### Migration rule

Each extraction must be independently linted and smoke-tested before the next feature is moved. No compatibility adapter may silently duplicate business logic. Where a legacy file is still large, it should be reduced by delegating one responsibility at a time, with the original route and frontend selectors retained until the replacement is proven.


## Major secure-panel redesign — August 2026

The administrator and tenant panels now share a new `assets/css/secure-command.css` presentation layer. This presentation-only command-center system targets the existing `.dashboard-layout`, `.sidebar-container`, `.main-content`, `.card`, `.analytic-card`, `.table`, `.modal`, navigation, DataTables, and form classes. It creates a dark deep-space workspace with glass panels, orbital glow, stronger hierarchy, role-aware command strips, responsive sidebars, improved inputs, table pagination, modal surfaces, and a large command hero for both user and admin dashboards.

The shared secure sidebar now uses the existing `/main/assets/images/logo.png` and distinguishes `Operations Console` from `Workspace Console`. The user dashboard adds a workspace command strip and hero containing the existing website, storage, and traffic values plus links to Manage Files and Website Builder. The administrator overview adds an operations-center hero and quick links to the user directory and system settings. These additions do not change routes, role gates, session behavior, tab IDs, form IDs, AJAX URLs, file-manager hooks, payment flows, or service logic.

The secure redesign is layered after legacy styles so future visual changes can be made in one component-owned asset without rewriting each secure page. The next migration step is to replace remaining monolithic dashboard markup with independently owned feature modules while leaving the secure shell and compatibility contracts stable.
