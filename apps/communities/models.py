"""
Models for Communities, Professional Collaboration Rooms, Creator Tools, and Verification Applications.
"""
from django.db import models
from django.conf import settings

class Community(models.Model):
    """
    Public and private user communities with rules and member roles.
    """
    name = models.CharField(max_length=150, unique=True)
    slug = models.SlugField(max_length=150, unique=True)
    description = models.TextField(blank=True)
    cover_image = models.ImageField(upload_to='communities/covers/', blank=True, null=True)
    owner = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='owned_communities')
    is_private = models.BooleanField(default=False)
    rules = models.JSONField(default=list, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.name


class CommunityMembership(models.Model):
    """
    Community member roles (Owner, Admin, Moderator, Member).
    """
    ROLE_CHOICES = (
        ('owner', 'Owner'),
        ('admin', 'Admin'),
        ('moderator', 'Moderator'),
        ('member', 'Member'),
    )
    community = models.ForeignKey(Community, on_delete=models.CASCADE, related_name='memberships')
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='community_memberships')
    role = models.CharField(max_length=20, choices=ROLE_CHOICES, default='member')
    joined_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('community', 'user')


class ProfessionalRoom(models.Model):
    """
    Dedicated workspace for creators, businesses, professionals, teams, and clients.
    Supports topic channels, shared files, action items, meeting invitations, and notes.
    """
    name = models.CharField(max_length=150)
    description = models.TextField(blank=True)
    owner = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='owned_professional_rooms')
    is_private = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.name


class RoomChannel(models.Model):
    """
    Topic channel inside a Professional Room.
    """
    room = models.ForeignKey(ProfessionalRoom, on_delete=models.CASCADE, related_name='channels')
    name = models.CharField(max_length=100)
    topic = models.CharField(max_length=255, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)


class VerificationApplication(models.Model):
    """
    Verification applications for Creators and Businesses.
    """
    STATUS_CHOICES = (
        ('pending', 'Pending Review'),
        ('approved', 'Approved'),
        ('rejected', 'Rejected'),
    )
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='verification_applications')
    account_type = models.CharField(max_length=20, choices=(('creator', 'Creator'), ('business', 'Business')))
    document = models.FileField(upload_to='verification_docs/')
    notes = models.TextField(blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    review_notes = models.TextField(blank=True)
    submitted_at = models.DateTimeField(auto_now_add=True)
    reviewed_at = models.DateTimeField(null=True, blank=True)
