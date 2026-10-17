"""
Serializers for Messaging, Calling, and Notifications.
"""
from rest_framework import serializers
from apps.messaging.models import Conversation, Message, MessageReceipt
from apps.calling.models import CallSession
from apps.notifications.models import AppNotification
from apps.accounts.serializers import UserSerializer

class MessageReceiptSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)

    class Meta:
        model = MessageReceipt
        fields = ('id', 'user', 'delivered_at', 'read_at')

class MessageSerializer(serializers.ModelSerializer):
    sender = UserSerializer(read_only=True)
    receipts = MessageReceiptSerializer(many=True, read_only=True)

    class Meta:
        model = Message
        fields = (
            'id', 'conversation', 'sender', 'reply_to', 'message_type', 'content',
            'attachment', 'voice_note_duration', 'is_edited', 'is_deleted',
            'is_disappearing', 'receipts', 'created_at', 'updated_at'
        )
        read_only_fields = ('id', 'sender', 'created_at', 'updated_at')

class ConversationSerializer(serializers.ModelSerializer):
    participants = UserSerializer(many=True, read_only=True)
    last_message = serializers.SerializerMethodField()

    class Meta:
        model = Conversation
        fields = ('id', 'title', 'type', 'participants', 'last_message', 'created_at', 'updated_at')

    def get_last_message(self, obj):
        last_msg = obj.messages.order_by('-created_at').first()
        if last_msg:
            return MessageSerializer(last_msg).data
        return None

class CallSessionSerializer(serializers.ModelSerializer):
    caller = UserSerializer(read_only=True)
    callee = UserSerializer(read_only=True)

    class Meta:
        model = CallSession
        fields = ('id', 'caller', 'callee', 'call_type', 'status', 'started_at', 'connected_at', 'ended_at', 'duration_seconds')

class AppNotificationSerializer(serializers.ModelSerializer):
    actor = UserSerializer(read_only=True)

    class Meta:
        model = AppNotification
        fields = ('id', 'recipient', 'actor', 'title', 'message', 'is_read', 'created_at')
