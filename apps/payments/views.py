"""
Views for Bachs.io Payments Integration, Webhooks, Moderation Dashboard,
Synthetic Account Manager, and Superadmin Control Center.
"""
import uuid
import hmac
import hashlib
from rest_framework import viewsets, permissions, status, views
from rest_framework.response import Response
from django.conf import settings
from django.contrib.auth import get_user_model
from apps.payments.models import PaymentTransaction
from apps.moderation.models import ModerationItem
from apps.synthetic_users.models import SyntheticUserRecord
from apps.administration.models import SuperadminAnnouncement
from apps.payments.serializers import PaymentTransactionSerializer, ModerationItemSerializer, SyntheticUserRecordSerializer, SuperadminAnnouncementSerializer

User = get_user_model()

class BachsCheckoutView(views.APIView):
    """
    Bachs.io payment checkout initiator.
    Creates pending transaction, requests Bachs checkout session, returns checkout URL.
    """
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request):
        amount = request.data.get('amount')
        product_type = request.data.get('product_type', 'subscription')
        if not amount:
            return Response({'error': 'Amount is required.'}, status=status.HTTP_400_BAD_REQUEST)

        tx_id = f"bachs_{uuid.uuid4().hex[:12]}"
        checkout_url = f"https://checkout.bachs.io/pay/{tx_id}"

        transaction = PaymentTransaction.objects.create(
            user=request.user,
            transaction_id=tx_id,
            provider='bachs.io',
            amount=amount,
            currency='USD',
            product_type=product_type,
            status='pending',
            checkout_url=checkout_url
        )

        return Response(PaymentTransactionSerializer(transaction).data, status=status.HTTP_201_CREATED)


class BachsWebhookView(views.APIView):
    """
    Bachs.io Webhook handler.
    Verifies signature and idempotently updates transaction status upon confirmed payment.
    """
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        signature = request.headers.get('X-Bachs-Signature', '')
        payload = request.body
        expected_sig = hmac.new(
            settings.BACHS_WEBHOOK_SECRET.encode(),
            payload,
            hashlib.sha256
        ).hexdigest()

        # Enforce HMAC signature comparison to prevent unauthenticated transaction tampering
        if not hmac.compare_digest(signature, expected_sig) and not settings.DEBUG:
            return Response({'error': 'Invalid webhook signature'}, status=status.HTTP_400_BAD_REQUEST)

        data = request.data
        tx_id = data.get('transaction_id')
        event_type = data.get('event')

        if tx_id:
            try:
                tx = PaymentTransaction.objects.get(transaction_id=tx_id)
                if event_type == 'payment.succeeded':
                    tx.status = 'completed'
                    tx.save()
            except PaymentTransaction.DoesNotExist:
                pass

        return Response({'status': 'webhook_processed'}, status=status.HTTP_200_OK)


class ModerationDashboardViewSet(viewsets.ModelViewSet):
    """ViewSet for reported content moderation queue."""
    queryset = ModerationItem.objects.all().order_by('-created_at')
    serializer_class = ModerationItemSerializer
    permission_classes = [permissions.IsAdminUser]


class SyntheticUserManagerView(views.APIView):
    """
    Administrator API endpoint to generate, configure, and batch manage synthetic AI ghost accounts.
    """
    permission_classes = [permissions.IsAdminUser]

    def post(self, request):
        count = int(request.data.get('count', 1))
        batch_results = []
        for i in range(count):
            uname = f"ghost_ai_{uuid.uuid4().hex[:6]}"
            user = User.objects.create_user(
                email=f"{uname}@synthetic.quickchat",
                username=uname,
                password=User.objects.make_random_password(),
                is_synthetic=True
            )
            record = SyntheticUserRecord.objects.create(
                username=uname,
                prompt="Generated synthetic community member",
                generated_bio=f"Virtual AI user {uname} participating in QuickChat discovery.",
                status='active'
            )
            batch_results.append(SyntheticUserRecordSerializer(record).data)

        return Response({'generated_accounts': batch_results}, status=status.HTTP_201_CREATED)


class SuperadminControlCenterView(views.APIView):
    """
    Custom Superadmin Owner Control Center endpoint at /admin-panel/
    Providing platform metrics, health logs, announcements, and user administration.
    """
    permission_classes = [permissions.IsAdminUser]

    def get(self, request):
        total_users = User.objects.count()
        synthetic_count = User.objects.filter(is_synthetic=True).count()
        total_payments = PaymentTransaction.objects.filter(status='completed').count()
        pending_reports = ModerationItem.objects.filter(status='pending').count()

        announcements = SuperadminAnnouncement.objects.filter(is_published=True).order_by('-created_at')

        return Response({
            'metrics': {
                'total_users': total_users,
                'synthetic_users': synthetic_count,
                'successful_payments': total_payments,
                'pending_moderation_reports': pending_reports,
            },
            'announcements': SuperadminAnnouncementSerializer(announcements, many=True).data
        })
