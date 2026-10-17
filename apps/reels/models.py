"""
Models for Vertical Reels (Short-Form Videos).
"""
from django.db import models
from django.conf import settings

class Reel(models.Model):
    """
    Vertical short-video feeds with video captions, cover images, creator attribution,
    and audio metadata.
    """
    author = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='reels')
    video_file = models.FileField(upload_to='reels/videos/')
    cover_image = models.ImageField(upload_to='reels/covers/', blank=True, null=True)
    caption = models.TextField(max_length=500, blank=True)
    audio_title = models.CharField(max_length=150, default='Original Audio')
    creator_attribution = models.CharField(max_length=150, blank=True)
    views_count = models.PositiveIntegerField(default=0)

    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Reel {self.id} by {self.author.username}"
