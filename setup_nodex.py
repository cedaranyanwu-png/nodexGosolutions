#!/usr/bin/env python3
"""
NodexPlatform Complete Initial Setup (FBC v5)
---------------------------------------------
Rules:
1. 'Main/' is created EMPTY. User puts HTML, CSS, JS, backend code there.
2. ALL other folders are at root level, outside Main/.
3. Django is configured to serve templates and static files from Main/.
4. All configs live in 'config/' folder.
5. Database lives exclusively in 'databases/' folder.
6. Runs locally and live via config.json with zero code changes.
"""
import json, subprocess, sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent

print()
print("=" * 60)
print("       NodexPlatform Complete Setup (FBC v5)")
print("=" * 60)
print(f"\nProject root: {PROJECT_ROOT}\n")

# ============================================================
# 1. CREATE DIRECTORIES
# ============================================================
DIRECTORIES = [
    "config",
    "databases",
    "apps",
    "Php_public",
    "Python_public",
    "Nodexjs",
    "Cgi_bin",
    "logs",
    "Main",  # Created EMPTY — no subfolders, no files
]

print("Creating FBC directories...")
for d in DIRECTORIES:
    path = PROJECT_ROOT / d
    path.mkdir(parents=True, exist_ok=True)
    # Only add .gitkeep to non-Main folders
    if d != "Main":
        (path / ".gitkeep").touch(exist_ok=True)
    print(f"  ✓ {d}/")

print("\n✓ Directory structure created.\n")

