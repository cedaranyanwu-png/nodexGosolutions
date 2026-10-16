"""
URL routing for Recommendations API.
"""
from django.urls import path
from apps.posts.views import RecommendationsView

app_name = "recommendations"

urlpatterns = [
    path('', RecommendationsView.as_view(), name='recommendations-index'),
]
