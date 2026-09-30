"""HTML page routes for NodexPlatform.

Page ownership lives here; API ownership stays in ``Main.python.views``. This split
prevents a template link from silently depending on a backend implementation detail.
"""
from django.contrib.auth.decorators import login_required
from django.shortcuts import render, redirect
from django.contrib.auth import logout

ROUTE_OVERRIDES = {
    "home": "", "user_dashboard": "users/dashboard/", "admin_dashboard": "admin/dashboard/",
    "forgot_password_page": "forgot-password/", "quick_request_page": "quick-request/", "client_register_page": "client-register/",
    "create_workspace_page": "users/create/work/space/", "add_worker_page": "users/add-worker/",
    "invite_worker_page": "users/invite-worker/", "client_area_page": "users/client-area/",
}

def route(path: str, *, name: str | None = None):
    def decorator(view):
        view.route_path = path.strip("/") + "/" if path else ""
        view.route_name = name or view.__name__
        return view
    return decorator

def home(request):
    """Render the compact public landing page."""
    return render(request, "index.html", {"domain": "nodexplatform.com.ng"})

def login_page(request): return render(request, "login.html")
def signup_page(request): return render(request, "register.html")
def forgot_password_page(request): return render(request, "forgot-password.html")
def verify_page(request): return render(request, "verify.html", {"token": request.GET.get("token", "")})
def terms_page(request): return render(request, "terms.html")
def privacy_page(request): return render(request, "privacy.html")

@login_required
def profile_page(request): return render(request, "users/profile.html")
@login_required
def user_dashboard(request): return render(request, "users/dashboard.html")
def admin_dashboard(request): return render(request, "admin/dashboard.html")
def quick_request_page(request): return render(request, "quick_request.html")
def client_register_page(request): return render(request, "client_registration.html")
@login_required
def create_workspace_page(request): return render(request, "users/create_work_space.html")
@login_required
def add_worker_page(request): return render(request, "users/add_worker.html")
@login_required
def invite_worker_page(request): return render(request, "users/invite_worker.html")
@login_required
def client_area_page(request): return render(request, "users/client_area.html")

# Compatibility aliases retained for older internal templates. They intentionally
# resolve to the smaller product surface instead of exposing unfinished pages.
def about_page(request): return redirect('/#about')
def services_page(request): return redirect('/#services')
def contact_page(request): return redirect('/#contact')
def clients_dashboard_page(request): return redirect('/users/dashboard/')
def clients_profile_page(request): return redirect('/profile/')
def clients_settings_page(request): return redirect('/profile/')
def logout_page(request):
    logout(request)
    return redirect('/login/')
