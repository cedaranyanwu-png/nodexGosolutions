"""
Serializers for User Profiles, Follows, and Blocks/Mutes.
"""
from rest_framework import serializers
from apps.profiles.models import UserProfile, Follow, BlockMute
from apps.accounts.serializers import UserSerializer

class UserProfileSerializer(serializers.ModelSerializer):
    """Serializer for user profile management."""
    user = UserSerializer(read_only=True)

    class Meta:
        model = UserProfile
        fields = (
            'id', 'user', 'display_name', 'bio', 'avatar', 'cover_image',
            'website', 'location', 'interests', 'category', 'portfolio_url',
            'business_email', 'business_phone', 'is_verified', 'created_at', 'updated_at'
        )
        read_only_fields = ('id', 'user', 'is_verified', 'created_at', 'updated_at')


class FollowSerializer(serializers.ModelSerializer):
    """Serializer for follow relationships."""
    follower = UserSerializer(read_only=True)
    following = UserSerializer(read_only=True)

    class Meta:
        model = Follow
        fields = ('id', 'follower', 'following', 'is_approved', 'created_at')


class BlockMuteSerializer(serializers.ModelSerializer):
    """Serializer for block and mute actions."""
    class Meta:
        model = BlockMute
        fields = ('id', 'user', 'target_user', 'action', 'created_at')
        read_only_fields = ('id', 'user', 'created_at')
