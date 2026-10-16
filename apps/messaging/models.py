"""
Models for Real-time WhatsApp-style Messaging.
"""
from django.db import models
from django.conf import settings

class Conversation(models.Model):
    """
    Direct 1-on-1 or Group conversation thread.
    """
    CONVERSATION_TYPE_CHOICES = (
        ('direct', 'Direct Message'),
        ('group', 'Group Chat'),
    )
    title = models.CharField(max_length=150, blank=True)
    type = models.CharField(max_length=10, choices=CONVERSATION_TYPE_CHOICES, default='direct')
    participants = models.ManyToManyField(settings.AUTH_USER_MODEL, related_name='conversations')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Conversation {self.id} ({self.type})"


class Message(models.Model):
    """
    WhatsApp-style message supporting text, voice notes, media attachments, link previews,
    reply-to, forwarding, disappearing messages, reactions, read receipts, and typing states.
    """
    MESSAGE_TYPE_CHOICES = (
        ('text', 'Text Message'),
        ('voice_note', 'Voice Note'),
        ('image', 'Image'),
        ('video', 'Video'),
        ('document', 'Document'),
        ('poll', 'Poll'),
    )

    conversation = models.ForeignKey(Conversation, on_delete=models.CASCADE, related_name='messages')
    sender = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='sent_messages')
    reply_to = models.ForeignKey('self', on_delete=models.SET_NULL, null=True, blank=True, related_name='replies')
    message_type = models.CharField(max_length=20, choices=MESSAGE_TYPE_CHOICES, default='text')
    content = models.TextField(blank=True)

    # Media & Voice Note attributes
    attachment = models.FileField(upload_to='chat_attachments/', blank=True, null=True)
    voice_note_duration = models.FloatField(default=0.0, help_text="Duration in seconds for voice notes.")

    # Status flags
    is_edited = models.BooleanField(default=False)
    is_deleted = models.BooleanField(default=False)
    is_disappearing = models.BooleanField(default=False)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Message {self.id} from {self.sender.username}"


class MessageReceipt(models.Model):
    """
    Delivery and Read receipts for every message participant.
    """
    message = models.ForeignKey(Message, on_delete=models.CASCADE, related_name='receipts')
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    delivered_at = models.DateTimeField(null=True, blank=True)
    read_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        unique_together = ('message', 'user')
