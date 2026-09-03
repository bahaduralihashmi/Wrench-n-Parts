"""Shop and shopkeeper management routes."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.shop import Shop
from app.models.user import User
from app.core.security import require_roles, get_current_user
from app.schemas.workshop import ShopOut
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_shops(db: Session = Depends(get_db), status_filter: Optional[str] = "active"):
    q = db.query(Shop)
    if status_filter:
        q = q.filter(Shop.status == status_filter)
    return ok([ShopOut.model_validate(s).model_dump(mode="json") for s in q.all()])


@router.get("/mine")
def my_shop(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    s = db.query(Shop).filter(Shop.user_id == user.user_id).first()
    if not s:
        raise HTTPException(status_code=404, detail="No shop")
    return ok(ShopOut.model_validate(s).model_dump(mode="json"))


@router.get("/{shop_id}")
def get_shop(shop_id: int, db: Session = Depends(get_db)):
    s = db.query(Shop).filter(Shop.shop_id == shop_id).first()
    if not s:
        raise HTTPException(status_code=404, detail="Shop not found")
    return ok(ShopOut.model_validate(s).model_dump(mode="json"))


@router.put("/{shop_id}/approve")
def approve_shop(shop_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    s = db.query(Shop).filter(Shop.shop_id == shop_id).first()
    if not s:
        raise HTTPException(status_code=404, detail="Shop not found")
    s.status = "active"
    # also activate owner
    owner = db.query(User).filter(User.user_id == s.user_id).first()
    if owner:
        owner.status = "active"
    db.commit()
    return ok(ShopOut.model_validate(s).model_dump(mode="json"))


@router.put("/{shop_id}/reject")
def reject_shop(shop_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    s = db.query(Shop).filter(Shop.shop_id == shop_id).first()
    if not s:
        raise HTTPException(status_code=404, detail="Shop not found")
    s.status = "inactive"
    owner = db.query(User).filter(User.user_id == s.user_id).first()
    if owner:
        owner.status = "rejected"
    db.commit()
    return ok(ShopOut.model_validate(s).model_dump(mode="json"))


@router.put("/mine")
def update_my_shop(payload: dict, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    s = db.query(Shop).filter(Shop.user_id == user.user_id).first()
    if not s:
        raise HTTPException(status_code=404, detail="No shop")
    for k in ("shop_name", "description", "location", "contact", "logo"):
        if k in payload:
            setattr(s, k, payload[k])
    db.commit()
    return ok(ShopOut.model_validate(s).model_dump(mode="json"))
