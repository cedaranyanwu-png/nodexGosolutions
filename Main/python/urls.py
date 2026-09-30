"""API routes for the Python backend.

This module is included under ``/python/api/`` by ``config.urls``.  Keeping
API routes here prevents the project URL file from becoming a long list of
backend implementation details.
"""

from django.urls import path

from . import views


urlpatterns = [
    path("auth/register/", views.api_register, name="api_register"),
    path("auth/verify/", views.api_verify, name="api_verify"),
    path("auth/login/", views.api_login, name="api_login"),
    path("auth/forgot-password/", views.api_forgot_password, name="api_forgot_password"),
    path("auth/logout/", views.api_logout, name="api_logout"),
    path("auth/me/", views.api_get_current_user, name="api_get_current_user"),
    path("auth/google-login/", views.api_google_login, name="api_google_login"),
    path("profile/update/", views.api_update_profile, name="api_update_profile"),
    path("profile/password/", views.api_change_password, name="api_change_password"),
    path("profile/image/", views.api_upload_profile_image, name="api_upload_profile_image"),
    path("profile/account-type/", views.api_update_account_type, name="api_update_account_type"),
    path("workspace/create/", views.api_create_workspace, name="api_create_workspace"),
    path("workspace/invite/", views.api_invite_worker, name="api_invite_worker"),
    path("workspace/info/", views.api_get_workspace, name="api_get_workspace"),
    path("requests/create/", views.api_create_request, name="api_create_request"),
]
