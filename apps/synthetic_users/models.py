"""
Models for Synthetic User System and Generation Schedules.
"""
from django.db import models

class SyntheticUserRecord(models.Model):
    """
    Model tracking AI generated synthetic users with activity schedules and AI labels.
    """
    username = models.CharField(max_length=150, unique=True)
    display_name = models.CharField(max_length=150, blank=True)
    prompt = models.TextField(blank=True)
    generated_bio = models.TextField(blank=True)
    activity_schedule = models.CharField(max_length=50, default='daily')
    auto_replies_enabled = models.BooleanField(default=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"AI Synthetic User @{self.username}"
