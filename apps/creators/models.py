"""
Models for Creator Dashboard & Analytics.
"""
from django.db import models
from django.conf import settings

class CreatorAnalytics(models.Model):
    """
    Analytics summary records for Creators and Businesses.
    """
    creator = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='analytics')
    period_start = models.DateField()
    period_end = models.DateField()
    reach = models.PositiveIntegerField(default=0)
    impressions = models.PositiveIntegerField(default=0)
    views = models.PositiveIntegerField(default=0)
    engagement_rate = models.FloatField(default=0.0)
    follower_growth = models.IntegerField(default=0)

    def __str__(self):
        return f"Analytics for {self.creator.username} ({self.period_start} to {self.period_end})"
