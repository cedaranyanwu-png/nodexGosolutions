#!/usr/bin/env python3
import os, sys, json
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent
CONFIG_FILE = PROJECT_ROOT / "config" / "config.json"

# CRITICAL FIX: Add project root to sys.path BEFORE Django loads
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

def load_config():
    if not CONFIG_FILE.exists():
        print(f"❌ Critical Error: config.json not found at {CONFIG_FILE}")
        sys.exit(1)
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)

def validate_fbc():
    paths = load_config().get("paths", {})
    missing = [p for k, p in paths.items() if k != "main" and not (PROJECT_ROOT / p).exists()]
    if missing:
        print(f"❌ Critical Error: Missing FBC directories: {', '.join(missing)}")
        sys.exit(1)

    if not (PROJECT_ROOT / "manage.py").exists():
        print(f"❌ Critical Error: manage.py not found at ROOT.")
        sys.exit(1)

    print("✅ FBC Directory structure validated.")

def main():
    print("🚀 Starting NodexPlatform...")
    config = load_config()
    print(f"📌 Environment : {config.get('environment', 'development').capitalize()}")
    print(f"🌐 Domain      : {config.get('domain', 'localhost')}")

    validate_fbc()

    os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
    os.environ["PROJECT_ENV"] = config.get("environment", "development")

    try:
        from django.core.management import execute_from_command_line
        if len(sys.argv) == 1:
            port = config.get("ports", {}).get("django_main", 8000)
            print(f"\n✅ Server running!")
            print(f"👉 OPEN IN BROWSER: http://127.0.0.1:{port}/")
            print(f"👉 LIVE DOMAIN:     https://{config.get('domain')}\n")
            execute_from_command_line(["manage.py", "runserver", f"127.0.0.1:{port}"])
        else:
            execute_from_command_line(["manage.py"] + sys.argv[1:])
    except ImportError:
        print("❌ Django not installed. Run: pip install -r requirements.txt")
        sys.exit(1)

if __name__ == "__main__":
    main()