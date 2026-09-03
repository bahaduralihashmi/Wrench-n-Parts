from functools import lru_cache
from typing import Optional
import os
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

    def effective_db(self) -> dict:
        return {
            "host": self.db_host or self.mysqlhost or "localhost",
            "port": int(self.db_port or self.mysqlport or 3306),
            "name": self.db_name or self.mysqldatabase or "wrench_parts_db",
            "user": self.db_user or self.mysqluser or "root",
            "password": self.db_password if self.db_password is not None else (self.mysqlpassword or ""),
        }

    @property
    def database_url(self) -> str:
        c = self.effective_db()
        pwd = c["password"]
        # pymysql URL escape for special chars in password
        from urllib.parse import quote_plus
        return (
            f"mysql+pymysql://{c['user']}:{quote_plus(pwd)}@{c['host']}:{c['port']}/{c['name']}"
            f"?charset=utf8mb4"
        )


@lru_cache
def get_settings() -> Settings:
    return Settings()
