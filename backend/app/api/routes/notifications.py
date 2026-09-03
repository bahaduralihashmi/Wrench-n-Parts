"""Notifications for the current user."""
from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.notification import Notification
from app.models.user import User
from app.core.security import get_current_user
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_notifications(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = (
        db.query(Notification)
        .filter(Notification.user_id == user.user_id)
        .order_by(Notification.created_at.desc())
        .limit(100)
        .all()
    )
    return ok([{
        "notification_id": n.notification_id,
        "title": n.title,
        "message": n.message,
        "is_read": n.is_read,
        "link": n.link,
        "created_at": str(n.created_at) if n.created_at else None,
    } for n in items])


@router.get("/count")
def unread_count(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    n = db.query(Notification).filter(Notification.user_id == user.user_id, Notification.is_read == 0).count()
    return ok({"count": n})


@router.put("/{notification_id}/read")
def mark_read(notification_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    n = db.query(Notification).filter(Notification.notification_id == notification_id, Notification.user_id == user.user_id).first()
    if n:
        n.is_read = 1
        db.commit()
    return ok({"notification_id": notification_id, "is_read": 1})


@router.put("/read-all")
def mark_all_read(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    db.query(Notification).filter(Notification.user_id == user.user_id, Notification.is_read == 0).update({"is_read": 1})
    db.commit()
    return ok({"ok": True})
