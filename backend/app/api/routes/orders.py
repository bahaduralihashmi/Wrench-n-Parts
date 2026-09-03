"""Order endpoints — customer history, shopkeeper/admin management."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import or_

from app.database.connection import get_db
from app.models.order import Order, OrderItem
from app.models.product import Product
from app.models.shop import Shop
from app.models.user import User
from app.core.security import get_current_user, require_roles
from app.schemas.order import OrderOut, OrderItemOut, OrderStatusUpdate
from app.schemas.response import ok

router = APIRouter()


def _serialize_order(o: Order, db: Session) -> dict:
    items = db.query(OrderItem).filter(OrderItem.order_id == o.order_id).all()
    out = OrderOut.model_validate(o).model_dump(mode="json")
    out["items"] = []
    for it in items:
        d = OrderItemOut.model_validate(it).model_dump(mode="json")
        prod = db.query(Product).filter(Product.product_id == it.product_id).first()
        if prod:
            d["product_name"] = prod.product_name
            d["product_image"] = prod.product_image
        out["items"].append(d)
    if o.customer:
        out["customer_name"] = o.customer.name
        out["customer_email"] = o.customer.email
    return out


@router.get("")
def list_orders(
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
    status_filter: Optional[str] = Query(None, alias="status"),
    limit: int = 100,
    offset: int = 0,
):
    q = db.query(Order)
    if user.role == "customer":
        q = q.filter(Order.customer_id == user.user_id)
    elif user.role == "shopkeeper":
        shop = db.query(Shop).filter(Shop.user_id == user.user_id).first()
        if not shop:
            return ok([])
        # orders containing products from this shop
        product_ids = [p.product_id for p in db.query(Product.product_id).filter(Product.shop_id == shop.shop_id).all()]
        order_ids = [r[0] for r in db.query(OrderItem.order_id).filter(OrderItem.product_id.in_(product_ids)).distinct().all()]
        if not order_ids:
            return ok([])
        q = q.filter(Order.order_id.in_(order_ids))
    elif user.role == "workshop":
        return ok([])
    # admin / management see all
    if status_filter:
        q = q.filter(Order.order_status == status_filter)
    q = q.order_by(Order.created_at.desc()).offset(offset).limit(limit)
    return ok([_serialize_order(o, db) for o in q.all()])


@router.get("/{order_id}")
def get_order(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    o = db.query(Order).filter(Order.order_id == order_id).first()
    if not o:
        raise HTTPException(status_code=404, detail="Order not found")
    if user.role == "customer" and o.customer_id != user.user_id:
        raise HTTPException(status_code=403, detail="Forbidden")
    return ok(_serialize_order(o, db))


@router.put("/{order_id}")
def update_order(order_id: int, payload: OrderStatusUpdate, db: Session = Depends(get_db), _user: User = Depends(require_roles("admin", "management", "shopkeeper"))):
    o = db.query(Order).filter(Order.order_id == order_id).first()
    if not o:
        raise HTTPException(status_code=404, detail="Order not found")
    if payload.order_status:
        o.order_status = payload.order_status
    if payload.payment_status:
        o.payment_status = payload.payment_status
    db.commit()
    return ok(_serialize_order(o, db))


@router.get("/{order_id}/items")
def get_order_items(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    o = db.query(Order).filter(Order.order_id == order_id).first()
    if not o:
        raise HTTPException(status_code=404, detail="Order not found")
    if user.role == "customer" and o.customer_id != user.user_id:
        raise HTTPException(status_code=403, detail="Forbidden")
    items = db.query(OrderItem).filter(OrderItem.order_id == order_id).all()
    out = []
    for it in items:
        d = OrderItemOut.model_validate(it).model_dump(mode="json")
        prod = db.query(Product).filter(Product.product_id == it.product_id).first()
        if prod:
            d["product_name"] = prod.product_name
            d["product_image"] = prod.product_image
        out.append(d)
    return ok(out)
