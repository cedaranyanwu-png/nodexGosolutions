"""
URL routing for Communities.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.communities.views import CommunityViewSet

app_name = "communities"

router = DefaultRouter()
router.register(r'', CommunityViewSet, basename='community')

urlpatterns = [
    path('', include(router.urls)),
]
