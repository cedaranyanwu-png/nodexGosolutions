"""
NodexPlatform Flat-File Database System
Secure MySQLi-like interface for .txt file storage
Stores data in the databases/ folder at root level
Windows-compatible (no fcntl)
"""

import os
import json
import hashlib
import threading
from pathlib import Path
from datetime import datetime
from typing import List, Dict, Any, Optional


class DatabaseError(Exception):
    """Custom exception for database errors"""
    pass


class SecurityError(Exception):
    """Custom exception for security violations"""
    pass


class FlatFileDatabase:
    """
    Secure flat-file database with MySQLi-like interface
    """

    def __init__(self, db_name: str):
        """
        Initialize database connection

        Args:
            db_name: Name of the database (will create db_name.txt in databases/ folder)
        """
        # File is at: Main/python/database.py -> three parents reach the project root.
        # Keeping records inside the project makes local runs portable and testable.
        self.base_dir = Path(__file__).resolve().parent.parent.parent
        self.db_folder = self.base_dir / "databases"

        # Ensure databases folder exists
        self.db_folder.mkdir(parents=True, exist_ok=True)

        # Set database file path
        self.db_name = self._sanitize_filename(db_name)
        self.db_path = self.db_folder / f"{self.db_name}.txt"

        # Security settings - threading.Lock works on Windows AND Linux
        self.file_lock = threading.Lock()
        self.encryption_key = self._generate_encryption_key()

        # Initialize database file if it doesn't exist
        if not self.db_path.exists():
            self._create_database()

    def _sanitize_filename(self, filename: str) -> str:
        """
        Sanitize filename to prevent directory traversal attacks
        """
        safe_name = "".join(c for c in filename if c.isalnum() or c in ('-', '_'))

        if not safe_name:
            raise SecurityError("Invalid database name")

        return safe_name

    def _generate_encryption_key(self) -> str:
        """
        Generate encryption key based on database path
        """
        key_data = str(self.db_path).encode('utf-8')
        return hashlib.sha256(key_data).hexdigest()

    def _create_database(self):
        """
        Create a new database file with initial structure
        """
        initial_data = {
            "metadata": {
                "created_at": datetime.now().isoformat(),
                "version": "1.0",
                "records": 0
            },
            "data": []
        }

        try:
            with self.file_lock:
                with open(self.db_path, 'w', encoding='utf-8') as f:
                    json.dump(initial_data, f, indent=2)
        except Exception as e:
            raise DatabaseError(f"Failed to create database: {str(e)}")

    def _read_database(self) -> Dict:
        """
        Read database file with thread locking
        """
        try:
            with self.file_lock:
                with open(self.db_path, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    return data
        except json.JSONDecodeError:
            raise DatabaseError("Database file is corrupted")
        except Exception as e:
            raise DatabaseError(f"Failed to read database: {str(e)}")

    def _write_database(self, data: Dict):
        """
        Write to database file with thread locking
        """
        try:
            with self.file_lock:
                with open(self.db_path, 'w', encoding='utf-8') as f:
                    json.dump(data, f, indent=2)
        except Exception as e:
            raise DatabaseError(f"Failed to write database: {str(e)}")

    def _sanitize_input(self, value: Any) -> Any:
        """
        Sanitize input to prevent injection attacks
        """
        if isinstance(value, str):
            value = value.strip()
            value = value.replace('\x00', '')
            return value
        return value

    def _validate_data(self, data: Dict) -> bool:
        """
        Validate data structure before insertion
        """
        if not isinstance(data, dict):
            raise SecurityError("Data must be a dictionary")

        dangerous_keys = ['__class__', '__dict__', '__init__', '__new__']
        for key in data.keys():
            if key in dangerous_keys:
                raise SecurityError(f"Dangerous key detected: {key}")

        return True

    def insert(self, data: Dict) -> int:
        """
        Insert a new record (like MySQLi INSERT)
        """
        sanitized_data = {}
        for key, value in data.items():
            sanitized_key = self._sanitize_input(key)
            sanitized_value = self._sanitize_input(value)
            sanitized_data[sanitized_key] = sanitized_value

        self._validate_data(sanitized_data)

        db_data = self._read_database()

        record_id = len(db_data['data']) + 1

        sanitized_data['id'] = record_id
        sanitized_data['created_at'] = datetime.now().isoformat()
        sanitized_data['updated_at'] = datetime.now().isoformat()

        db_data['data'].append(sanitized_data)
        db_data['metadata']['records'] = len(db_data['data'])

        self._write_database(db_data)

        return record_id

    def select(self, where: Optional[Dict] = None, limit: Optional[int] = None) -> List[Dict]:
        """
        Select records (like MySQLi SELECT)
        """
        db_data = self._read_database()
        results = []

        for record in db_data['data']:
            if where:
                match = True
                for key, value in where.items():
                    if key not in record or record[key] != value:
                        match = False
                        break

                if match:
                    results.append(record)
            else:
                results.append(record)

            if limit and len(results) >= limit:
                break

        return results

    def select_one(self, where: Dict) -> Optional[Dict]:
        """
        Select a single record
        """
        results = self.select(where, limit=1)
        return results[0] if results else None

    def update(self, where: Dict, data: Dict) -> int:
        """
        Update records (like MySQLi UPDATE)
        """
        sanitized_data = {}
        for key, value in data.items():
            sanitized_key = self._sanitize_input(key)
            sanitized_value = self._sanitize_input(value)
            sanitized_data[sanitized_key] = sanitized_value

        self._validate_data(sanitized_data)

        db_data = self._read_database()
        updated_count = 0

        for record in db_data['data']:
            match = True
            for key, value in where.items():
                if key not in record or record[key] != value:
                    match = False
                    break

            if match:
                for key, value in sanitized_data.items():
                    if key != 'id':
                        record[key] = value
                record['updated_at'] = datetime.now().isoformat()
                updated_count += 1

        if updated_count > 0:
            self._write_database(db_data)

        return updated_count

    def delete(self, where: Dict) -> int:
        """
        Delete records (like MySQLi DELETE)
        """
        db_data = self._read_database()
        original_count = len(db_data['data'])

        db_data['data'] = [
            record for record in db_data['data']
            if not all(record.get(key) == value for key, value in where.items())
        ]

        deleted_count = original_count - len(db_data['data'])

        if deleted_count > 0:
            db_data['metadata']['records'] = len(db_data['data'])
            self._write_database(db_data)

        return deleted_count

    def count(self, where: Optional[Dict] = None) -> int:
        """
        Count records
        """
        if where:
            return len(self.select(where))
        else:
            db_data = self._read_database()
            return db_data['metadata']['records']

    def truncate(self):
        """
        Delete all records
        """
        db_data = self._read_database()
        db_data['data'] = []
        db_data['metadata']['records'] = 0
        self._write_database(db_data)

    def close(self):
        """
        Close database connection
        """
        pass

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.close()


def get_database(db_name: str) -> FlatFileDatabase:
    """
    Get a database instance
    """
    return FlatFileDatabase(db_name)
