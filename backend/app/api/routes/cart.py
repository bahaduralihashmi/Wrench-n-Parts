"""Cart endpoints — add/update/remove/list for the current customer."""
from decimal import Decimal
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.cart import Cart
from app.models.product import Product
from app.models.user import User
from app.core.security import get_current_user
from app.schemas.order import CartItemOut, CartAdd, CartUpdate
from app.schemas.response import ok

router = APIRouter()


def _serialize(c: Cart) -> dict:
    out = CartItemOut.model_validate(c).model_dump(mode="json")
    if c.product:
        out["product_name"] = c.product.product_name
        out["price"] = c.product.price
        out["discount_price"] = c.product.discount_price
        out["product_image"] = c.product.product_image
        out["stock"] = c.product.stock
        out["brand"] = c.product.brand
        unit = c.product.discount_price or c.product.price
        out["subtotal"] = Decimal(unit) * c.quantity
    return out


@router.get("")
def list_cart(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = db.query(Cart).filter(Cart.user_id == user.user_id).all()
    total = Decimal("0.00")
    serialized = []
    for c in items:
        s = _serialize(c)
        if s.get("subtotal"):
            total += Decimal(s["subtotal"])
        serialized.append(s)
    return ok({"items": serialized, "total": str(total), "count": sum(c.quantity for c in items)})


@router.get("/count")
def cart_count(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = db.query(Cart).filter(Cart.user_id == user.user_id).all()
    return ok({"count": sum(c.quantity for c in items)})


@router.post("")
def add_to_cart(payload: CartAdd, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if payload.quantity <= 0:
        raise HTTPException(status_code=400, detail="Invalid quantity")
    product = db.query(Product).filter(Product.product_id == payload.product_id).first()
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    existing = db.query(Cart).filter(Cart.user_id == user.user_id, Cart.product_id == payload.product_id).first()
    if existing:
        existing.quantity += payload.quantity
    else:
        existing = Cart(user_id=user.user_id, product_id=payload.product_id, quantity=payload.quantity)
        db.add(existing)
    db.commit()
    db.refresh(existing)
    return ok(_serialize(existing))


@router.put("/{product_id}")
def update_cart_item(product_id: int, payload: CartUpdate, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    c = db.query(Cart).filter(Cart.user_id == user.user_id, Cart.product_id == product_id).first()
    if not c:
        raise HTTPException(status_code=404, detail="Item not in cart")
    if payload.quantity <= 0:
        db.delete(c)
    else:
        c.quantity = payload.quantity
    db.commit()
    return ok({"product_id": product_id, "quantity": payload.quantity})


@router.delete("/{product_id}")
def remove_from_cart(product_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    c = db.query(Cart).filter(Cart.user_id == user.user_id, Cart.product_id == product_id).first()
    if not c:
        raise HTTPException(status_code=404, detail="Item not in cart")
    db.delete(c)
    db.commit()
    return ok({"removed": product_id})


@router.delete("")
def clear_cart(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    db.query(Cart).filter(Cart.user_id == user.user_id).delete()
    db.commit()
    return ok({"cleared": True})
