"""
URL routing for Payments (Bachs.io) API.
"""
from django.urls import path
from apps.payments.views import BachsCheckoutView, BachsWebhookView

app_name = "payments"

urlpatterns = [
    path('checkout/', BachsCheckoutView.as_view(), name='checkout'),
    path('webhook/', BachsWebhookView.as_view(), name='webhook'),
]
