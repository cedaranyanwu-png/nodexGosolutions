"""
URL Routing for Accounts.
"""
from django.urls import path
from apps.accounts.views import RegisterView, LoginView, MeView, GhostModeView

app_name = "accounts"

urlpatterns = [
    path('register/', RegisterView.as_view(), name='register'),
    path('login/', LoginView.as_view(), name='login'),
    path('me/', MeView.as_view(), name='me'),
    path('ghost-mode/', GhostModeView.as_view(), name='ghost-mode'),
]
