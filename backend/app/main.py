"""FastAPI application factory and CORS/middleware wiring."""
import logging
import os
from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from fastapi.staticfiles import StaticFiles

from app.core.config import get_settings
from app.api.routes import register_routers

logger = logging.getLogger("wnp")
logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s %(message)s")


def create_app() -> FastAPI:
    settings = get_settings()
    app = FastAPI(
        title="Wrench n Parts API",
        version="1.0.0",
        docs_url="/api/docs",
        redoc_url="/api/redoc",
        openapi_url="/api/openapi.json",
    )

    # CORS — explicit origins for safety
    allowed = []
    if settings.frontend_url:
        allowed.append(settings.frontend_url.rstrip("/"))
    if settings.site_url:
        allowed.append(settings.site_url.rstrip("/"))
    if settings.environment == "development":
        allowed.extend([
            "http://localhost:8000",
            "http://localhost:3000",
            "http://127.0.0.1:8000",
        ])

    app.add_middleware(
        CORSMiddleware,
        allow_origins=allowed or ["http://localhost:8000"],
        allow_credentials=True,
        allow_methods=["GET", "POST", "PUT", "DELETE", "PATCH", "OPTIONS"],
        allow_headers=["*"],
    )

    # Global exception handler — never leak internals
    @app.exception_handler(Exception)
    async def unhandled(request: Request, exc: Exception):
        logger.exception("Unhandled error on %s %s", request.method, request.url.path)
        return JSONResponse(
            status_code=500,
            content={"success": False, "error": "Internal server error"},
        )

    @app.get("/api/health")
    async def health():
        return {"success": True, "data": {"status": "ok"}}

    register_routers(app)

    # Static files — uploads + frontend (mounted after API so API paths win)
    static_dirs = [
        ("/uploads", settings.storage_local_dir),
    ]
    frontend = Path(__file__).resolve().parent.parent.parent / "frontend"
    for mount, d in static_dirs:
        p = Path(d)
        if p.exists():
            app.mount(mount, StaticFiles(directory=str(p)), name=mount.strip("/"))

    if frontend.exists():
        app.mount("/", StaticFiles(directory=str(frontend), html=True), name="frontend")

    return app


app = create_app()
