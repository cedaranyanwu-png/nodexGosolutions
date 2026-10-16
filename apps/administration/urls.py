"""
URL routing for Administration Control Center API.
"""
from django.urls import path
from apps.payments.views import SuperadminControlCenterView

app_name = "administration"

urlpatterns = [
    path('dashboard/', SuperadminControlCenterView.as_view(), name='admin-dashboard'),
]
