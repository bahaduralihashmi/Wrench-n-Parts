"""Authentication, password hashing, JWT helpers, and FastAPI dependencies."""
from datetime import datetime, timedelta, timezone
from typing import Optional, List

import bcrypt
import jwt
from fastapi import Depends, HTTPException, Request, status, Cookie
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.database.connection import get_db
from app.models.user import User

_settings = get_settings()


# ----- Password hashing (bcrypt, drop-in compatible with PHP password_hash) -----
def _normalize(plain: str) -> bytes:
    # bcrypt accepts max 72 bytes; truncate to match prior behaviour.
    return plain.encode("utf-8")[:72]


def hash_password(plain: str) -> str:
    return bcrypt.hashpw(_normalize(plain), bcrypt.gensalt(rounds=12)).decode("utf-8")


def verify_password(plain: str, hashed: str) -> bool:
    try:
        return bcrypt.checkpw(_normalize(plain), hashed.encode("utf-8"))
    except Exception:
        return False


# ----- JWT -----
def create_access_token(user_id: int, role: str) -> str:
    payload = {
        "sub": str(user_id),
        "role": role,
        "iat": datetime.now(timezone.utc),
        "exp": datetime.now(timezone.utc) + timedelta(days=_settings.jwt_expire_days),
    }
    return jwt.encode(payload, _settings.secret_key, algorithm=_settings.jwt_algorithm)


def decode_token(token: str) -> Optional[dict]:
    try:
        return jwt.decode(token, _settings.secret_key, algorithms=[_settings.jwt_algorithm])
    except jwt.PyJWTError:
        return None


COOKIE_NAME = "wnp_session"


def set_auth_cookie(response, token: str):
    response.set_cookie(
        key=COOKIE_NAME,
        value=token,
        max_age=_settings.jwt_expire_days * 24 * 3600,
        httponly=True,
        secure=_settings.environment != "development",
        samesite="lax",
        path="/",
    )


def clear_auth_cookie(response):
    response.delete_cookie(COOKIE_NAME, path="/")


# ----- Auth dependencies -----
def get_current_user(
    db: Session = Depends(get_db),
    token_cookie: Optional[str] = Cookie(default=None, alias=COOKIE_NAME),
    authorization: Optional[str] = None,
) -> User:
    token = token_cookie
    if not token and authorization:
        # Also accept "Authorization: Bearer <token>" for programmatic clients
        if authorization.lower().startswith("bearer "):
            token = authorization.split(" ", 1)[1]
    if not token:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Not authenticated")
    payload = decode_token(token)
    if not payload:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid or expired token")
    try:
        user_id = int(payload["sub"])
    except (KeyError, ValueError):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token")
    user = db.query(User).filter(User.user_id == user_id).first()
    if not user:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="User not found")
    return user


def get_current_user_optional(
    db: Session = Depends(get_db),
    token_cookie: Optional[str] = Cookie(default=None, alias=COOKIE_NAME),
) -> Optional[User]:
    if not token_cookie:
        return None
    payload = decode_token(token_cookie)
    if not payload:
        return None
    try:
        user_id = int(payload["sub"])
    except (KeyError, ValueError):
        return None
    return db.query(User).filter(User.user_id == user_id).first()


def require_roles(*roles: str):
    allowed = set(roles)

    def _checker(user: User = Depends(get_current_user)) -> User:
        if user.role not in allowed:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Insufficient permissions")
        return user

    return _checker
