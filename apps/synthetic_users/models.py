"""
Models for Synthetic Users.
"""
from django.db import models

class SyntheticUserRecord(models.Model):
    """
    Log of AI generated synthetic users.
    """
    username = models.CharField(max_length=150)
    prompt = models.TextField(blank=True)
    generated_bio = models.TextField(blank=True)
    status = models.CharField(max_length=20, default='active')
    created_at = models.DateTimeField(auto_now_add=True)
