"""
URL routing for Verification Applications.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.communities.views import VerificationApplicationViewSet

app_name = "verification"

router = DefaultRouter()
router.register(r'applications', VerificationApplicationViewSet, basename='verification-application')

urlpatterns = [
    path('', include(router.urls)),
]
