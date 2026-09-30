# NodexPlatform reconstruction

## Scope

This pass keeps the existing Django-style server-rendered architecture and reconnects the product around the workflows that already have real templates and backend support:

- `/` — compact public landing page
- `/login/`, `/signup/`, `/forgot-password/`, `/verify/` — authentication flow
- `/users/dashboard/` — authenticated workspace overview
- `/profile/` — authenticated profile editing
- `/quick-request/` — talent search and request submission
- `/client-register/` — client onboarding, using the same registration API with a business account type
- `/terms/`, `/privacy/` — legal pages
- `/admin/dashboard/` — project admin surface

Legacy marketing links that pointed to non-existent pages were removed from the shared navigation and footer. Landing-page content now uses real section anchors instead of dead routes.

## Backend/API contract

All dynamic endpoints live under `/python/api/`:

- `auth/register/`, `auth/login/`, `auth/logout/`, `auth/me/`, `auth/verify/`, `auth/forgot-password/`
- `profile/update/`, `profile/password/`, `profile/image/`, `profile/account-type/`
- `workspace/create/`, `workspace/invite/`, `workspace/info/`
- `requests/create/` — validates and stores quick requests in `databases/requests.txt`

The frontend calls these endpoints through `Main/static/js/nodex.js` and `Main/static/js/auth.js`. The shared script uses same-origin credentials so Django sessions remain the source of truth; localStorage is no longer required for protected-page access.

## UI direction

The redesign uses a compact editorial dashboard style: navy ink, cobalt actions, teal status accents, 12px cards, 60px navigation, smaller type scale, tighter spacing, and responsive containers capped at 1120px. `Main/static/css/nodex.css` is loaded after page styles so it can normalize the older templates without a risky framework migration.

## Maintenance notes

- Add new page views to `config/views.py` and document them here.
- Add dynamic endpoints to `Main/python/urls.py`, then implement the handler in `Main/python/views.py`.
- Prefer `Nodex.api()` and `Nodex.showMessage()` for new browser interactions.
- Keep comments focused on decisions and contracts, not line-by-line narration.
