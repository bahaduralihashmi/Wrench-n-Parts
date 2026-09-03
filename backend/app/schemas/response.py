from typing import Any, Optional
from pydantic import BaseModel


class ApiResponse(BaseModel):
    success: bool
    data: Optional[Any] = None
    error: Optional[str] = None
    message: Optional[str] = None


def ok(data: Any = None, message: Optional[str] = None) -> dict:
    return {"success": True, "data": data, "message": message}


def fail(error: str, status_code: int = 400) -> dict:
    return {"success": False, "error": error}
