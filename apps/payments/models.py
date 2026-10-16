"""
Models for Payments (Bachs.io transactions, subscriptions, webhooks),
Moderation Reports & Actions, Synthetic User generation logs, and Superadmin Announcements.
"""
from django.db import models
from django.conf import settings

class PaymentTransaction(models.Model):
    """
    Tracks transactions processed via Bachs.io API/SDK.
    """
    STATUS_CHOICES = (
        ('pending', 'Pending'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
        ('refunded', 'Refunded'),
    )
    PRODUCT_TYPE_CHOICES = (
        ('subscription', 'Creator Subscription'),
        ('tip', 'Creator Tip'),
        ('paid_content', 'Paid Content Access'),
    )

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='payments')
    transaction_id = models.CharField(max_length=100, unique=True)
    provider = models.CharField(max_length=50, default='bachs.io')
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    currency = models.CharField(max_length=10, default='USD')
    product_type = models.CharField(max_length=30, choices=PRODUCT_TYPE_CHOICES)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    checkout_url = models.URLField(max_length=500, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Transaction {self.transaction_id} ({self.status})"


class ModerationReport(models.Model):
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

    reporter = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='submitted_reports')
    content_type = models.CharField(max_length=50) # post, comment, profile, message
    content_id = models.PositiveIntegerField()
    reason = models.CharField(max_length=30, choices=REASON_CHOICES)
    details = models.TextField(blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    moderator_notes = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Report {self.id} on {self.content_type}:{self.content_id}"


class SyntheticAccountLog(models.Model):
    """
    Tracks AI-generated synthetic ghost users generated via llama-cpp-python or simulation.
    """
    username = models.CharField(max_length=150)
    prompt = models.TextField(blank=True)
    generated_bio = models.TextField(blank=True)
    status = models.CharField(max_length=20, default='active')
    created_at = models.DateTimeField(auto_now_add=True)


class PlatformAnnouncement(models.Model):
    """
    Official superadmin announcements and help resources.
    """
    title = models.CharField(max_length=200)
    content = models.TextField()
    is_published = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.title
