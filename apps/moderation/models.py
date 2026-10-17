"""
Models for Moderation Reports & Actions.
"""
from django.db import models
from django.conf import settings

class ModerationItem(models.Model):
    """
    Moderation queue reports for reported posts, comments, profiles, and messages.
    """
    REASON_CHOICES = (
        ('spam', 'Spam or Misleading'),
        ('harassment', 'Harassment or Bullying'),
        ('hate_speech', 'Hate Speech'),
        ('inappropriate', 'Inappropriate Content'),
    )
    STATUS_CHOICES = (
        ('pending', 'Pending Review'),
        ('action_taken', 'Action Taken'),
        ('dismissed', 'Dismissed'),
    )

    reporter = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='mod_submitted_reports')
    content_type = models.CharField(max_length=50) # post, comment, profile, message
    content_id = models.PositiveIntegerField()
    reason = models.CharField(max_length=30, choices=REASON_CHOICES)
    details = models.TextField(blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    moderator_notes = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Moderation Item {self.id} on {self.content_type}:{self.content_id}"
