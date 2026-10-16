"""
Tests for Accounts and Profiles.
"""
from django.test import TestCase
from django.contrib.auth import get_user_model
from rest_framework.test import APIClient
from rest_framework import status
from apps.profiles.models import UserProfile, Follow

User = get_user_model()

class AccountsAndProfilesTestCase(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user1_data = {
            'email': 'alice@example.com',
            'username': 'alice',
            'password': 'password123'
        }
        self.user2_data = {
            'email': 'bob@example.com',
            'username': 'bob',
            'password': 'password123'
        }
        self.user1 = User.objects.create_user(**self.user1_data)
        self.user2 = User.objects.create_user(**self.user2_data)

    def test_user_registration(self):
        response = self.client.post('/api/v1/accounts/register/', {
            'email': 'charlie@example.com',
            'username': 'charlie',
            'password': 'password123'
        })
        self.assertIn(response.status_code, [200, 201])
        self.assertTrue(User.objects.filter(email='charlie@example.com').exists())

    def test_ghost_mode_settings(self):
        self.client.force_authenticate(user=self.user1)
        response = self.client.post('/api/v1/accounts/ghost-mode/', {
            'ghost_mode_enabled': True,
            'hide_online_status': True
        })
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.user1.refresh_from_db()
        self.assertTrue(self.user1.ghost_mode_enabled)

    def test_follow_unfollow(self):
        self.client.force_authenticate(user=self.user1)
        # Follow bob
        res_follow = self.client.post(f'/api/v1/profiles/follow/{self.user2.username}/')
        self.assertEqual(res_follow.status_code, status.HTTP_201_CREATED)
        self.assertTrue(Follow.objects.filter(follower=self.user1, following=self.user2).exists())

        # Unfollow bob
        res_unfollow = self.client.delete(f'/api/v1/profiles/follow/{self.user2.username}/')
        self.assertEqual(res_unfollow.status_code, status.HTTP_200_OK)
        self.assertFalse(Follow.objects.filter(follower=self.user1, following=self.user2).exists())
