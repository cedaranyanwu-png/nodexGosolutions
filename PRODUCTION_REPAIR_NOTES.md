# Production Repair Notes

## Resolved issues

The dashboard navigation now keeps every valid tab visible after direct hash links, browser refreshes, and sidebar navigation. The missing tenant Support Tickets panel was added so `/user/dashboard#support` resolves to a real workspace section.

The file manager API now loads the centralized runtime configuration in every entry point, including the tenant public-root constant required by all `/api/files/*` routes. JSON database reads now use shared locks instead of exclusive write locks, preventing concurrent dashboard reads from blocking workspace provisioning and file operations.

The frontend file manager now handles authenticated API failures, safely escapes API-provided file names and paths before rendering HTML, reloads the directory when the File Manager tab is selected, and preserves a safe Overview fallback for invalid hashes. Missing legacy admin manager and financial route targets now resolve to the unified admin dashboard instead of fatal missing-file errors.

File editing and uploads reject executable server-side extensions such as PHP, CGI, Python, shell, and Phar files. Path validation remains bounded to the selected tenant root.

## Tenant public links

New tenant websites now receive a canonical public URL in the creation response and database record, using the host-based single-entry format `https://{subdomain}.nodexplatform.com.ng/`. The main router resolves the request hostname to the tenant’s `public/{subdomain}/` directory and serves its homepage and assets through the same PHP entry point. Existing website records without a stored URL receive the same computed fallback in both tenant and admin website lists.

## Verification performed

The project passed PHP syntax validation for all PHP files and JavaScript syntax validation for `app.js`. Public routes, route fallbacks, dashboard assets, and unauthenticated API protection were smoke-tested over HTTP. A disposable authenticated end-to-end test successfully provisioned a workspace and completed file-manager list, folder creation, file creation, save, read, and delete operations. Test accounts and workspaces were removed before packaging.

## Deployment note

Run the project behind the intended PHP web server with the document root and rewrite/router configuration supplied by the deployment environment. Ensure the `databases/` and `public/` directories are writable by the application user, while sensitive database files remain outside direct public access.
