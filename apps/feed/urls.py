"""
URL routing for Feed API.
"""
from django.urls import path
from apps.posts.views import FeedView

app_name = "feed"

urlpatterns = [
    path('', FeedView.as_view(), name='feed-index'),
]
