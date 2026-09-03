"""Storage abstraction. Local disk for development, pluggable S3-compatible for production."""
from abc import ABC, abstractmethod
from pathlib import Path
from typing import BinaryIO, Optional
import os
import uuid

from app.core.config import get_settings


class StorageService(ABC):
    @abstractmethod
    def upload(self, file_obj: BinaryIO, filename: str, content_type: str = "application/octet-stream") -> str:
        """Return a public URL (or relative URL) for the stored object."""

    @abstractmethod
    def delete(self, url_or_key: str) -> bool:
        pass

    @abstractmethod
    def get_url(self, key: str) -> str:
        pass


class LocalStorage(StorageService):
    def __init__(self, base_dir: str, public_prefix: str):
        self.base_dir = Path(base_dir).resolve()
        self.base_dir.mkdir(parents=True, exist_ok=True)
        self.public_prefix = public_prefix.rstrip("/")

    def _safe_key(self, filename: str) -> str:
        ext = Path(filename).suffix.lower() or ""
        return f"{uuid.uuid4().hex}{ext}"

    def upload(self, file_obj: BinaryIO, filename: str, content_type: str = "application/octet-stream") -> str:
        key = self._safe_key(filename)
        target = self.base_dir / key
        file_obj.seek(0)
        target.write_bytes(file_obj.read())
        return f"{self.public_prefix}/{key}"

    def delete(self, url_or_key: str) -> bool:
        key = url_or_key.split("/")[-1]
        target = self.base_dir / key
        if target.exists():
            target.unlink()
            return True
        return False

    def get_url(self, key: str) -> str:
        return f"{self.public_prefix}/{key}"


class S3Storage(StorageService):
    def __init__(self, endpoint, region, access_key, secret_key, bucket, public_url):
        import boto3
        self.bucket = bucket
        self.public_url = (public_url or "").rstrip("/")
        self.client = boto3.client(
            "s3",
            endpoint_url=endpoint or None,
            region_name=region or "auto",
            aws_access_key_id=access_key,
            aws_secret_access_key=secret_key,
        )

    def upload(self, file_obj: BinaryIO, filename: str, content_type: str = "application/octet-stream") -> str:
        key = f"{uuid.uuid4().hex}{Path(filename).suffix.lower()}"
        file_obj.seek(0)
        self.client.upload_fileobj(
            file_obj, self.bucket, key, ExtraArgs={"ContentType": content_type, "ACL": "public-read"}
        )
        return self.get_url(key)

    def delete(self, url_or_key: str) -> bool:
        key = url_or_key.split("/")[-1]
        try:
            self.client.delete_object(Bucket=self.bucket, Key=key)
            return True
        except Exception:
            return False

    def get_url(self, key: str) -> str:
        if self.public_url:
            return f"{self.public_url}/{key}"
        return f"s3://{self.bucket}/{key}"


_storage: Optional[StorageService] = None


def get_storage() -> StorageService:
    global _storage
    if _storage is not None:
        return _storage
    s = get_settings()
    if s.storage_provider == "s3" and s.storage_bucket and s.storage_access_key and s.storage_secret_key:
        _storage = S3Storage(
            endpoint=s.storage_endpoint,
            region=s.storage_region,
            access_key=s.storage_access_key,
            secret_key=s.storage_secret_key,
            bucket=s.storage_bucket,
            public_url=s.storage_public_url,
        )
    else:
        _storage = LocalStorage(
            base_dir=s.storage_local_dir or "backend/uploads",
            public_prefix=s.storage_public_prefix or "/uploads",
        )
    return _storage
