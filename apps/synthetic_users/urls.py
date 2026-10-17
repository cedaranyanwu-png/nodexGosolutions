"""
URL routing for Synthetic Users API.
"""
from django.urls import path
from apps.payments.views import SyntheticUserManagerView

app_name = "synthetic_users"

urlpatterns = [
    path('generate/', SyntheticUserManagerView.as_view(), name='synthetic-generate'),
]
