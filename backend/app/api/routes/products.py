"""Public product routes — listing, search, filtering, detail."""
from typing import Optional, List
from decimal import Decimal
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import or_, and_
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.product import Product
from app.models.category import Category
from app.models.shop import Shop
from app.models.user import User
from app.core.security import require_roles
from app.schemas.product import ProductOut, ProductCreate, ProductUpdate
from app.schemas.response import ok

router = APIRouter()


def _serialize_product(p: Product) -> dict:
    out = ProductOut.model_validate(p).model_dump(mode="json")
    out["shop_name"] = p.shop.shop_name if p.shop else None
    out["category_name"] = p.category.category_name if p.category else None
    return out


@router.get("")
def list_products(
    db: Session = Depends(get_db),
    q: Optional[str] = None,
    category_id: Optional[int] = None,
    shop_id: Optional[int] = None,
    brand: Optional[str] = None,
    min_price: Optional[float] = None,
    max_price: Optional[float] = None,
    sort: Optional[str] = "newest",
    status_filter: Optional[str] = "available",
    limit: int = Query(50, le=200),
    offset: int = 0,
):
    query = db.query(Product)
    if status_filter:
        query = query.filter(Product.status == status_filter)
    if q:
        like = f"%{q}%"
        query = query.filter(or_(
            Product.product_name.like(like),
            Product.description.like(like),
            Product.brand.like(like),
            Product.car_brand.like(like),
            Product.car_model.like(like),
            Product.compatible_vehicles.like(like),
        ))
    if category_id:
        query = query.filter(Product.category_id == category_id)
    if shop_id:
        query = query.filter(Product.shop_id == shop_id)
    if brand:
        query = query.filter(Product.brand == brand)
    if min_price is not None:
        query = query.filter(Product.price >= min_price)
    if max_price is not None:
        query = query.filter(Product.price <= max_price)
    if sort == "price_asc":
        query = query.order_by(Product.price.asc())
    elif sort == "price_desc":
        query = query.order_by(Product.price.desc())
    elif sort == "name":
        query = query.order_by(Product.product_name.asc())
    else:
        query = query.order_by(Product.created_at.desc())
    items = query.offset(offset).limit(limit).all()
    return ok([_serialize_product(p) for p in items])


@router.get("/hot-deals")
def hot_deals(db: Session = Depends(get_db), limit: int = 12):
    items = (
        db.query(Product)
        .filter(Product.status == "available", Product.discount_price.isnot(None))
        .order_by(Product.created_at.desc())
        .limit(limit)
        .all()
    )
    return ok([_serialize_product(p) for p in items])


@router.get("/{product_id}")
def get_product(product_id: int, db: Session = Depends(get_db)):
    p = db.query(Product).filter(Product.product_id == product_id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")
    return ok(_serialize_product(p))


@router.post("")
def create_product(payload: ProductCreate, db: Session = Depends(get_db), user: User = Depends(require_roles("shopkeeper", "admin", "management"))):
    shop = db.query(Shop).filter(Shop.user_id == user.user_id).first()
    if not shop and user.role != "admin" and user.role != "management":
        raise HTTPException(status_code=400, detail="No shop found for this user")
    p = Product(
        shop_id=shop.shop_id if shop else payload.__dict__.get("shop_id", 0),
        category_id=payload.category_id,
        product_name=payload.product_name,
        description=payload.description,
        price=payload.price,
        discount_price=payload.discount_price,
        stock=payload.stock,
        brand=payload.brand,
        car_brand=payload.car_brand,
        car_model=payload.car_model,
        compatible_vehicles=payload.compatible_vehicles,
        status=payload.status,
    )
    db.add(p)
    db.commit()
    db.refresh(p)
    return ok(_serialize_product(p))


@router.put("/{product_id}")
def update_product(product_id: int, payload: ProductUpdate, db: Session = Depends(get_db), user: User = Depends(require_roles("shopkeeper", "admin", "management"))):
    p = db.query(Product).filter(Product.product_id == product_id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")
    if user.role == "shopkeeper":
        shop = db.query(Shop).filter(Shop.user_id == user.user_id).first()
        if not shop or shop.shop_id != p.shop_id:
            raise HTTPException(status_code=403, detail="Not your product")
    for k, v in payload.model_dump(exclude_unset=True).items():
        setattr(p, k, v)
    db.commit()
    db.refresh(p)
    return ok(_serialize_product(p))


@router.delete("/{product_id}")
def delete_product(product_id: int, db: Session = Depends(get_db), user: User = Depends(require_roles("shopkeeper", "admin", "management"))):
    p = db.query(Product).filter(Product.product_id == product_id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")
    if user.role == "shopkeeper":
        shop = db.query(Shop).filter(Shop.user_id == user.user_id).first()
        if not shop or shop.shop_id != p.shop_id:
            raise HTTPException(status_code=403, detail="Not your product")
    db.delete(p)
    db.commit()
    return ok({"deleted": product_id})
