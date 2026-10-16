"""
URL routing for Calling API.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.messaging.views import CallSessionViewSet

app_name = "calling"

router = DefaultRouter()
router.register(r'calls', CallSessionViewSet, basename='call-session')

urlpatterns = [
    path('', include(router.urls)),
]
