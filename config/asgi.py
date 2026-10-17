"""
QuickChat ASGI routing setup.
"""
from channels.routing import ProtocolTypeRouter, URLRouter
from channels.auth import AuthMiddlewareStack
import config.routing

application = ProtocolTypeRouter({
    "websocket": AuthMiddlewareStack(
        URLRouter(
            config.routing.websocket_urlpatterns
        )
    ),
})
