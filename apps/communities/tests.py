"""
Tests for Communities, Professional Rooms, Creator Analytics, and Verification.
"""
from django.test import TestCase
from django.contrib.auth import get_user_model
from rest_framework.test import APIClient
from rest_framework import status
from apps.communities.models import Community, ProfessionalRoom, VerificationApplication

User = get_user_model()

class CommunitiesAndProfessionalRoomsTestCase(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user(email='creator@example.com', username='creatoruser', password='password123', account_mode='creator')
        self.client.force_authenticate(user=self.user)

    def test_create_community(self):
        response = self.client.post('/api/v1/communities/', {
            'name': 'Tech Innovators',
            'slug': 'tech-innovators',
            'description': 'A community for tech builders'
        })
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(Community.objects.count(), 1)

    def test_create_professional_room(self):
        response = self.client.post('/api/v1/professional-rooms/', {
            'name': 'Client Workspace',
            'description': 'Dedicated project room'
        })
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(ProfessionalRoom.objects.count(), 1)
