"""
URL routing for Reels API.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.posts.views import ReelViewSet

app_name = "reels"

router = DefaultRouter()
router.register(r'', ReelViewSet, basename='reel-item')

urlpatterns = [
    path('', include(router.urls)),
]
