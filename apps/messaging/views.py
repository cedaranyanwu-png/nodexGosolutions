"""
Views for Messaging, Calling, and Notifications REST APIs.
"""
from rest_framework import viewsets, permissions, status, views
from rest_framework.response import Response
from apps.messaging.models import Conversation, Message
from apps.calling.models import CallSession
from apps.notifications.models import AppNotification
from apps.messaging.serializers import ConversationSerializer, MessageSerializer, CallSessionSerializer, AppNotificationSerializer

class ConversationViewSet(viewsets.ModelViewSet):
    """ViewSet for managing direct and group conversations."""
    serializer_class = ConversationSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return Conversation.objects.filter(participants=self.request.user).order_by('-updated_at')

    def perform_create(self, serializer):
        conversation = serializer.save()
        conversation.participants.add(self.request.user)


class MessageViewSet(viewsets.ModelViewSet):
    """ViewSet for messages in conversations."""
    serializer_class = MessageSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return Message.objects.filter(conversation__participants=self.request.user).order_by('created_at')

    def perform_create(self, serializer):
        serializer.save(sender=self.request.user)


class CallSessionViewSet(viewsets.ModelViewSet):
    """ViewSet for audio and video call sessions."""
    serializer_class = CallSessionSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return CallSession.objects.filter(caller=self.request.user) | CallSession.objects.filter(callee=self.request.user)


class NotificationViewSet(viewsets.ReadOnlyModelViewSet):
    """ViewSet for in-app notifications."""
    serializer_class = AppNotificationSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return AppNotification.objects.filter(recipient=self.request.user).order_by('-created_at')
