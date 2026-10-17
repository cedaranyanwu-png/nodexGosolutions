"""
URL routing for Professional Rooms.
"""
from django.urls import path, include
from rest_framework.routers import DefaultRouter
from apps.communities.views import ProfessionalRoomViewSet

app_name = "professional_rooms"

router = DefaultRouter()
router.register(r'', ProfessionalRoomViewSet, basename='professional-room')

urlpatterns = [
    path('', include(router.urls)),
]
