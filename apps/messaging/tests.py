"""
Tests for Messaging, WebRTC Calling, and Notifications.
"""
from django.test import TestCase
from django.contrib.auth import get_user_model
from rest_framework.test import APIClient
from rest_framework import status
from apps.messaging.models import Conversation, Message
from apps.calling.models import CallSession

User = get_user_model()

class MessagingAndCallingTestCase(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user1 = User.objects.create_user(email='msg1@example.com', username='msg1', password='password123')
        self.user2 = User.objects.create_user(email='msg2@example.com', username='msg2', password='password123')
        self.client.force_authenticate(user=self.user1)

    def test_create_conversation_and_message(self):
        conv = Conversation.objects.create(type='direct')
        conv.participants.add(self.user1, self.user2)

        response = self.client.post('/api/v1/messaging/messages/', {
            'conversation': conv.id,
            'content': 'Hello over WebSocket!',
            'message_type': 'text'
        })
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(Message.objects.count(), 1)

    def test_call_session(self):
        call = CallSession.objects.create(caller=self.user1, callee=self.user2, call_type='video')
        response = self.client.get('/api/v1/calling/calls/')
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data), 1)
