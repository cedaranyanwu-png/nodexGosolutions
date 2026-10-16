"""
Tests for Payments, Moderation, Synthetic Users, and Administration.
"""
from django.test import TestCase
from django.contrib.auth import get_user_model
from rest_framework.test import APIClient
from rest_framework import status
from apps.payments.models import PaymentTransaction
from apps.moderation.models import ModerationItem

User = get_user_model()

class PaymentsAndAdminTestCase(TestCase):
    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user(email='payer@example.com', username='payer', password='password123')
        self.admin = User.objects.create_superuser(email='admin@example.com', username='adminuser', password='password123')
        self.client.force_authenticate(user=self.user)

    def test_bachs_checkout(self):
        response = self.client.post('/api/v1/payments/checkout/', {
            'amount': '19.99',
            'product_type': 'subscription'
        })
        self.assertEqual(response.status_code, status.HTTP_201_CREATED)
        self.assertEqual(PaymentTransaction.objects.count(), 1)

    def test_superadmin_dashboard(self):
        self.client.force_authenticate(user=self.admin)
        response = self.client.get('/api/v1/administration/dashboard/')
        self.assertEqual(response.status_code, status.HTTP_200_OK)
        self.assertIn('metrics', response.data)
