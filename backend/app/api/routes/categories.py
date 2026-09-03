from typing import Optional
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.category import Category
from app.core.security import require_roles
from app.models.user import User
from app.schemas.product import CategoryCreate, CategoryOut
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def list_categories(db: Session = Depends(get_db)):
    items = db.query(Category).order_by(Category.category_name).all()
    return ok([CategoryOut.model_validate(c).model_dump(mode="json") for c in items])


@router.get("/{category_id}")
def get_category(category_id: int, db: Session = Depends(get_db)):
    c = db.query(Category).filter(Category.category_id == category_id).first()
    if not c:
        raise HTTPException(status_code=404, detail="Category not found")
    return ok(CategoryOut.model_validate(c).model_dump(mode="json"))


@router.post("")
def create_category(payload: CategoryCreate, db: Session = Depends(get_db), _user: User = Depends(require_roles("admin", "management"))):
    c = Category(**payload.model_dump())
    db.add(c)
    db.commit()
    db.refresh(c)
    return ok(CategoryOut.model_validate(c).model_dump(mode="json"))


@router.put("/{category_id}")
def update_category(category_id: int, payload: CategoryCreate, db: Session = Depends(get_db), _user: User = Depends(require_roles("admin", "management"))):
    c = db.query(Category).filter(Category.category_id == category_id).first()
    if not c:
        raise HTTPException(status_code=404, detail="Category not found")
    for k, v in payload.model_dump(exclude_unset=True).items():
        setattr(c, k, v)
    db.commit()
    return ok(CategoryOut.model_validate(c).model_dump(mode="json"))


@router.delete("/{category_id}")
def delete_category(category_id: int, db: Session = Depends(get_db), _user: User = Depends(require_roles("admin", "management"))):
    c = db.query(Category).filter(Category.category_id == category_id).first()
    if not c:
        raise HTTPException(status_code=404, detail="Category not found")
    db.delete(c)
    db.commit()
    return ok({"deleted": category_id})
