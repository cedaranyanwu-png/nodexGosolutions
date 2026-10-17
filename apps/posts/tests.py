"""
Tests for Posts, Feed, Stories, Reels, Recommendations, and Media Studio.
"""
from django.test import TestCase
from django.contrib.auth import get_user_model
from rest_framework.test import APIClient
from rest_framework import status
from apps.posts.models import Post
from apps.stories.models import Story
from apps.reels.models import Reel

User = get_user_model()

class SocialFeedAndMediaTestCase(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user(email='social@example.com', username='socialuser', password='password123')
        self.client.force_authenticate(user=self.user)

    def test_create_post(self):
        response = self.client.post('/api/v1/posts/posts/', {
            'content': 'Hello QuickChat world!',
            'post_type': 'standard',
            'audience': 'public'
        })
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(Post.objects.count(), 1)

    def test_feed_view(self):
        Post.objects.create(author=self.user, content='Feed post 1')
        response = self.client.get('/api/v1/feed/')
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertEqual(len(response.data), 1)

    def test_recommendations_view(self):
        response = self.client.get('/api/v1/recommendations/')
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertIn('for_you', response.data)
