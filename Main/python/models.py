"""
NodexPlatform Database Models
Updated with Email Verification, Profile Logic, and Account Types
"""

import hashlib
import uuid
from .database import get_database, DatabaseError, SecurityError
from datetime import datetime


class User:
    def __init__(self):
        self.db = get_database('users')

    def create(self, email: str, password: str, first_name: str, last_name: str, account_type: str = 'personal') -> dict:
        try:
            if self.db.select_one({'email': email}):
                raise Exception("Email already registered")

            hashed_password = hashlib.sha256(password.encode()).hexdigest()
            verification_token = str(uuid.uuid4())

            user_id = self.db.insert({
                'email': email,
                'password': hashed_password,
                'first_name': first_name,
                'last_name': last_name,
                'account_type': account_type,
                'status': 'pending',
                'verification_token': verification_token,
                'role': 'user',
                'profile_image': None
            })

            return {
                'id': user_id,
                'email': email,
                'verification_token': verification_token,
                'account_type': account_type
            }

        except (DatabaseError, SecurityError, Exception) as e:
            raise Exception(f"Failed to create user: {str(e)}")

    def find_by_email(self, email: str):
        try:
            return self.db.select_one({'email': email})
        except DatabaseError as e:
            raise Exception(f"Failed to find user: {str(e)}")

    def find_by_token(self, token: str):
        try:
            return self.db.select_one({'verification_token': token})
        except DatabaseError as e:
            raise Exception(f"Failed to find user by token: {str(e)}")

    def verify_user(self, token: str) -> bool:
        try:
            user = self.find_by_token(token)
            if not user:
                return False

            self.db.update(
                {'verification_token': token},
                {'status': 'active', 'verification_token': ''}
            )
            return True
        except Exception:
            return False

    def verify_password(self, email: str, password: str) -> dict:
        try:
            user = self.find_by_email(email)
            if not user:
                return None

            hashed_password = hashlib.sha256(password.encode()).hexdigest()
            if user['password'] == hashed_password:
                return user
            return None
        except Exception:
            return None

    def update_profile(self, email: str, data: dict) -> bool:
        try:
            user = self.find_by_email(email)
            if not user:
                raise Exception("User not found")

            if 'email' in data and data['email'] and data['email'] != email:
                existing = self.db.select_one({'email': data['email']})
                if existing:
                    raise Exception("Email already in use by another account")

            allowed_fields = ['first_name', 'last_name', 'email', 'account_type']
            update_data = {k: v for k, v in data.items() if k in allowed_fields and v}

            # Account type is intentionally restricted to the two values used
            # by the UI, preventing invalid values from entering the database.
            if 'account_type' in update_data and update_data['account_type'] not in {'personal', 'business'}:
                raise Exception('Account type must be personal or business')

            if not update_data:
                raise Exception("No valid fields to update")

            return self.db.update({'email': email}, update_data) > 0

        except Exception as e:
            raise Exception(f"Failed to update profile: {str(e)}")

    def change_password(self, email: str, current_password: str, new_password: str) -> bool:
        try:
            user = self.find_by_email(email)
            if not user:
                raise Exception("User not found")

            current_hash = hashlib.sha256(current_password.encode()).hexdigest()
            if user['password'] != current_hash:
                raise Exception("Current password is incorrect")

            if len(new_password) < 8:
                raise Exception("New password must be at least 8 characters")
            if not any(c.isupper() for c in new_password):
                raise Exception("New password must contain at least one uppercase letter")
            if not any(c.isdigit() for c in new_password):
                raise Exception("New password must contain at least one number")

            new_hash = hashlib.sha256(new_password.encode()).hexdigest()
            return self.db.update({'email': email}, {'password': new_hash}) > 0

        except Exception as e:
            raise Exception(str(e))

    def update_profile_image(self, email: str, image_path: str) -> bool:
        try:
            user = self.find_by_email(email)
            if not user:
                raise Exception("User not found")

            return self.db.update({'email': email}, {'profile_image': image_path}) > 0

        except Exception as e:
            raise Exception(f"Failed to update profile image: {str(e)}")

class Workspace:
    def __init__(self):
        self.db = get_database('workspaces')

    def create(self, name: str, owner_email: str) -> dict:
        try:
            workspace_id = f"ws_{uuid.uuid4().hex[:8]}"
            self.db.insert({
                'id': workspace_id,
                'name': name,
                'owner_email': owner_email,
                'created_at': datetime.now().isoformat()
            })
            return {'id': workspace_id, 'name': name}
        except Exception as e:
            raise Exception(f"Failed to create workspace: {str(e)}")

    def get_by_id(self, workspace_id: str):
        return self.db.select_one({'id': workspace_id})

    def get_by_owner(self, owner_email: str):
        return self.db.select_one({'owner_email': owner_email})

    def update(self, workspace_id: str, data: dict) -> bool:
        return self.db.update({'id': workspace_id}, data) > 0
