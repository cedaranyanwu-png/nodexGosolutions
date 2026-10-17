"""
Serializers for Payments (Bachs.io), Moderation, Synthetic Users, and Administration.
"""
from rest_framework import serializers
from apps.payments.models import PaymentTransaction
from apps.moderation.models import ModerationItem
from apps.synthetic_users.models import SyntheticUserRecord
from apps.administration.models import SuperadminAnnouncement
from apps.accounts.serializers import UserSerializer

class PaymentTransactionSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)

    class Meta:
        model = PaymentTransaction
        fields = ('id', 'user', 'transaction_id', 'provider', 'amount', 'currency', 'product_type', 'status', 'checkout_url', 'created_at', 'updated_at')

class ModerationItemSerializer(serializers.ModelSerializer):
    reporter = UserSerializer(read_only=True)

    class Meta:
        model = ModerationItem
        fields = ('id', 'reporter', 'content_type', 'content_id', 'reason', 'details', 'status', 'moderator_notes', 'created_at')

class SyntheticUserRecordSerializer(serializers.ModelSerializer):
    class Meta:
        model = SyntheticUserRecord
        fields = ('id', 'username', 'prompt', 'generated_bio', 'status', 'created_at')

class SuperadminAnnouncementSerializer(serializers.ModelSerializer):
    class Meta:
        model = SuperadminAnnouncement
        fields = ('id', 'title', 'content', 'is_published', 'created_at')
