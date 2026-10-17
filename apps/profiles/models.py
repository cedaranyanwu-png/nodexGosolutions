"""
UserProfile and Follow models for QuickChat.
"""
from django.db import models
from django.conf import settings

class UserProfile(models.Model):
    """
    Profile extension model holding bios, links, avatars, interest tags,
    and professional/creator metadata.
    """
    user = models.OneToOneField(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='profile')
    display_name = models.CharField(max_length=100, blank=True)
    bio = models.TextField(max_length=500, blank=True)
    avatar = models.ImageField(upload_to='avatars/', blank=True, null=True)
    cover_image = models.ImageField(upload_to='covers/', blank=True, null=True)
    website = models.URLField(max_length=200, blank=True)
    location = models.CharField(max_length=100, blank=True)
    interests = models.JSONField(default=list, blank=True, help_text="List of user interest tags for recommendations.")

    # Creator & Professional Mode Fields
    category = models.CharField(max_length=100, blank=True, help_text="Niche or business category.")
    portfolio_url = models.URLField(max_length=200, blank=True)
    business_email = models.EmailField(blank=True)
    business_phone = models.CharField(max_length=20, blank=True)
    is_verified = models.BooleanField(default=False)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Profile of {self.user.username}"


class Follow(models.Model):
    """
    Follow relationship supporting follower/following links and private account follow requests.
    """
    follower = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='following_set')
    following = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='followers_set')
    is_approved = models.BooleanField(default=True, help_text="False if pending approval for private accounts.")
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('follower', 'following')

    def __str__(self):
        status = "Approved" if self.is_approved else "Pending"
        return f"{self.follower.username} -> {self.following.username} ({status})"


class BlockMute(models.Model):
    """
    Model managing blocked and muted accounts.
    """
    ACTION_CHOICES = (
        ('block', 'Block'),
        ('mute', 'Mute'),
    )
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='blocked_muted_actions')
    target_user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='blocked_muted_by')
    action = models.CharField(max_length=10, choices=ACTION_CHOICES)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('user', 'target_user', 'action')
