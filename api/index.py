"""Vercel serverless entry point. Exposes the FastAPI ASGI application."""

import sys
from pathlib import Path

# Project root
ROOT = Path(__file__).resolve().parent.parent

# Ensure backend/ is on sys.path
BACKEND = ROOT / "backend"

if str(BACKEND) not in sys.path:
    sys.path.insert(0, str(BACKEND))

from app.main import app  # noqa: E402

__all__ = ["app"]
