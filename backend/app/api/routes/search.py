"""Unified search across products, shops, workshops, categories, KB."""
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import or_

from app.database.connection import get_db
from app.models.product import Product
from app.models.shop import Shop
from app.models.workshop import Workshop
from app.models.category import Category
from app.models.chatbot import KbArticle
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def search(q: str = Query(..., min_length=1), db: Session = Depends(get_db), limit: int = 20):
    like = f"%{q}%"
    products = (
        db.query(Product)
        .filter(or_(Product.product_name.like(like), Product.description.like(like), Product.brand.like(like)))
        .filter(Product.status == "available")
        .limit(limit)
        .all()
    )
    shops = db.query(Shop).filter(or_(Shop.shop_name.like(like), Shop.description.like(like))).limit(5).all()
    workshops = db.query(Workshop).filter(or_(Workshop.workshop_name.like(like), Workshop.description.like(like), Workshop.location.like(like))).limit(5).all()
    categories = db.query(Category).filter(Category.category_name.like(like)).limit(10).all()
    articles = db.query(KbArticle).filter(or_(KbArticle.title.like(like), KbArticle.content.like(like))).limit(5).all()

    return ok({
        "products": [
            {"product_id": p.product_id, "product_name": p.product_name, "price": str(p.price), "product_image": p.product_image, "brand": p.brand}
            for p in products
        ],
        "shops": [{"shop_id": s.shop_id, "shop_name": s.shop_name} for s in shops],
        "workshops": [{"workshop_id": w.workshop_id, "workshop_name": w.workshop_name, "location": w.location} for w in workshops],
        "categories": [{"category_id": c.category_id, "category_name": c.category_name} for c in categories],
        "articles": [{"id": a.id, "title": a.title} for a in articles],
    })
