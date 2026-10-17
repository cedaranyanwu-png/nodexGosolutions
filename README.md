# QuickChat Platform

QuickChat is a next-generation social media, messaging, creator economy, and professional networking platform built with Python (Django, Django REST Framework, Django Channels), WebSockets, WebRTC, and React.js.

## Architecture & Quick Start

### Backend Launcher (`main.py`)
Run the unified entry point:
```bash
python main.py
```
Or run standard Django development commands:
```bash
python manage.py migrate
python manage.py runserver
```

### Running Tests
```bash
python -m pytest
```

### Frontend
Navigate to `frontend/` and run:
```bash
npm install
npm run dev
```
