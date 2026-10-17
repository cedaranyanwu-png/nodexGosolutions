"""
Models for Superadmin Control Center and Platform Announcements.
"""
from django.db import models

class SuperadminAnnouncement(models.Model):
    """
    Official platform announcements created by superadmins.
    """
    title = models.CharField(max_length=200)
    content = models.TextField()
    is_published = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.title
