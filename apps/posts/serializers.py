"""
Serializers for Posts, Comments, Reactions, Stories, Reels, and Recommendations.
"""
from rest_framework import serializers
from apps.posts.models import Post, PostMedia, PollOption, Comment, LikeReaction, Bookmark
from apps.stories.models import Story, StoryView
from apps.reels.models import Reel
from apps.accounts.serializers import UserSerializer

class PostMediaSerializer(serializers.ModelSerializer):
    class Meta:
        model = PostMedia
        fields = ('id', 'file', 'media_type', 'order')

class PollOptionSerializer(serializers.ModelSerializer):
    votes_count = serializers.SerializerMethodField()

    class Meta:
        model = PollOption
        fields = ('id', 'option_text', 'votes_count')

    def get_votes_count(self, obj):
        return obj.votes.count()

class CommentSerializer(serializers.ModelSerializer):
    author = UserSerializer(read_only=True)

    class Meta:
        model = Comment
        fields = ('id', 'post', 'author', 'parent', 'content', 'created_at')
        read_only_fields = ('id', 'author', 'created_at')

class PostSerializer(serializers.ModelSerializer):
    author = UserSerializer(read_only=True)
    media = PostMediaSerializer(many=True, read_only=True)
    poll_options = PollOptionSerializer(many=True, read_only=True)
    likes_count = serializers.SerializerMethodField()
    comments_count = serializers.SerializerMethodField()

    class Meta:
        model = Post
        fields = (
            'id', 'author', 'content', 'post_type', 'audience', 'is_draft',
            'scheduled_for', 'hashtags', 'mentions', 'allow_comments',
            'media', 'poll_options', 'likes_count', 'comments_count', 'created_at'
        )
        read_only_fields = ('id', 'author', 'created_at')

    def get_likes_count(self, obj):
        return obj.reactions.filter(reaction_type='like').count()

    def get_comments_count(self, obj):
        return obj.comments.count()

class StorySerializer(serializers.ModelSerializer):
    author = UserSerializer(read_only=True)
    views_count = serializers.SerializerMethodField()

    class Meta:
        model = Story
        fields = ('id', 'author', 'media_file', 'media_type', 'caption', 'is_close_friends_only', 'stickers', 'views_count', 'created_at', 'expires_at')
        read_only_fields = ('id', 'author', 'created_at', 'expires_at')

    def get_views_count(self, obj):
        return obj.views.count()

class ReelSerializer(serializers.ModelSerializer):
    author = UserSerializer(read_only=True)

    class Meta:
        model = Reel
        fields = ('id', 'author', 'video_file', 'cover_image', 'caption', 'audio_title', 'creator_attribution', 'views_count', 'created_at')
        read_only_fields = ('id', 'author', 'views_count', 'created_at')
