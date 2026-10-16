"""
Views for Media Studio utilities (image adjustments, filters, stickers, templates).
"""
from rest_framework import views, permissions, status
from rest_framework.response import Response

class MediaEditorTemplateView(views.APIView):
    """API endpoint providing Media Studio templates for promotions, announcements, and branding."""
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]

    def get(self, request):
        templates = [
            {'id': 'promo_1', 'title': 'Promotional Showcase', 'category': 'promotions', 'aspect_ratio': '1:1'},
            {'id': 'tutorial_1', 'title': 'Tutorial & Guides Overlay', 'category': 'tutorials', 'aspect_ratio': '9:16'},
            {'id': 'branding_1', 'title': 'Creator Watermark Banner', 'category': 'branding', 'aspect_ratio': '16:9'},
        ]
        return Response({'templates': templates})
