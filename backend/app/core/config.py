from functools import lru_cache
from typing import Optional
import os
from urllib.parse import urlparse
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
        case_sensitive=False,
    )

    site_url: str = "http://localhost:8000"
    frontend_url: str = "http://localhost:8000"
    api_base_url: str = ""

    db_host: Optional[str] = None
    db_port: int = 3306
    db_name: Optional[str] = None
    db_user: Optional[str] = None
    db_password: Optional[str] = None

    mysqlhost: Optional[str] = None
    mysqlport: Optional[int] = None
    mysqldatabase: Optional[str] = None
    mysqluser: Optional[str] = None
    mysqlpassword: Optional[str] = None

    mysql_url: Optional[str] = None
    mysql_public_url: Optional[str] = None
    database_url: Optional[str] = None  # full URL override

    secret_key: str = "change-me-in-production-please-set-a-long-random-secret"
    jwt_algorithm: str = "HS256"
    jwt_expire_days: int = 7

    gemini_api_key: Optional[str] = None
    gemini_model: str = "gemini-1.5-flash"

    storage_provider: str = "local"
    storage_local_dir: str = "backend/uploads"
    storage_public_prefix: str = "/uploads"
    storage_endpoint: Optional[str] = None
    storage_region: str = "auto"
    storage_access_key: Optional[str] = None
    storage_secret_key: Optional[str] = None
    storage_bucket: Optional[str] = None
    storage_public_url: Optional[str] = None

    maintenance_mode: int = 0

    environment: str = "development"

    def _from_url(self, url: str) -> dict:
        """Parse mysql://user:pass@host:port/db into a dict, ignoring SQLAlchemy driver prefix."""
        if not url:
            return {}
        s = url
        for prefix in ("mysql+pymysql://", "mysql://"):
            if s.startswith(prefix):
                s = s[len(prefix):]
                break
        parsed = urlparse("mysql://" + s)
        return {
            "host": parsed.hostname,
            "port": parsed.port or 3306,
            "name": (parsed.path or "/").lstrip("/"),
            "user": parsed.username,
            "password": parsed.password or "",
        }

    def effective_db(self) -> dict:
        # 1) Explicit full URL wins
        if self.database_url and (self.db_host or self.mysqlhost):
            # If user set DATABASE_URL, prefer it over component env vars.
            parsed = urlparse(self.database_url.replace("mysql+pymysql://", "mysql://"))
            return {
                "host": parsed.hostname,
                "port": parsed.port or 3306,
                "name": (parsed.path or "/").lstrip("/"),
                "user": parsed.username,
                "password": parsed.password or "",
            }
        if self.database_url and not (self.db_host or self.mysqlhost):
            return self._from_url(self.database_url)
        # 2) MYSQL_URL / MYSQL_PUBLIC_URL — public wins if both present (for local dev from outside Railway)
        for url in (self.mysql_public_url, self.mysql_url):
            d = self._from_url(url) if url else {}
            if d.get("host"):
                return d
        # 3) Components
        return {
            "host": self.db_host or self.mysqlhost or "localhost",
            "port": int(self.db_port or self.mysqlport or 3306),
            "name": self.db_name or self.mysqldatabase or "wrench_parts_db",
            "user": self.db_user or self.mysqluser or "root",
            "password": self.db_password if self.db_password is not None else (self.mysqlpassword or ""),
        }

    @property
    def resolved_database_url(self) -> str:
        c = self.effective_db()
        pwd = c["password"]
        from urllib.parse import quote_plus
        return (
            f"mysql+pymysql://{c['user']}:{quote_plus(pwd)}@{c['host']}:{c['port']}/{c['name']}"
            f"?charset=utf8mb4"
        )


@lru_cache
def get_settings() -> Settings:
    return Settings()
