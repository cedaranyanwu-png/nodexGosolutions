"""
User account models for QuickChat.
"""
from django.db import models
from django.contrib.auth.models import AbstractUser, BaseUserManager

class UserManager(BaseUserManager):
    """Custom user manager for email or username authentication."""
    def create_user(self, email, username, password=None, **extra_fields):
        if not email:
            raise ValueError('Email address is required')
        if not username:
            raise ValueError('Username is required')
        email = self.normalize_email(email)
        user = self.model(email=email, username=username, **extra_fields)
        if password:
            user.set_password(password)
        else:
            user.set_unusable_password()
        user.save(using=self._db)
        return user

    def create_superuser(self, email, username, password=None, **extra_fields):
        extra_fields.setdefault('is_staff', True)
        extra_fields.setdefault('is_superuser', True)
        extra_fields.setdefault('is_active', True)
        return self.create_user(email, username, password, **extra_fields)


class User(AbstractUser):
    """
    Custom user model supporting email or phone registration, account modes
    (Personal, Creator, Professional), email verification, and Ghost Mode settings.
    """
    ACCOUNT_MODE_CHOICES = (
        ('personal', 'Personal'),
        ('creator', 'Creator'),
        ('professional', 'Professional'),
    )

    email = models.EmailField(unique=True)
    phone_number = models.CharField(max_length=20, blank=True, null=True, unique=True)
    is_email_verified = models.BooleanField(default=False)
    account_mode = models.CharField(max_length=20, choices=ACCOUNT_MODE_CHOICES, default='personal')

    # Ghost Mode & Privacy Settings
    ghost_mode_enabled = models.BooleanField(default=False, help_text="Hide online presence, read receipts, and last seen.")
    hide_online_status = models.BooleanField(default=False)
    hide_last_seen = models.BooleanField(default=False)
    hide_read_receipts = models.BooleanField(default=False)
    is_private_account = models.BooleanField(default=False)

    # Security & Status Flags
    mfa_enabled = models.BooleanField(default=False)
    is_suspended = models.BooleanField(default=False)
    is_synthetic = models.BooleanField(default=False, help_text="AI or virtual account flag")

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    objects = UserManager()

    USERNAME_FIELD = 'email'
    REQUIRED_FIELDS = ['username']

    def __str__(self):
        return f"{self.username} ({self.email})"
