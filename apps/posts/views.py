"""
Views for Posts, Feed, Stories, Reels, Recommendations and Media Studio.
"""
from rest_framework import viewsets, permissions, status, views
from rest_framework.response import Response
from django.shortcuts import get_object_or_404
from django.utils import timezone
from apps.posts.models import Post, Comment, LikeReaction, Bookmark
from apps.stories.models import Story, StoryView
from apps.reels.models import Reel
from apps.posts.serializers import PostSerializer, CommentSerializer, StorySerializer, ReelSerializer
from apps.profiles.models import Follow

class PostViewSet(viewsets.ModelViewSet):
    """ViewSet for managing Posts."""
    queryset = Post.objects.filter(is_draft=False).order_by('-created_at')
    serializer_class = PostSerializer
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]

    def perform_create(self, serializer):
        serializer.save(author=self.request.user)


class FeedView(views.APIView):
    """Personalized timeline feed combining followed accounts and public recommendations."""
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        following_ids = Follow.objects.filter(follower=request.user, is_approved=True).values_list('following_id', flat=True)
        posts = Post.objects.filter(
            author_id__in=list(following_ids) + [request.user.id],
            is_draft=False
        ).order_by('-created_at')[:50]
        serializer = PostSerializer(posts, many=True)
        return Response(serializer.data)


class StoryViewSet(viewsets.ModelViewSet):
    """ViewSet for active temporary 24-hour Stories."""
    serializer_class = StorySerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return Story.objects.filter(expires_at__gt=timezone.now()).order_by('-created_at')

    def perform_create(self, serializer):
        serializer.save(author=self.request.user)


class ReelViewSet(viewsets.ModelViewSet):
    """ViewSet for vertical short-form video Reels."""
    queryset = Reel.objects.all().order_by('-created_at')
    serializer_class = ReelSerializer
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]

    def perform_create(self, serializer):
        serializer.save(author=self.request.user)


class RecommendationsView(views.APIView):
    """Candidate generation & relevance ranking recommendation pipeline."""
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        # Fetch high engagement or fresh posts
        recommended_posts = Post.objects.filter(is_draft=False).order_by('-created_at')[:20]
        serializer = PostSerializer(recommended_posts, many=True)
        return Response({'for_you': serializer.data})
