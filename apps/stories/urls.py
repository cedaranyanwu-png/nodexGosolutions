"""
URL routing for Stories API.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.posts.views import StoryViewSet

app_name = "stories"

router = DefaultRouter()
router.register(r'', StoryViewSet, basename='story-item')

urlpatterns = [
    path('', include(router.urls)),
]
