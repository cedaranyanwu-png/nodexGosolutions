"""
Views for Profiles API (Profile editing, follow/unfollow, block/mute).
"""
from rest_framework import status, permissions, views, viewsets
from rest_framework.response import Response
from django.shortcuts import get_object_or_404
from django.contrib.auth import get_user_model
from apps.profiles.models import UserProfile, Follow, BlockMute
from apps.profiles.serializers import UserProfileSerializer, FollowSerializer, BlockMuteSerializer

User = get_user_model()

class ProfileViewSet(viewsets.ModelViewSet):
    """ViewSet for viewing and updating user profiles."""
    queryset = UserProfile.objects.all()
    serializer_class = UserProfileSerializer
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]
    lookup_field = 'user__username'

    def get_queryset(self):
        return UserProfile.objects.select_related('user').all()

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class FollowView(views.APIView):
    """API endpoint for following, unfollowing, and accepting follow requests."""
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request, username):
        target_user = get_object_or_404(User, username=username)
        if target_user == request.user:
            return Response({'error': 'You cannot follow yourself.'}, status=status.HTTP_400_BAD_REQUEST)

        # Check if already following
        follow, created = Follow.objects.get_or_create(
            follower=request.user,
            following=target_user,
            defaults={'is_approved': not target_user.is_private_account}
        )
        if not created:
            return Response({'message': 'Already following or request pending.'}, status=status.HTTP_200_OK)

        return Response(FollowSerializer(follow).data, status=status.HTTP_201_CREATED)

    def delete(self, request, username):
        target_user = get_object_or_404(User, username=username)
        deleted, _ = Follow.objects.filter(follower=request.user, following=target_user).delete()
        if deleted:
            return Response({'message': 'Successfully unfollowed.'}, status=status.HTTP_200_OK)
        return Response({'error': 'Follow relationship not found.'}, status=status.HTTP_404_NOT_FOUND)


class BlockMuteView(views.APIView):
    """API endpoint to block or mute a user."""
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request, username):
        target_user = get_object_or_404(User, username=username)
        action = request.data.get('action')
        if action not in ['block', 'mute']:
            return Response({'error': 'Action must be block or mute.'}, status=status.HTTP_400_BAD_REQUEST)

        record, _ = BlockMute.objects.get_or_create(
            user=request.user,
            target_user=target_user,
            action=action
        )
        return Response(BlockMuteSerializer(record).data, status=status.HTTP_201_CREATED)
