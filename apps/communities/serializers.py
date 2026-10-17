"""
Serializers for Communities, Professional Rooms, Creators, and Verification.
"""
from rest_framework import serializers
from apps.communities.models import Community, CommunityMembership, ProfessionalRoom, RoomChannel, VerificationApplication
from apps.creators.models import CreatorAnalytics
from apps.accounts.serializers import UserSerializer

class CommunityMembershipSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)

    class Meta:
        model = CommunityMembership
        fields = ('id', 'community', 'user', 'role', 'joined_at')

class CommunitySerializer(serializers.ModelSerializer):
    owner = UserSerializer(read_only=True)
    members_count = serializers.SerializerMethodField()

    class Meta:
        model = Community
        fields = ('id', 'name', 'slug', 'description', 'cover_image', 'owner', 'is_private', 'rules', 'members_count', 'created_at')

    def get_members_count(self, obj):
        return obj.memberships.count()

class RoomChannelSerializer(serializers.ModelSerializer):
    class Meta:
        model = RoomChannel
        fields = ('id', 'room', 'name', 'topic', 'created_at')

class ProfessionalRoomSerializer(serializers.ModelSerializer):
    owner = UserSerializer(read_only=True)
    channels = RoomChannelSerializer(many=True, read_only=True)

    class Meta:
        model = ProfessionalRoom
        fields = ('id', 'name', 'description', 'owner', 'is_private', 'channels', 'created_at')

class VerificationApplicationSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)

    class Meta:
        model = VerificationApplication
        fields = ('id', 'user', 'account_type', 'document', 'notes', 'status', 'review_notes', 'submitted_at', 'reviewed_at')
        read_only_fields = ('id', 'user', 'status', 'review_notes', 'submitted_at', 'reviewed_at')

class CreatorAnalyticsSerializer(serializers.ModelSerializer):
    class Meta:
        model = CreatorAnalytics
        fields = ('id', 'creator', 'period_start', 'period_end', 'reach', 'impressions', 'views', 'engagement_rate', 'follower_growth')
