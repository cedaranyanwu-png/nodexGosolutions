"""Django settings for NodexPlatform.

Debug remains enabled by default for local development, as requested.  Set
``debug`` to false in ``config/config.json`` for a production deployment.
"""

import json
import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
CONFIG_FILE = BASE_DIR / "config" / "config.json"

with CONFIG_FILE.open("r", encoding="utf-8") as config_file:
    APP_CONFIG = json.load(config_file)

SECRET_KEY = os.environ.get("DJANGO_SECRET_KEY", "django-insecure-local-dev-key")
DEBUG = APP_CONFIG.get("debug", True)
ENVIRONMENT = APP_CONFIG.get("environment", "development")
ALLOWED_HOSTS = APP_CONFIG.get("allowed_hosts", ["localhost", "127.0.0.1"])
if DEBUG:
    for local_host in ("testserver", ".manus.computer"):
        if local_host not in ALLOWED_HOSTS:
            ALLOWED_HOSTS.append(local_host)

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

DATABASE_CONFIG = APP_CONFIG.get("database", {})
DATABASES = {
    "default": {
        "ENGINE": DATABASE_CONFIG.get("engine", "django.db.backends.sqlite3"),
        "NAME": BASE_DIR / DATABASE_CONFIG.get("name", "databases/db.sqlite3"),
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
STATICFILES_DIRS = [BASE_DIR / "Main" / "static"]
MEDIA_URL = "/media/"
MEDIA_ROOT = BASE_DIR / "Main" / "media"
DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

if ENVIRONMENT == "production":
    SECURE_BROWSER_XSS_FILTER = True
    SECURE_CONTENT_TYPE_NOSNIFF = True
