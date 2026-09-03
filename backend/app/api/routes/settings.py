"""Public system settings endpoints (site name, currency, maintenance)."""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.settings import SystemSetting
from app.core.security import require_roles
from app.models.user import User
from app.schemas.response import ok

router = APIRouter()


def _all(db: Session) -> dict:
    return {s.setting_key: s.setting_value for s in db.query(SystemSetting).all()}


@router.get("")
def public_settings(db: Session = Depends(get_db)):
    data = _all(db)
    # Public-safe subset
    safe = {
        "site_name": data.get("site_name", "Wrench n Parts"),
        "currency": data.get("currency", "PKR"),
        "tax_rate": data.get("tax_rate", "0"),
        "shipping_fee": data.get("shipping_fee", "0"),
        "chatbot_enabled": data.get("chatbot_enabled", "1"),
        "chatbot_name": data.get("chatbot_name", "MechBot"),
        "maintenance_mode": data.get("maintenance_mode", "0"),
        "site_email": data.get("site_email", ""),
        "site_phone": data.get("site_phone", ""),
        "site_address": data.get("site_address", ""),
    }
    return ok(safe)


@router.get("/all")
def all_settings(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    return ok(_all(db))


@router.put("")
def update_settings(payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    for k, v in payload.items():
        s = db.query(SystemSetting).filter(SystemSetting.setting_key == k).first()
        if s:
            s.setting_value = str(v)
        else:
            db.add(SystemSetting(setting_key=k, setting_value=str(v)))
    db.commit()
    return ok({"updated": list(payload.keys())})
