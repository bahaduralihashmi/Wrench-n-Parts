"""File upload endpoint with type/size validation."""
from pathlib import Path
from fastapi import APIRouter, Depends, HTTPException, UploadFile, File
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.services.storage import get_storage
from app.schemas.response import ok

router = APIRouter()

ALLOWED_IMAGE_TYPES = {"image/jpeg", "image/png", "image/webp", "image/gif"}
MAX_BYTES = 5 * 1024 * 1024  # 5MB


@router.post("/image")
def upload_image(file: UploadFile = File(...)):
    if file.content_type not in ALLOWED_IMAGE_TYPES:
        raise HTTPException(status_code=400, detail="Unsupported image type")
    data = file.file.read()
    if len(data) > MAX_BYTES:
        raise HTTPException(status_code=400, detail="File too large (max 5MB)")
    if not data:
        raise HTTPException(status_code=400, detail="Empty file")
    storage = get_storage()
    url = storage.upload(__import__("io").BytesIO(data), file.filename or "image.jpg", file.content_type)
    return ok({"url": url, "filename": file.filename, "size": len(data), "content_type": file.content_type})


@router.post("/file")
def upload_file(file: UploadFile = File(...)):
    data = file.file.read()
    if len(data) > MAX_BYTES:
        raise HTTPException(status_code=400, detail="File too large (max 5MB)")
    if not data:
        raise HTTPException(status_code=400, detail="Empty file")
    storage = get_storage()
    url = storage.upload(__import__("io").BytesIO(data), file.filename or "file.bin", file.content_type or "application/octet-stream")
    return ok({"url": url, "filename": file.filename, "size": len(data)})
