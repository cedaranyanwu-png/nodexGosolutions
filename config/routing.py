"""
QuickChat WebSocket URL patterns for Chat and WebRTC Calling.
"""
from django.urls import path
from apps.messaging.consumers import ChatConsumer, WebRTCSignalingConsumer

websocket_urlpatterns = [
    path('ws/chat/<int:conversation_id>/', ChatConsumer.as_asgi()),
    path('ws/calling/<int:user_id>/', WebRTCSignalingConsumer.as_asgi()),
]
