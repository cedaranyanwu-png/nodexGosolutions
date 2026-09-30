"""
NodexPlatform Python Backend Views
Handles Auth, Verification, and Admin Logic
"""

from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_POST, require_GET
from django.contrib.auth import login, logout
from django.contrib.auth.models import User as DjangoUser
from django.conf import settings
import json
import os
import uuid
from google.oauth2 import id_token
from google.auth.transport import requests as google_requests

from .models import User as UserModel, Workspace
from .database import get_database

# Hardcoded Main Admin Credentials
MAIN_ADMIN_EMAIL = "nodex@nodex.com"
MAIN_ADMIN_PASSWORD = "admin123"


@csrf_exempt
@require_POST
def api_register(request):
    """Handle user registration (DOES NOT auto-login)"""
    try:
        data = json.loads(request.body)
        email = data.get('email', '').strip().lower()
        password = data.get('password', '')
        first_name = data.get('first_name', '').strip()
        last_name = data.get('last_name', '').strip()

        if not all([email, password, first_name, last_name]):
            return JsonResponse({'success': False, 'message': 'All fields are required'}, status=400)

        user_model = UserModel()
        user_data = user_model.create(email, password, first_name, last_name)

        # Generate verification link (In production, send this via email)
        domain = request.get_host()
        verify_link = f"http://{domain}/verify/?token={user_data['verification_token']}"

        return JsonResponse({
            'success': True,
            'message': 'Registration successful! Please verify your email before logging in.',
            'verify_link': verify_link,
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_GET
def api_verify(request):
    """Handle email verification"""
    try:
        token = request.GET.get('token')

        if not token:
            return JsonResponse({'success': False, 'message': 'Invalid verification link'}, status=400)

        user_model = UserModel()
        is_verified = user_model.verify_user(token)

        if is_verified:
            return JsonResponse({
                'success': True,
                'message': 'Email verified successfully! You can now log in.'
            })
        else:
            return JsonResponse({
                'success': False,
                'message': 'Invalid or expired verification link.'
            }, status=400)

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_login(request):
    """Handle login (Dual: User and Main Admin)"""
    try:
        data = json.loads(request.body)
        email = data.get('email', '').strip().lower()
        password = data.get('password', '')

        # 1. Check for Main Admin Hardcoded Credentials
        if email == MAIN_ADMIN_EMAIL and password == MAIN_ADMIN_PASSWORD:
            admin_user, created = DjangoUser.objects.get_or_create(
                username=MAIN_ADMIN_EMAIL,
                defaults={'email': MAIN_ADMIN_EMAIL, 'is_staff': True, 'is_superuser': True}
            )
            if created:
                admin_user.set_password(MAIN_ADMIN_PASSWORD)
                admin_user.save()

            login(request, admin_user)

            return JsonResponse({
                'success': True,
                'message': 'Welcome, Main Admin!',
                'role': 'admin',
                'token': 'admin_session_token',
                'user': {'email': MAIN_ADMIN_EMAIL, 'first_name': 'Main', 'last_name': 'Admin'},
                'redirect_url': '/admin/dashboard/'
            })

        # 2. Check Regular Users in Flat-File Database
        user_model = UserModel()
        user_data = user_model.verify_password(email, password)

        if not user_data:
            return JsonResponse({
                'success': False,
                'message': 'Invalid email or password.'
            }, status=401)

        # 3. BLOCK LOGIN IF EMAIL IS NOT VERIFIED
        if user_data.get('status') == 'pending':
            return JsonResponse({
                'success': False,
                'message': 'Your email is not verified. Please check your inbox or the registration screen.'
            }, status=403)

        # 4. Successful Regular User Login (Only if status is 'active')
        django_user, created = DjangoUser.objects.get_or_create(
            username=email,
            defaults={
                'email': email,
                'first_name': user_data.get('first_name', ''),
                'last_name': user_data.get('last_name', '')
            }
        )

        if not django_user.check_password(password):
             django_user.set_password(password)
             django_user.save()

        login(request, django_user)

        return JsonResponse({
            'success': True,
            'message': 'Login successful!',
            'role': 'user',
            'token': request.session.session_key or 'user_session_token',
            'user': {
                'email': email,
                'first_name': user_data.get('first_name', ''),
                'last_name': user_data.get('last_name', '')
            },
            'redirect_url': '/users/dashboard/'
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_forgot_password(request):
    """Handle forgot password requests"""
    try:
        data = json.loads(request.body)
        email = data.get('email', '').strip().lower()

        # Always return success to prevent email enumeration attacks
        return JsonResponse({
            'success': True,
            'message': 'If an account with that email exists, a password reset link has been sent.'
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_logout(request):
    """Handle logout requests"""
    logout(request)
    return JsonResponse({'success': True, 'message': 'Logged out successfully'})


@csrf_exempt
@require_GET
def api_get_current_user(request):
    """Get current logged-in user info from database"""
    try:
        # Check if user is authenticated via Django session
        if not request.user.is_authenticated:
            return JsonResponse({
                'success': False,
                'message': 'Not authenticated'
            }, status=401)

        # Get email from Django user
        email = request.user.email

        # Check if this is the Main Admin
        if email == MAIN_ADMIN_EMAIL:
            return JsonResponse({
                'success': True,
                'user': {
                    'email': MAIN_ADMIN_EMAIL,
                    'first_name': 'Main',
                    'last_name': 'Admin',
                    'role': 'admin',
                    'profile_image': None
                }
            })

        # Get user from flat-file database
        user_model = UserModel()
        user_data = user_model.find_by_email(email)

        if not user_data:
            return JsonResponse({
                'success': False,
                'message': 'User not found in database'
            }, status=404)

        return JsonResponse({
            'success': True,
            'user': {
                'email': user_data.get('email'),
                'first_name': user_data.get('first_name'),
                'last_name': user_data.get('last_name'),
                'role': user_data.get('role', 'user'),
                'profile_image': user_data.get('profile_image'),
                'status': user_data.get('status'),
                'account_type': user_data.get('account_type', 'unspecified')
            }
        })

    except Exception as e:
        return JsonResponse({
            'success': False,
            'message': str(e)
        }, status=500)


@csrf_exempt
@require_POST
def api_update_profile(request):
    """Update user profile information (Now safely includes account_type)"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        data = json.loads(request.body)
        current_email = request.user.email

        if current_email == MAIN_ADMIN_EMAIL:
            return JsonResponse({'success': False, 'message': 'Cannot update main admin profile'}, status=403)

        new_first_name = data.get('first_name', '').strip()
        new_last_name = data.get('last_name', '').strip()
        new_email = data.get('email', '').strip().lower()
        new_account_type = data.get('account_type', '').strip().lower()

        user_model = UserModel()

        # 1. Build the update dictionary with standard fields
        update_data = {
            'first_name': new_first_name,
            'last_name': new_last_name,
            'email': new_email
        }

        # 2. Safely add account_type to the dictionary if it's valid
        if new_account_type in ['personal', 'business']:
            update_data['account_type'] = new_account_type

        # 3. Update the flat-file database using the existing, proven update_profile method
        user_model.update_profile(current_email, update_data)

        # 4. ALWAYS update the Django User model to keep both databases perfectly in sync
        request.user.first_name = new_first_name
        request.user.last_name = new_last_name

        if new_email and new_email != current_email:
            request.user.email = new_email
            request.user.username = new_email

        request.user.save()

        return JsonResponse({
            'success': True,
            'message': 'Profile updated successfully!'
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_change_password(request):
    """Change user password"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        data = json.loads(request.body)
        email = request.user.email

        if email == MAIN_ADMIN_EMAIL:
            return JsonResponse({'success': False, 'message': 'Cannot change main admin password here'}, status=403)

        current_password = data.get('current_password', '')
        new_password = data.get('new_password', '')
        confirm_password = data.get('confirm_password', '')

        if not all([current_password, new_password, confirm_password]):
            return JsonResponse({'success': False, 'message': 'All password fields are required'}, status=400)

        if new_password != confirm_password:
            return JsonResponse({'success': False, 'message': 'New passwords do not match'}, status=400)

        user_model = UserModel()
        user_model.change_password(email, current_password, new_password)

        # Update Django user password too
        request.user.set_password(new_password)
        request.user.save()

        # Re-login to keep session valid
        login(request, request.user)

        return JsonResponse({
            'success': True,
            'message': 'Password changed successfully!'
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_upload_profile_image(request):
    """Upload and save profile image"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        if 'image' not in request.FILES:
            return JsonResponse({'success': False, 'message': 'No image file provided'}, status=400)

        image_file = request.FILES['image']

        # Validate file type
        allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        if image_file.content_type not in allowed_types:
            return JsonResponse({
                'success': False,
                'message': 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'
            }, status=400)

        # Validate file size (max 2MB)
        if image_file.size > 2 * 1024 * 1024:
            return JsonResponse({
                'success': False,
                'message': 'File too large. Maximum size is 2MB.'
            }, status=400)

        # Create profiles directory
        profiles_dir = settings.MEDIA_ROOT / "profiles"
        profiles_dir.mkdir(parents=True, exist_ok=True)

        # Generate unique filename
        ext = image_file.name.split('.')[-1].lower()
        unique_name = f"user_{request.user.id}_{uuid.uuid4().hex[:8]}.{ext}"
        file_path = profiles_dir / unique_name

        # Save file
        with open(file_path, 'wb+') as destination:
            for chunk in image_file.chunks():
                destination.write(chunk)

        # Store relative path in database
        relative_path = f"/media/profiles/{unique_name}"

        user_model = UserModel()
        email = request.user.email

        if email != MAIN_ADMIN_EMAIL:
            user_model.update_profile_image(email, relative_path)

        return JsonResponse({
            'success': True,
            'message': 'Profile image updated successfully!',
            'image_url': relative_path
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_update_account_type(request):
    """Allow user to change their account type (Uses proven update_profile method)"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        account_type = ''

        # 1. PRIMARY: Try to parse JSON
        if request.body:
            try:
                data = json.loads(request.body)
                account_type = data.get('account_type', '').strip().lower()
            except (json.JSONDecodeError, ValueError):
                pass

        # 2. FALLBACK: Try Form Data
        if not account_type:
            account_type = request.POST.get('account_type', '').strip().lower()

        # 3. Validate the data
        if account_type not in ['personal', 'business']:
            return JsonResponse({
                'success': False,
                'message': 'Invalid account type. Please choose "personal" or "business".'
            }, status=400)

        # Persist the selected value in the flat-file database.  The model
        # validates the value and returns False when no user was updated.
        user_model = UserModel()
        updated = user_model.update_profile(
            request.user.email,
            {'account_type': account_type},
        )
        if not updated:
            return JsonResponse({'success': False, 'message': 'User account was not found'}, status=404)

        return JsonResponse({
            'success': True,
            'account_type': account_type,
            'message': 'Account type updated successfully!',
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_create_workspace(request):
    """Create a new business workspace"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        data = json.loads(request.body)
        workspace_name = data.get('name', '').strip()

        if not workspace_name:
            return JsonResponse({'success': False, 'message': 'Workspace name is required'}, status=400)

        workspace_model = Workspace()
        new_workspace = workspace_model.create(workspace_name, request.user.email)

        # Update the user's profile to link to this workspace as owner
        user_model = UserModel()
        user_model.db.update({'email': request.user.email}, {
            'workspace_id': new_workspace['id'],
            'role': 'owner'
        })

        return JsonResponse({
            'success': True,
            'message': 'Workspace created successfully!',
            'workspace': new_workspace
        })
    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_invite_worker(request):
    """Invite a user to the workspace by email"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        data = json.loads(request.body)
        worker_email = data.get('email', '').strip().lower()
        role = data.get('role', 'worker')

        if role not in ['worker', 'admin']:
            role = 'worker'

        user_model = UserModel()
        worker = user_model.find_by_email(worker_email)

        if not worker:
            return JsonResponse({'success': False, 'message': 'User not found. They must register first.'}, status=404)

        if worker['email'] == request.user.email:
            return JsonResponse({'success': False, 'message': 'You cannot invite yourself.'}, status=400)

        # Get current workspace
        current_user = user_model.find_by_email(request.user.email)
        workspace_id = current_user.get('workspace_id')

        if not workspace_id:
            return JsonResponse({'success': False, 'message': 'You do not have a workspace yet.'}, status=400)

        # Add worker to workspace
        user_model.db.update({'email': worker_email}, {
            'workspace_id': workspace_id,
            'role': role
        })

        return JsonResponse({
            'success': True,
            'message': f'{worker_email} has been added to your workspace as a {role}.'
        })
    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_GET
def api_get_workspace(request):
    """Get workspace details and members"""
    try:
        if not request.user.is_authenticated:
            return JsonResponse({'success': False, 'message': 'Not authenticated'}, status=401)

        user_model = UserModel()
        current_user = user_model.find_by_email(request.user.email)
        workspace_id = current_user.get('workspace_id')

        if not workspace_id:
            return JsonResponse({'success': True, 'workspace': None, 'members': []})

        workspace_model = Workspace()
        workspace = workspace_model.get_by_id(workspace_id)

        # Get all members of this workspace
        all_users = user_model.db.select({'workspace_id': workspace_id})
        members = [{
            'email': u['email'],
            'first_name': u['first_name'],
            'last_name': u['last_name'],
            'role': u['role'],
            'is_owner': u['email'] == workspace['owner_email']
        } for u in all_users]

        return JsonResponse({
            'success': True,
            'workspace': workspace,
            'members': members,
            'user_role': current_user.get('role')
        })
    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_google_login(request):
    """Handle Google Sign-In and Sign-Up (Unified)"""
    try:
        data = json.loads(request.body)
        token = data.get('token')

        if not token:
            return JsonResponse({'success': False, 'message': 'No token provided'}, status=400)

        # --- TESTING MODE ---
        # If you don't have a Google Client ID yet, use "test_token" to simulate success
        if token == "test_token":
            email = "googleuser@example.com"
            first_name = "Google"
            last_name = "User"
        else:
            # --- PRODUCTION MODE ---
            # Replace 'YOUR_GOOGLE_CLIENT_ID' with your actual Client ID from Google Cloud Console
            try:
                idinfo = id_token.verify_oauth2_token(
                    token,
                    google_requests.Request(),
                    "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
                )
                email = idinfo['email']
                first_name = idinfo.get('given_name', 'User')
                last_name = idinfo.get('family_name', '')
            except ValueError:
                return JsonResponse({'success': False, 'message': 'Invalid Google token'}, status=401)

        user_model = UserModel()
        user_data = user_model.find_by_email(email)

        # SCENARIO 1: SIGN UP (User doesn't exist yet)
        if not user_data:
            user_model.db.insert({
                'email': email,
                'password': 'google_auth', # Placeholder, they use Google to log in
                'first_name': first_name,
                'last_name': last_name,
                'account_type': 'personal', # Default for Google sign-ups
                'status': 'active',         # Auto-verify Google users
                'verification_token': '',
                'role': 'user',
                'profile_image': None
            })
            user_data = user_model.find_by_email(email)
            is_new_user = True
        else:
            is_new_user = False

        # SCENARIO 2: SIGN IN (Create Django session for the user)
        django_user, created = DjangoUser.objects.get_or_create(username=email)
        login(request, django_user)

        return JsonResponse({
            'success': True,
            'message': 'Google login successful!' if not is_new_user else 'Account created and logged in successfully!',
            'role': 'user',
            'token': request.session.session_key or 'google_session_token',
            'user': {
                'email': email,
                'first_name': user_data.get('first_name', ''),
                'last_name': user_data.get('last_name', ''),
                'account_type': user_data.get('account_type', 'personal')
            },
            'redirect_url': '/users/dashboard/'
        })

    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)}, status=400)


@csrf_exempt
@require_POST
def api_create_request(request):
    """Validate and persist a talent request submitted by the quick-request page."""
    try:
        data = json.loads(request.body or '{}')
        required = ('title', 'description', 'email')
        if any(not str(data.get(field, '')).strip() for field in required):
            return JsonResponse({'success': False, 'message': 'Title, description, and email are required.'}, status=400)
        if '@' not in data['email']:
            return JsonResponse({'success': False, 'message': 'Please provide a valid email address.'}, status=400)
        request_db = get_database('requests')
        request_db.insert({
            'profile': str(data.get('profile', '')).strip(),
            'title': str(data['title']).strip()[:160],
            'description': str(data['description']).strip()[:4000],
            'budget_min': str(data.get('budget_min', '')).strip(),
            'budget_max': str(data.get('budget_max', '')).strip(),
            'deadline': str(data.get('deadline', '')).strip(),
            'email': str(data['email']).strip().lower(),
            'status': 'new',
        })
        return JsonResponse({'success': True, 'message': 'Your request was sent. We will follow up by email.'})
    except (json.JSONDecodeError, TypeError):
        return JsonResponse({'success': False, 'message': 'Request data must be valid JSON.'}, status=400)
    except Exception as exc:
        return JsonResponse({'success': False, 'message': str(exc)}, status=400)
