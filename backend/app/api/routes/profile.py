"""User profile management."""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.user import User
from app.core.security import get_current_user, hash_password, verify_password
from app.schemas.user import ProfileUpdate, PasswordChange, UserPublic
from app.schemas.response import ok

router = APIRouter()


@router.get("")
def my_profile(user: User = Depends(get_current_user)):
    return ok(UserPublic.model_validate(user).model_dump(mode="json"))


@router.put("")
def update_profile(payload: ProfileUpdate, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    data = payload.model_dump(exclude_unset=True)
    for k, v in data.items():
        setattr(user, k, v)
    db.commit()
    db.refresh(user)
    return ok(UserPublic.model_validate(user).model_dump(mode="json"))


@router.post("/change-password")
def change_password(payload: PasswordChange, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if not verify_password(payload.current_password, user.password):
        raise HTTPException(status_code=400, detail="Current password is incorrect")
    user.password = hash_password(payload.new_password)
    db.commit()
    return ok({"changed": True})
