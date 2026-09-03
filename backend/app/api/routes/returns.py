"""Returns/refund requests."""
from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.order import Order, OrderItem
from app.models.user import User
from app.models.notification import Notification
from app.core.security import get_current_user, require_roles
from app.schemas.response import ok

router = APIRouter()

# Lightweight returns table-less model — store as notifications only (matches original simple impl)


@router.post("")
def request_return(payload: dict, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order_id = payload.get("order_id")
    reason = payload.get("reason", "")
    if not order_id:
        raise HTTPException(status_code=400, detail="order_id required")
    order = db.query(Order).filter(Order.order_id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.customer_id != user.user_id:
        raise HTTPException(status_code=403, detail="Forbidden")
    # Notify all admins/management
    from app.models.user import User as UserModel
    admins = db.query(UserModel).filter(UserModel.role.in_(["admin", "management"])).all()
    for a in admins:
        db.add(Notification(
            user_id=a.user_id,
            title="Return requested",
            message=f"Order #{order_id} return: {reason[:200]}",
            link=f"/admin/orders.html?id={order_id}",
        ))
    db.commit()
    return ok({"submitted": True})


@router.get("")
def list_returns(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    # Returns are surfaced as notifications; expose a thin summary
    notifs = (
        db.query(Notification)
        .filter(Notification.title == "Return requested")
        .order_by(Notification.created_at.desc())
        .limit(100)
        .all()
    )
    return ok([{
        "notification_id": n.notification_id,
        "user_id": n.user_id,
        "title": n.title,
        "message": n.message,
        "link": n.link,
        "created_at": str(n.created_at) if n.created_at else None,
        "is_read": n.is_read,
    } for n in notifs])
