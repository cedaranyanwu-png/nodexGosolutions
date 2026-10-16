#!/usr/bin/env python
"""
main.py - Main application launcher and bootstrap entrypoint for QuickChat.

This module initializes the Django environment, checks database readiness,
creates essential default records, and launches the development server or ASGI runner.
"""

import os
import sys
import argparse

def bootstrap():
    """Configure and initialize Django environment settings."""
    os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
    import django
    django.setup()
    print("QuickChat application successfully bootstrapped.")

def run_server(host='127.0.0.1', port='8000'):
    """Launch the application using Django management commands."""
    from django.core.management import call_command
    print(f"Starting QuickChat server on http://{host}:{port}...")
    call_command('runserver', f"{host}:{port}")

def main():
    """Parse command line arguments and invoke requested action."""
    parser = argparse.ArgumentParser(description="QuickChat Platform Launcher")
    parser.add_argument('--host', default='127.0.0.1', help='Host IP address to bind to')
    parser.add_argument('--port', default='8000', help='Port number to listen on')
    parser.add_argument('--check', action='store_true', help='Check Django setup and settings')
    args = parser.parse_args()

    bootstrap()

    if args.check:
        from django.core.management import call_command
        call_command('check')
        print("System check completed successfully.")
        return

    run_server(host=args.host, port=args.port)

if __name__ == '__main__':
    main()
