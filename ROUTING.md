# NodexPlatform routing guide

## Add a normal page

Create a public view in `config/views.py`:

```python
def pricing_page(request):
    return render(request, "pricing.html")
```

The page is automatically available at `/pricing/`, with the URL name
`pricing`. No edit to `config/urls.py` is needed.

Naming rules:

- `home` becomes `/`
- a function ending in `_page` or `_view` loses that suffix
- underscores become `/`
- for example, `account_settings_page` becomes `/account/settings/`

## Use a custom URL

Use the decorator when the URL cannot be derived from the function name:

```python
@route("reports/<int:year>/", name="annual_report")
def annual_report(request, year):
    return render(request, "reports/annual.html", {"year": year})
```

## API routes

Backend API endpoints belong in `Main/python/urls.py` and are included under
`/python/api/`. This keeps page routing in `config/views.py` separate from
API routing and prevents the root URL file from becoming a long list.

## Development debugging

Debug is still enabled in `config/config.json` (`"debug": true`). Keep that
setting for local development only; set it to `false` and provide a strong
`DJANGO_SECRET_KEY` before production deployment.
