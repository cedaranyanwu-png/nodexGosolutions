"""
URL routing for Media Studio.
"""
from django.urls import path
from apps.media_studio.views import MediaEditorTemplateView

app_name = "media_studio"

urlpatterns = [
    path('templates/', MediaEditorTemplateView.as_view(), name='templates'),
]
