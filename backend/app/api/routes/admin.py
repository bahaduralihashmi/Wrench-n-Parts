"""Admin-only endpoints: user management, stats, moderation."""
from typing import Optional
from decimal import Decimal
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.database.connection import get_db
from app.models.user import User
from app.models.product import Product
from app.models.order import Order, OrderItem
from app.models.shop import Shop
from app.models.workshop import Workshop
from app.models.category import Category
from app.models.appointment import Appointment
from app.core.security import require_roles
from app.schemas.user import UserPublic
from app.schemas.response import ok

router = APIRouter()


@router.get("/dashboard")
def dashboard(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    total_users = db.query(User).count()
    total_customers = db.query(User).filter(User.role == "customer").count()
    total_shopkeepers = db.query(User).filter(User.role == "shopkeeper").count()
    total_workshops = db.query(User).filter(User.role == "workshop").count()
    pending_shops = db.query(Shop).filter(Shop.status == "pending").count()
    pending_workshops = db.query(Workshop).filter(Workshop.status == "pending").count()
    total_products = db.query(Product).count()
    total_orders = db.query(Order).count()
    revenue = db.query(func.coalesce(func.sum(Order.total_amount), 0)).filter(Order.payment_status == "paid").scalar()
    pending_orders = db.query(Order).filter(Order.order_status == "pending").count()
    total_categories = db.query(Category).count()
    total_appointments = db.query(Appointment).count()
    return ok({
        "total_users": total_users,
        "total_customers": total_customers,
        "total_shopkeepers": total_shopkeepers,
        "total_workshops": total_workshops,
        "pending_shops": pending_shops,
        "pending_workshops": pending_workshops,
        "total_products": total_products,
        "total_orders": total_orders,
        "pending_orders": pending_orders,
        "revenue": str(revenue),
        "total_categories": total_categories,
        "total_appointments": total_appointments,
    })


@router.get("/users")
def list_users(
    db: Session = Depends(get_db),
    _u: User = Depends(require_roles("admin", "management")),
    role: Optional[str] = None,
    status_filter: Optional[str] = Query(None, alias="status"),
    limit: int = 200,
):
    q = db.query(User)
    if role:
        q = q.filter(User.role == role)
    if status_filter:
        q = q.filter(User.status == status_filter)
    q = q.order_by(User.created_at.desc()).limit(limit)
    return ok([UserPublic.model_validate(u).model_dump(mode="json") for u in q.all()])


@router.put("/users/{user_id}/status")
def set_user_status(user_id: int, payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    u = db.query(User).filter(User.user_id == user_id).first()
    if not u:
        raise HTTPException(status_code=404, detail="User not found")
    new_status = payload.get("status")
    if new_status not in ("active", "inactive", "banned", "approved", "rejected", "pending"):
        raise HTTPException(status_code=400, detail="Invalid status")
    u.status = new_status
    db.commit()
    return ok(UserPublic.model_validate(u).model_dump(mode="json"))


@router.delete("/users/{user_id}")
def delete_user(user_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin"))):
    u = db.query(User).filter(User.user_id == user_id).first()
    if not u:
        raise HTTPException(status_code=404, detail="User not found")
    if u.role == "admin":
        raise HTTPException(status_code=400, detail="Cannot delete admin")
    db.delete(u)
    db.commit()
    return ok({"deleted": user_id})


@router.get("/categories-stats")
def categories_stats(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    rows = (
        db.query(Category.category_name, func.count(Product.product_id).label("count"))
        .outerjoin(Product, Product.category_id == Category.category_id)
        .group_by(Category.category_id)
        .all()
    )
    return ok([{"category": r[0], "count": r[1]} for r in rows])


@router.get("/shop-profits")
def shop_profits(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    rows = (
        db.query(Shop.shop_name, func.coalesce(func.sum(OrderItem.price * OrderItem.quantity), 0).label("revenue"))
        .join(Product, Product.shop_id == Shop.shop_id)
        .join(OrderItem, OrderItem.product_id == Product.product_id)
        .join(Order, Order.order_id == OrderItem.order_id)
        .filter(Order.payment_status == "paid")
        .group_by(Shop.shop_id)
        .all()
    )
    return ok([{"shop": r[0], "revenue": str(r[1])} for r in rows])


@router.get("/hot-deals")
def admin_hot_deals(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    products = (
        db.query(Product, func.count(OrderItem.item_id).label("sold"))
        .outerjoin(OrderItem, OrderItem.product_id == Product.product_id)
        .group_by(Product.product_id)
        .order_by(func.count(OrderItem.item_id).desc())
        .limit(10)
        .all()
    )
    out = []
    for p, sold in products:
        out.append({
            "product_id": p.product_id,
            "product_name": p.product_name,
            "price": str(p.price),
            "discount_price": str(p.discount_price) if p.discount_price else None,
            "product_image": p.product_image,
            "sold": sold,
        })
    return ok(out)
