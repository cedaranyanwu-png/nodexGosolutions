"""
Models for Recommendation Signals & Candidate Ranking.
"""
from django.db import models
from django.conf import settings

class RecommendationInteraction(models.Model):
    """
    Log of explicit and implicit engagement signals for the recommendation pipeline.
    """
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    content_type = models.CharField(max_length=50) # post, reel, story
    content_id = models.PositiveIntegerField()
    action = models.CharField(max_length=50) # like, watch_completion, share, not_interested
    score = models.FloatField(default=1.0)
    created_at = models.DateTimeField(auto_now_add=True)
