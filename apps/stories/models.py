"""
Models for Temporary Stories & Story View Management.
"""
from django.db import models
from django.conf import settings
from datetime import timedelta
from django.utils import timezone

class Story(models.Model):
    """
    Temporary 24-hour Stories supporting close friends audience, stickers, polls, and views.
    """
    author = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='stories')
    media_file = models.FileField(upload_to='stories/')
    media_type = models.CharField(max_length=10, choices=(('image', 'Image'), ('video', 'Video')), default='image')
    caption = models.CharField(max_length=300, blank=True)
    is_close_friends_only = models.BooleanField(default=False)
    stickers = models.JSONField(default=list, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    expires_at = models.DateTimeField(blank=True)

    def save(self, *args, **kwargs):
        if not self.expires_at:
            self.expires_at = timezone.now() + timedelta(hours=24)
        super().save(*args, **kwargs)

    @property
    def is_active(self):
        return timezone.now() < self.expires_at


class StoryView(models.Model):
    """
    Tracks user views and reactions on Stories.
    """
    story = models.ForeignKey(Story, on_delete=models.CASCADE, related_name='views')
    viewer = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    viewed_at = models.DateTimeField(auto_now_add=True)
    reaction = models.CharField(max_length=20, blank=True, null=True)

    class Meta:
        unique_together = ('story', 'viewer')


class CloseFriend(models.Model):
    """
    User close friends list for restricted stories.
    """
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='close_friends')
    friend = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)

    class Meta:
        unique_together = ('user', 'friend')
