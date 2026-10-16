"""
Django Channels WebSocket consumers for Chat Messaging and WebRTC Call Signaling.
"""
import json
from channels.generic.websocket import AsyncWebsocketConsumer

class ChatConsumer(AsyncWebsocketConsumer):
    """
    WebSocket consumer handling real-time chat messages, typing/recording indicators,
    read receipts, and online status updates.
    """
    async def connect(self):
        self.room_name = self.scope['url_route']['kwargs']['conversation_id']
        self.room_group_name = f'chat_{self.room_name}'

        await self.channel_layer.group_add(
            self.room_group_name,
            self.channel_name
        )
        await self.accept()

    async def disconnect(self, close_code):
        await self.channel_layer.group_discard(
            self.room_group_name,
            self.channel_name
        )

    async def receive(self, text_data):
        data = json.loads(text_data)
        event_type = data.get('type')

        # Broadcast typing, recording, or new message events to group
        await self.channel_layer.group_send(
            self.room_group_name,
            {
                'type': 'chat_event',
                'event_data': data
            }
        )

    async def chat_event(self, event):
        await self.send(text_data=json.dumps(event['event_data']))


class WebRTCSignalingConsumer(AsyncWebsocketConsumer):
    """
    WebSocket consumer handling WebRTC signaling for 1-on-1 and group calls.
    Transmits SDP offers, answers, and ICE candidates between peers.
    """
    async def connect(self):
        self.user_id = self.scope['url_route']['kwargs']['user_id']
        self.room_group_name = f'webrtc_{self.user_id}'

        await self.channel_layer.group_add(
            self.room_group_name,
            self.channel_name
        )
        await self.accept()

    async def disconnect(self, close_code):
        await self.channel_layer.group_discard(
            self.room_group_name,
            self.channel_name
        )

    async def receive(self, text_data):
        data = json.loads(text_data)
        target_user_id = data.get('target_user_id')

        if target_user_id:
            target_group = f'webrtc_{target_user_id}'
            await self.channel_layer.group_send(
                target_group,
                {
                    'type': 'signal_event',
                    'signal_data': data
                }
            )

    async def signal_event(self, event):
        await self.send(text_data=json.dumps(event['signal_data']))
