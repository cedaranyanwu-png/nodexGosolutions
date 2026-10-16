"""
URL Routing for Profiles.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.profiles.views import ProfileViewSet, FollowView, BlockMuteView

app_name = "profiles"

router = DefaultRouter()
router.register(r'profiles', ProfileViewSet, basename='profile')

urlpatterns = [
    path('', include(router.urls)),
    path('follow/<str:username>/', FollowView.as_view(), name='follow'),
    path('block-mute/<str:username>/', BlockMuteView.as_view(), name='block-mute'),
]
