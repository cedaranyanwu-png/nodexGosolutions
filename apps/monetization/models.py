"""
Models for Creator Monetization, Subscriptions, Tips, and Content Entitlements.
"""
from django.db import models
from django.conf import settings

class CreatorSubscription(models.Model):
    """
    Subscribers model for creator paid subscriptions.
    """
    subscriber = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='subscriptions')
    creator = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='subscribers')
    plan_name = models.CharField(max_length=100, default='Monthly Supporter')
    price = models.DecimalField(max_digits=10, decimal_places=2, default=9.99)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('subscriber', 'creator')


class CreatorTip(models.Model):
    """
    Voluntary creator tips.
    """
    sender = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    creator = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='received_tips')
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    message = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
