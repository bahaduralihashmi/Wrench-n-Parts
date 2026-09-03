"""User-to-user chat (customer <-> shopkeeper/workshop)."""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import or_, and_
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.chat import ChatMessage
from app.models.user import User
from app.core.security import get_current_user
from app.schemas.chat import ChatSend
from app.schemas.response import ok

router = APIRouter()


@router.get("/with/{other_id}")
def conversation(other_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    rows = (
        db.query(ChatMessage)
        .filter(or_(
            and_(ChatMessage.sender_id == user.user_id, ChatMessage.receiver_id == other_id),
            and_(ChatMessage.sender_id == other_id, ChatMessage.receiver_id == user.user_id),
        ))
        .order_by(ChatMessage.created_at.asc())
        .all()
    )
    return ok([{
        "message_id": m.message_id,
        "sender_id": m.sender_id,
        "receiver_id": m.receiver_id,
        "message": m.message,
        "is_read": m.is_read,
        "created_at": str(m.created_at) if m.created_at else None,
    } for m in rows])


@router.post("")
def send(payload: ChatSend, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if not db.query(User).filter(User.user_id == payload.receiver_id).first():
        raise HTTPException(status_code=404, detail="Recipient not found")
    m = ChatMessage(sender_id=user.user_id, receiver_id=payload.receiver_id, message=payload.message, is_read=0)
    db.add(m)
    db.commit()
    db.refresh(m)
    return ok({"message_id": m.message_id})


@router.get("/threads")
def threads(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    # Distinct conversation partners
    rows = (
        db.query(ChatMessage)
        .filter(or_(ChatMessage.sender_id == user.user_id, ChatMessage.receiver_id == user.user_id))
        .order_by(ChatMessage.created_at.desc())
        .all()
    )
    seen = set()
    out = []
    for m in rows:
        other = m.receiver_id if m.sender_id == user.user_id else m.sender_id
        if other in seen:
            continue
        seen.add(other)
        u = db.query(User).filter(User.user_id == other).first()
        out.append({
            "other_id": other,
            "other_name": u.name if u else None,
            "other_role": u.role if u else None,
            "last_message": m.message,
            "last_at": str(m.created_at) if m.created_at else None,
        })
    return ok(out)


@router.put("/{message_id}/read")
def mark_read(message_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    m = db.query(ChatMessage).filter(ChatMessage.message_id == message_id).first()
    if not m or m.receiver_id != user.user_id:
        raise HTTPException(status_code=404, detail="Message not found")
    m.is_read = 1
    db.commit()
    return ok({"message_id": message_id, "is_read": 1})
