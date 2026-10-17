"""
QuickChat URL configuration.
"""
from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static
from django.views.generic import TemplateView

urlpatterns = [
    # Server-rendered HTML page template views
    path('', TemplateView.as_view(template_name='public/home.html'), name='home'),
    path('login/', TemplateView.as_view(template_name='accounts/login.html'), name='login-page'),
    path('register/', TemplateView.as_view(template_name='accounts/register.html'), name='register-page'),
    path('feed/', TemplateView.as_view(template_name='social/feed.html'), name='feed-page'),
    path('profile/', TemplateView.as_view(template_name='profiles/profile.html'), name='profile-page'),
    path('messages/inbox/', TemplateView.as_view(template_name='messaging/inbox.html'), name='inbox-page'),
    path('creators/dashboard/', TemplateView.as_view(template_name='creators/dashboard.html'), name='creator-dashboard-page'),
    path('admin-panel/', TemplateView.as_view(template_name='admin/dashboard.html'), name='admin-dashboard-page'),

    # Django Admin
    path('admin/', admin.site.urls),

    # REST APIs
    path('api/v1/accounts/', include('apps.accounts.urls')),
    path('api/v1/profiles/', include('apps.profiles.urls')),
    path('api/v1/posts/', include('apps.posts.urls')),
    path('api/v1/feed/', include('apps.feed.urls')),
    path('api/v1/stories/', include('apps.stories.urls')),
    path('api/v1/reels/', include('apps.reels.urls')),
    path('api/v1/media-studio/', include('apps.media_studio.urls')),
    path('api/v1/discovery/', include('apps.discovery.urls')),
    path('api/v1/recommendations/', include('apps.recommendations.urls')),
    path('api/v1/messaging/', include('apps.messaging.urls')),
    path('api/v1/calling/', include('apps.calling.urls')),
    path('api/v1/notifications/', include('apps.notifications.urls')),
    path('api/v1/communities/', include('apps.communities.urls')),
    path('api/v1/professional-rooms/', include('apps.professional_rooms.urls')),
    path('api/v1/creators/', include('apps.creators.urls')),
    path('api/v1/verification/', include('apps.verification.urls')),
    path('api/v1/analytics/', include('apps.analytics.urls')),
    path('api/v1/monetization/', include('apps.monetization.urls')),
    path('api/v1/payments/', include('apps.payments.urls')),
    path('api/v1/moderation/', include('apps.moderation.urls')),
    path('api/v1/synthetic-users/', include('apps.synthetic_users.urls')),
    path('api/v1/administration/', include('apps.administration.urls')),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)
