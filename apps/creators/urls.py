"""
URL routing for Creator Analytics.
"""
from django.urls import path
from apps.communities.views import CreatorAnalyticsView

app_name = "creators"

urlpatterns = [
    path('analytics/', CreatorAnalyticsView.as_view(), name='creator-analytics'),
]
