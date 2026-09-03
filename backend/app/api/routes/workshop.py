"""Workshop endpoints."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.workshop import Workshop
from app.models.user import User
from app.core.security import require_roles, get_current_user
from app.schemas.workshop import WorkshopOut
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_workshops(db: Session = Depends(get_db), status_filter: Optional[str] = "active"):
    q = db.query(Workshop)
    if status_filter:
        q = q.filter(Workshop.status == status_filter)
    return ok([WorkshopOut.from_orm_flex(w).model_dump(mode="json") for w in q.all()])


@router.get("/mine")
def my_workshop(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    w = db.query(Workshop).filter(Workshop.user_id == user.user_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="No workshop")
    return ok(WorkshopOut.from_orm_flex(w).model_dump(mode="json"))


@router.get("/{workshop_id}")
def get_workshop(workshop_id: int, db: Session = Depends(get_db)):
    w = db.query(Workshop).filter(Workshop.workshop_id == workshop_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="Workshop not found")
    return ok(WorkshopOut.from_orm_flex(w).model_dump(mode="json"))


@router.put("/{workshop_id}/approve")
def approve_workshop(workshop_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    w = db.query(Workshop).filter(Workshop.workshop_id == workshop_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="Workshop not found")
    w.status = "active"
    owner = db.query(User).filter(User.user_id == w.user_id).first()
    if owner:
        owner.status = "active"
    db.commit()
    return ok(WorkshopOut.model_validate(w).model_dump(mode="json"))


@router.put("/{workshop_id}/reject")
def reject_workshop(workshop_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    w = db.query(Workshop).filter(Workshop.workshop_id == workshop_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="Workshop not found")
    w.status = "inactive"
    owner = db.query(User).filter(User.user_id == w.user_id).first()
    if owner:
        owner.status = "rejected"
    db.commit()
    return ok(WorkshopOut.model_validate(w).model_dump(mode="json"))


@router.put("/mine")
def update_my_workshop(payload: dict, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    w = db.query(Workshop).filter(Workshop.user_id == user.user_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="No workshop")
    for k in ("workshop_name", "description", "location", "contact", "services", "logo", "opening_time", "closing_time"):
        if k in payload:
            setattr(w, k, payload[k])
    db.commit()
    return ok(WorkshopOut.model_validate(w).model_dump(mode="json"))