# ============================================================
# 2. CORE FILES
# ============================================================
FILES = {

    # --------------------------------------------------------
    # main.py
    # --------------------------------------------------------
    "main.py": '''#!/usr/bin/env python3
import os, sys, json
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent
CONFIG_FILE = PROJECT_ROOT / "config" / "config.json"

def load_config():
    if not CONFIG_FILE.exists():
        print(f"❌ Critical Error: config.json not found at {CONFIG_FILE}")
        sys.exit(1)
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)

def validate_fbc():
    paths = load_config().get("paths", {})
    missing = [p for k, p in paths.items() if not (PROJECT_ROOT / p).exists()]
    if missing:
        print(f"❌ Critical Error: Missing FBC directories: {', '.join(missing)}")
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
            print(f"\\n✅ Server running!")
            print(f"👉 OPEN IN BROWSER: http://127.0.0.1:{port}/")
            print(f"👉 LIVE DOMAIN:     https://{config.get('domain')}\\n")
            execute_from_command_line(["manage.py", "runserver", f"127.0.0.1:{port}"])
        else:
            execute_from_command_line(["manage.py"] + sys.argv[1:])
    except ImportError:
        print("❌ Django not installed. Run: pip install -r requirements.txt")
        sys.exit(1)

if __name__ == "__main__":
    main()
''',

    # --------------------------------------------------------
    # manage.py
    # --------------------------------------------------------
    "manage.py": '''#!/usr/bin/env python3
import os, sys
def main():
    os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
    try:
        from django.core.management import execute_from_command_line
    except ImportError as exc:
        raise ImportError("Couldn't import Django.") from exc
    execute_from_command_line(sys.argv)
if __name__ == "__main__":
    main()
''',

    # --------------------------------------------------------
    # config/__init__.py
    # --------------------------------------------------------
    "config/__init__.py": "",

    # --------------------------------------------------------
    # config/config.json
    # --------------------------------------------------------
    "config/config.json": json.dumps({
        "project_name": "nodexplatform",
        "domain": "nodexplatform.com.ng",
        "environment": "development",
        "debug": True,
        "allowed_hosts": [
            "nodexplatform.com.ng",
            "www.nodexplatform.com.ng",
            "localhost",
            "127.0.0.1",
            "[::1]"
        ],
        "ports": {
            "django_main": 8000,
            "nodejs_app": 3000,
            "php_public": 8080
        },
        "paths": {
            "main": "Main",
            "php_public": "Php_public",
            "python_public": "Python_public",
            "nodexjs": "Nodexjs",
            "cgi_bin": "Cgi_bin",
            "databases": "databases"
        },
        "database": {
            "engine": "django.db.backends.sqlite3",
            "name": "databases/db.sqlite3"
        }
    }, indent=4),

    # --------------------------------------------------------
    # config/settings.py
    # --------------------------------------------------------
    "config/settings.py": '''import os, json
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
CONFIG_FILE = Path(__file__).resolve().parent / "config.json"

with open(CONFIG_FILE, "r", encoding="utf-8") as f:
    APP_CONFIG = json.load(f)

SECRET_KEY = os.environ.get("DJANGO_SECRET_KEY", "django-insecure-local-dev-key")
DEBUG = APP_CONFIG.get("debug", True)
ENVIRONMENT = APP_CONFIG.get("environment", "development")
ALLOWED_HOSTS = APP_CONFIG.get("allowed_hosts", ["localhost", "127.0.0.1"])

INSTALLED_APPS = [
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "config.urls"

# Templates and static files are served from the EMPTY Main/ folder
# When the user puts HTML/CSS/JS into Main/, Django will find them here
TEMPLATES = [{
    "BACKEND": "django.template.backends.django.DjangoTemplates",
    "DIRS": [BASE_DIR / "Main"],
    "APP_DIRS": True,
    "OPTIONS": {
        "context_processors": [
            "django.template.context_processors.debug",
            "django.template.context_processors.request",
            "django.contrib.auth.context_processors.auth",
            "django.contrib.messages.context_processors.messages",
        ],
    },
}]

WSGI_APPLICATION = "config.wsgi.application"

DATABASES = {
    "default": {
        "ENGINE": APP_CONFIG.get("database", {}).get("engine", "django.db.backends.sqlite3"),
        "NAME": BASE_DIR / APP_CONFIG.get("database", {}).get("name", "databases/db.sqlite3"),
    }
}

AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator"},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.NumericPasswordValidator"},
]

LANGUAGE_CODE = "en-us"
TIME_ZONE = "Africa/Lagos"
USE_I18N = True
USE_TZ = True

STATIC_URL = "/static/"
STATIC_ROOT = BASE_DIR / "staticfiles"
STATICFILES_DIRS = [BASE_DIR / "Main"]

MEDIA_URL = "/media/"
MEDIA_ROOT = BASE_DIR / "Main" / "media"

DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

if ENVIRONMENT == "production":
    SECURE_BROWSER_XSS_FILTER = True
    SECURE_CONTENT_TYPE_NOSNIFF = True
''',

    # --------------------------------------------------------
    # config/urls.py
    # --------------------------------------------------------
    "config/urls.py": '''from django.contrib import admin
from django.urls import path
from django.shortcuts import render
from django.conf import settings
from django.conf.urls.static import static

def home(request):
    return render(request, "index.html", {
        "domain": "nodexplatform.com.ng",
        "message": "Welcome to NodexPlatform Core Business Website"
    })

urlpatterns = [
    path("", home, name="home"),
    path("admin/", admin.site.urls),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)
''',

    # --------------------------------------------------------
    # config/wsgi.py
    # --------------------------------------------------------
    "config/wsgi.py": '''import os
from django.core.wsgi import get_wsgi_application
os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
application = get_wsgi_application()
''',

    # --------------------------------------------------------
    # config/asgi.py
    # --------------------------------------------------------
    "config/asgi.py": '''import os
from django.core.asgi import get_asgi_application
os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
application = get_asgi_application()
''',

    # --------------------------------------------------------
    # apps/__init__.py
    # --------------------------------------------------------
    "apps/__init__.py": "",
}

