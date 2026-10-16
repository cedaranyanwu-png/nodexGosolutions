"""
QuickChat URL configuration.
"""
from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('admin/', admin.site.urls),
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
