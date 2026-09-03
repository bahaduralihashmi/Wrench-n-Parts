"""Wishlist endpoints."""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.cart import Wishlist
from app.models.product import Product
from app.models.user import User
from app.core.security import get_current_user
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_wishlist(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = db.query(Wishlist).filter(Wishlist.user_id == user.user_id).all()
    result = []
    for w in items:
        if not w.product_id:
            continue
        p = db.query(Product).filter(Product.product_id == w.product_id).first()
        if not p:
            continue
        result.append({
            "wishlist_id": w.wishlist_id,
            "product_id": p.product_id,
            "product_name": p.product_name,
            "price": str(p.price),
            "discount_price": str(p.discount_price) if p.discount_price else None,
            "product_image": p.product_image,
            "brand": p.brand,
        })
    return ok({"items": result, "count": len(result)})


@router.get("/ids")
def wishlist_ids(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    items = db.query(Wishlist.product_id).filter(Wishlist.user_id == user.user_id).all()
    return ok([i[0] for i in items])


@router.post("/{product_id}")
def add_to_wishlist(product_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if not db.query(Product).filter(Product.product_id == product_id).first():
        raise HTTPException(status_code=404, detail="Product not found")
    existing = db.query(Wishlist).filter(Wishlist.user_id == user.user_id, Wishlist.product_id == product_id).first()
    if existing:
        return ok({"already": True, "product_id": product_id})
    db.add(Wishlist(user_id=user.user_id, product_id=product_id))
    db.commit()
    return ok({"added": True, "product_id": product_id})


@router.delete("/{product_id}")
def remove_from_wishlist(product_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    w = db.query(Wishlist).filter(Wishlist.user_id == user.user_id, Wishlist.product_id == product_id).first()
    if not w:
        raise HTTPException(status_code=404, detail="Not in wishlist")
    db.delete(w)
    db.commit()
    return ok({"removed": product_id})
