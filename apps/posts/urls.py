"""
URL routing for Posts, Feed, Stories, Reels, and Recommendations.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.posts.views import PostViewSet, FeedView, StoryViewSet, ReelViewSet, RecommendationsView

app_name = "posts"

router = DefaultRouter()
router.register(r'posts', PostViewSet, basename='post')
router.register(r'stories', StoryViewSet, basename='story')
router.register(r'reels', ReelViewSet, basename='reel')

urlpatterns = [
    path('', include(router.urls)),
    path('feed/', FeedView.as_view(), name='feed'),
    path('recommendations/', RecommendationsView.as_view(), name='recommendations'),
]
