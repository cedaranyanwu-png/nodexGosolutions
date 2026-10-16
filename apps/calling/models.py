"""
Models for WebRTC Calling & Notifications.
"""
from django.db import models
from django.conf import settings

class CallSession(models.Model):
    """
    Call session tracking for 1-on-1 audio/video calls and group calls.
    """
    CALL_TYPE_CHOICES = (
        ('audio', 'Audio Call'),
        ('video', 'Video Call'),
    )
    CALL_STATUS_CHOICES = (
        ('initiated', 'Initiated'),
        ('ringing', 'Ringing'),
        ('active', 'Active'),
        ('ended', 'Ended'),
        ('declined', 'Declined'),
        ('missed', 'Missed'),
    )

    caller = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='outgoing_calls')
    callee = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='incoming_calls')
    call_type = models.CharField(max_length=10, choices=CALL_TYPE_CHOICES, default='audio')
    status = models.CharField(max_length=15, choices=CALL_STATUS_CHOICES, default='initiated')

    started_at = models.DateTimeField(auto_now_add=True)
    connected_at = models.DateTimeField(null=True, blank=True)
    ended_at = models.DateTimeField(null=True, blank=True)
    duration_seconds = models.PositiveIntegerField(default=0)

    def __str__(self):
        return f"{self.call_type} call from {self.caller.username} to {self.callee.username} ({self.status})"


class Notification(models.Model):
    """
    In-app user notifications for messages, calls, likes, comments, and system alerts.
    """
    recipient = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='notifications')
    actor = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True)
    verb = models.CharField(max_length=255)
    target_type = models.CharField(max_length=50, blank=True)
    target_id = models.PositiveIntegerField(null=True, blank=True)
    is_read = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Notification for {self.recipient.username}: {self.verb}"
