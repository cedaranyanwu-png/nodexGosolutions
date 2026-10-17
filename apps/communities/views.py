"""
Views for Communities, Professional Rooms, Creator Analytics, and Verification.
"""
from rest_framework import viewsets, permissions, status, views
from rest_framework.response import Response
from apps.communities.models import Community, ProfessionalRoom, VerificationApplication
from apps.creators.models import CreatorAnalytics
from apps.communities.serializers import CommunitySerializer, ProfessionalRoomSerializer, VerificationApplicationSerializer, CreatorAnalyticsSerializer

class CommunityViewSet(viewsets.ModelViewSet):
    """ViewSet for Communities."""
    queryset = Community.objects.all().order_by('-created_at')
    serializer_class = CommunitySerializer
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]

    def perform_create(self, serializer):
        serializer.save(owner=self.request.user)


class ProfessionalRoomViewSet(viewsets.ModelViewSet):
    """ViewSet for Professional Collaboration Rooms."""
    serializer_class = ProfessionalRoomSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return ProfessionalRoom.objects.filter(owner=self.request.user)

    def perform_create(self, serializer):
        serializer.save(owner=self.request.user)


class VerificationApplicationViewSet(viewsets.ModelViewSet):
    """ViewSet for Creator & Business Verification applications."""
    serializer_class = VerificationApplicationSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return VerificationApplication.objects.filter(user=self.request.user)

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class CreatorAnalyticsView(views.APIView):
    """API endpoint providing analytics breakdown for creators."""
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        analytics = CreatorAnalytics.objects.filter(creator=request.user).order_by('-period_end')
        serializer = CreatorAnalyticsSerializer(analytics, many=True)
        return Response({'analytics': serializer.data})