print("Creating FBC core files...")
for relative_path, content in FILES.items():
    file_path = PROJECT_ROOT / relative_path
    if file_path.exists():
        print(f"  ↻ Already exists: {relative_path}")
        continue
    try:
        file_path.write_text(content, encoding="utf-8")
        print(f"  ✓ Created: {relative_path}")
    except OSError as error:
        print(f"  ✗ Failed: {relative_path}\n    {error}")
        sys.exit(1)

print("\n✓ Core files created.\n")

# ============================================================
# 3. REQUIREMENTS
# ============================================================
REQUIREMENTS_PATH = PROJECT_ROOT / "requirements.txt"
if not REQUIREMENTS_PATH.exists():
    REQUIREMENTS_PATH.write_text("Django>=4.2,<6.0\n", encoding="utf-8")
    print("✓ Created requirements.txt\n")

# ============================================================
# 4. CHECK DJANGO
# ============================================================
print("=" * 60)
print("Checking Django installation...")
print("=" * 60)
try:
    import django
    print(f"✓ Django detected: {django.get_version()}\n")
except ImportError:
    print("⚠ Django is not currently installed.")
    answer = input("Install Django now? [Y/n]: ").strip().lower()
    if answer in ("", "y", "yes"):
        print("\nInstalling Django...")
        result = subprocess.run(
            [sys.executable, "-m", "pip", "install", "-r", str(REQUIREMENTS_PATH)],
            check=False
        )
        if result.returncode != 0:
            print("\n❌ Django installation failed.")
            sys.exit(result.returncode)
        print("✓ Django installation completed.\n")

# ============================================================
# 5. FINAL VALIDATION
# ============================================================
print("=" * 60)
print("Validating NodexPlatform FBC v5 Structure...")
print("=" * 60)

REQUIRED_FILES = [
    "main.py",
    "manage.py",
    "config/config.json",
    "config/settings.py",
    "config/urls.py",
    "config/wsgi.py",
    "config/asgi.py",
]

missing = []
for relative_path in REQUIRED_FILES:
    path = PROJECT_ROOT / relative_path
    if path.exists():
        print(f"  ✓ {relative_path}")
    else:
        print(f"  ✗ {relative_path}")
        missing.append(relative_path)

# Verify Main/ exists and is EMPTY
main_dir = PROJECT_ROOT / "Main"
if main_dir.exists():
    contents = list(main_dir.iterdir())
    if len(contents) == 0:
        print(f"  ✓ Main/ (empty, ready for your code)")
    else:
        print(f"  ⚠ Main/ exists but is not empty: {[f.name for f in contents]}")
else:
    print(f"  ✗ Main/")
    missing.append("Main/")

print()
if missing:
    print("❌ Setup completed with missing files.")
    sys.exit(1)

print("=" * 60)
print("       NodexPlatform Setup Complete! (FBC v5)")
print("=" * 60)
print(f"""
Project Root    : {PROJECT_ROOT}
Database Folder : {PROJECT_ROOT / 'databases'} (Exclusive)
Config Folder   : {PROJECT_ROOT / 'config'}
Main Folder     : {PROJECT_ROOT / 'Main'} (EMPTY - Ready for your HTML/CSS/JS)

📂 Your folder structure:
   nodexplatform/
   ├── main.py
   ├── manage.py
   ├── requirements.txt
   ├── config/           ← All configs here
   ├── databases/        ← Database files only
   ├── apps/             ← Django apps
   ├── Php_public/       ← Tenant PHP sites
   ├── Python_public/    ← Tenant Python sites
   ├── Nodexjs/          ← Tenant Node.js sites
   ├── Cgi_bin/          ← Tenant CGI scripts
   ├── logs/
   └── Main/             ← EMPTY (Put your HTML, CSS, JS here)

🚀 Next steps:
   1. Put your index.html inside the Main/ folder
   2. Run: python main.py migrate
   3. Run: python main.py
   4. Open: http://127.0.0.1:8000/

✅ Setup finished successfully with zero conflicts.
""")