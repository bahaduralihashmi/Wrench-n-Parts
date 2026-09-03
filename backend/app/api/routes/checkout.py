"""Checkout: convert the user's cart into an order, atomically."""
from decimal import Decimal
from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.cart import Cart
from app.models.order import Order, OrderItem
from app.models.product import Product
from app.models.user import User
from app.models.chatbot import Notification
from app.core.security import get_current_user
from app.schemas.order import CheckoutIn
from app.schemas.response import ok

router = APIRouter()


@router.post("")
def checkout(payload: CheckoutIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = db.query(Cart).filter(Cart.user_id == user.user_id).all()
    if not items:
        raise HTTPException(status_code=400, detail="Cart is empty")

    # Validate stock
    for c in items:
        if not c.product:
            raise HTTPException(status_code=400, detail="Cart contains unavailable product")
        if c.product.status != "available":
            raise HTTPException(status_code=400, detail=f"Product '{c.product.product_name}' is not available")
        if c.quantity > c.product.stock:
            raise HTTPException(status_code=400, detail=f"Insufficient stock for '{c.product.product_name}'")

    total = Decimal("0.00")
    for c in items:
        unit = c.product.discount_price or c.product.price
        total += Decimal(unit) * c.quantity

    order = Order(
        customer_id=user.user_id,
        total_amount=total,
        shipping_address=payload.shipping_address,
        contact_phone=payload.contact_phone,
        payment_method=payload.payment_method,
        payment_status="pending",
        order_status="pending",
        notes=payload.notes,
    )
    db.add(order)
    db.flush()

    for c in items:
        unit = c.product.discount_price or c.product.price
        db.add(OrderItem(
            order_id=order.order_id,
            product_id=c.product_id,
            quantity=c.quantity,
            price=unit,
        ))
        c.product.stock = max(0, (c.product.stock or 0) - c.quantity)

    # Clear cart
    db.query(Cart).filter(Cart.user_id == user.user_id).delete()

    # Customer notification
    db.add(Notification(
        user_id=user.user_id,
        title="Order placed",
        message=f"Your order #{order.order_id} has been placed. Total: Rs. {total}",
        link=f"/customer/orders.html?id={order.order_id}",
    ))

    db.commit()
    db.refresh(order)
    return ok({"order_id": order.order_id, "total_amount": str(total), "order_status": order.order_status})
