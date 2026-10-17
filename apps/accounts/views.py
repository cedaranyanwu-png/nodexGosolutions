"""
Views for Accounts API (Registration, Login, Password Reset, Ghost Mode Settings).
"""
from rest_framework import status, permissions, views
from rest_framework.response import Response
from rest_framework.authtoken.models import Token
from django.contrib.auth import authenticate, get_user_model
from apps.accounts.serializers import UserRegisterSerializer, UserSerializer, GhostModeSettingsSerializer
from apps.profiles.models import UserProfile

User = get_user_model()

class RegisterView(views.APIView):
    """API endpoint for user registration."""
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        serializer = UserRegisterSerializer(data=request.data)
        if serializer.is_valid():
            user = serializer.save()
            # Ensure associated profile exists
            UserProfile.objects.get_or_create(user=user)
            token, _ = Token.objects.get_or_create(user=user)
            return Response({
                'token': token.key,
                'user': UserSerializer(user).data
            }, status=status.HTTP_201_CREATED)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class LoginView(views.APIView):
    """API endpoint for secure user login."""
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        email = request.data.get('email')
        password = request.data.get('password')
        if not email or not password:
            return Response({'error': 'Email and password required'}, status=status.HTTP_400_BAD_REQUEST)

        # Authenticate using email as USERNAME_FIELD
        user = authenticate(request, username=email, password=password)

        if user:
            if user.is_suspended:
                return Response({'error': 'Account is suspended.'}, status=status.HTTP_403_FORBIDDEN)
            token, _ = Token.objects.get_or_create(user=user)
            return Response({
                'token': token.key,
                'user': UserSerializer(user).data
            })
        return Response({'error': 'Invalid credentials'}, status=status.HTTP_401_UNAUTHORIZED)


class MeView(views.APIView):
    """API endpoint to get and update current authenticated user."""
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        return Response(UserSerializer(request.user).data)

    def patch(self, request):
        serializer = UserSerializer(request.user, data=request.data, partial=True)
        if serializer.is_valid():
            serializer.save()
            return Response(serializer.data)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class GhostModeView(views.APIView):
    """API endpoint to configure Ghost Mode privacy settings."""
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        return Response(GhostModeSettingsSerializer(request.user).data)

    def post(self, request):
        serializer = GhostModeSettingsSerializer(request.user, data=request.data, partial=True)
        if serializer.is_valid():
            serializer.save()
            return Response(serializer.data)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)
