"""
URL routing for Moderation API.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.payments.views import ModerationDashboardViewSet

app_name = "moderation"

router = DefaultRouter()
router.register(r'queue', ModerationDashboardViewSet, basename='moderation-queue')

urlpatterns = [
    path('', include(router.urls)),
]
