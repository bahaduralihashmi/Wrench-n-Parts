"""Reviews for products and workshops."""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.chatbot import Review
from app.models.product import Product
from app.models.workshop import Workshop
from app.models.user import User
from app.core.security import get_current_user, require_roles
from app.schemas.workshop import ReviewIn
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_reviews(
    db: Session = Depends(get_db),
    product_id: int = Query(None),
    workshop_id: int = Query(None),
):
    q = db.query(Review)
    if product_id:
        q = q.filter(Review.product_id == product_id)
    if workshop_id:
        q = q.filter(Review.workshop_id == workshop_id)
    out = []
    for r in q.order_by(Review.created_at.desc()).all():
        out.append({
            "review_id": r.review_id,
            "user_id": r.user_id,
            "product_id": r.product_id,
            "workshop_id": r.workshop_id,
            "rating": r.rating,
            "comment": r.comment,
            "created_at": str(r.created_at) if r.created_at else None,
            "user_name": r.user.name if r.user else None,
        })
    return ok(out)


@router.post("")
def create_review(payload: ReviewIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if not payload.product_id and not payload.workshop_id:
        raise HTTPException(status_code=400, detail="Provide product_id or workshop_id")
    if payload.product_id:
        if not db.query(Product).filter(Product.product_id == payload.product_id).first():
            raise HTTPException(status_code=404, detail="Product not found")
    if payload.workshop_id:
        if not db.query(Workshop).filter(Workshop.workshop_id == payload.workshop_id).first():
            raise HTTPException(status_code=404, detail="Workshop not found")
    r = Review(
        user_id=user.user_id,
        product_id=payload.product_id,
        workshop_id=payload.workshop_id,
        rating=payload.rating,
        comment=payload.comment,
    )
    db.add(r)
    # Update workshop aggregate
    if payload.workshop_id:
        ws = db.query(Workshop).filter(Workshop.workshop_id == payload.workshop_id).first()
        if ws:
            total_reviews = (ws.total_reviews or 0) + 1
            new_avg = ((float(ws.rating or 0)) * (ws.total_reviews or 0) + payload.rating) / total_reviews
            ws.total_reviews = total_reviews
            ws.rating = round(new_avg, 2)
    db.commit()
    return ok({"review_id": r.review_id})


@router.delete("/{review_id}")
def delete_review(review_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    r = db.query(Review).filter(Review.review_id == review_id).first()
    if not r:
        raise HTTPException(status_code=404, detail="Review not found")
    if r.user_id != user.user_id and user.role not in ("admin", "management"):
        raise HTTPException(status_code=403, detail="Forbidden")
    db.delete(r)
    db.commit()
    return ok({"deleted": review_id})
