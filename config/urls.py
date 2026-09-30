"""Project URL configuration.

HTML routes are discovered from :mod:`config.views`, so adding a public
view there is enough to make it available.  For example, adding
``def pricing_page(request): ...`` automatically creates ``/pricing/``.
Use ``@route(...)`` in ``config.views`` for a custom URL.
"""

from inspect import getmembers, isfunction

from django.conf import settings
from django.conf.urls.static import static
from django.contrib import admin
from django.urls import include, path
from django.views.generic import RedirectView

from . import views


def _discover_page_routes(view_module):
    """Build URL patterns for functions defined in ``config.views``.

    Imported helpers and private functions are ignored.  A route decorator
    takes precedence; otherwise the function name is converted using the
    convention documented in ``config.views``.
    """
    patterns = []
    overrides = getattr(view_module, "ROUTE_OVERRIDES", {})

    for function_name, view in getmembers(view_module, isfunction):
        if view.__module__ != view_module.__name__ or function_name.startswith("_"):
            continue

        route_path = getattr(view, "route_path", None)
        if route_path is None:
            route_path = overrides.get(function_name)
        if route_path is None:
            route_path = function_name.removesuffix("_page").removesuffix("_view")
            route_path = route_path.replace("_", "/") + "/"

        route_name = getattr(
            view,
            "route_name",
            function_name.removesuffix("_page").removesuffix("_view"),
        )
        patterns.append(path(route_path, view, name=route_name))

    return patterns


# Page routes come first so the project's exact ``/admin/dashboard/`` page
# is not swallowed by Django's broader ``admin/`` prefix.
urlpatterns = _discover_page_routes(views)

# The admin route is deliberately explicit and is never inferred from page
# views.  Redirect aliases are also kept explicit because they are not views
# in config/views.py.
urlpatterns += [
    path("admin/", admin.site.urls),
    path("register/", RedirectView.as_view(url="/signup/", permanent=False), name="register_redirect"),
    path("dashboard/", RedirectView.as_view(url="/users/dashboard/", permanent=False), name="dashboard_redirect"),
    path("python/api/", include("Main.python.urls")),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)
