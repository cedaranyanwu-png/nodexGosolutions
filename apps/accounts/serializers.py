"""
Serializers for User Accounts & Authentication.
"""
from rest_framework import serializers
from django.contrib.auth import get_user_model

User = get_user_model()

class UserRegisterSerializer(serializers.ModelSerializer):
    """Serializer for user registration."""
    password = serializers.CharField(write_only=True, min_length=6)

    class Meta:
        model = User
        fields = ('id', 'email', 'username', 'password', 'phone_number', 'account_mode')

    def create(self, validated_data):
        user = User.objects.create_user(
            email=validated_data['email'],
            username=validated_data['username'],
            password=validated_data['password'],
            phone_number=validated_data.get('phone_number'),
            account_mode=validated_data.get('account_mode', 'personal')
        )
        return user


class UserSerializer(serializers.ModelSerializer):
    """Serializer for public and internal user details."""
    class Meta:
        model = User
        fields = (
            'id', 'email', 'username', 'phone_number', 'is_email_verified',
            'account_mode', 'ghost_mode_enabled', 'hide_online_status',
            'hide_last_seen', 'hide_read_receipts', 'is_private_account',
            'mfa_enabled', 'is_suspended', 'is_synthetic', 'created_at'
        )
        read_only_fields = ('id', 'is_email_verified', 'is_suspended', 'is_synthetic', 'created_at')


class GhostModeSettingsSerializer(serializers.ModelSerializer):
    """Serializer for Ghost Mode privacy options."""
    class Meta:
        model = User
        fields = (
            'ghost_mode_enabled', 'hide_online_status', 'hide_last_seen',
            'hide_read_receipts', 'is_private_account'
        )
