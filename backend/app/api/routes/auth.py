"""Authentication routes: register, login, logout, me."""
from fastapi import APIRouter, Depends, HTTPException, Response
from sqlalchemy.orm import Session

from app.core.security import (
    create_access_token,
    set_auth_cookie,
    clear_auth_cookie,
    verify_password,
    hash_password,
    get_current_user,
    get_current_user_optional,
)
from app.core.config import get_settings
from app.database.connection import get_db
from app.models.user import User
from app.models.shop import Shop
from app.models.workshop import Workshop
from app.schemas.user import (
    UserCreate,
    UserLogin,
    UserPublic,
    ShopkeeperRegister,
    WorkshopRegister,
)
from app.schemas.response import ok

router = APIRouter()

DASH_BY_ROLE = {
    "customer": "/customer/dashboard.html",
    "shopkeeper": "/shopkeeper/dashboard.html",
    "workshop": "/workshop/dashboard.html",
    "admin": "/admin/dashboard.html",
    "management": "/management/dashboard.html",
}


def _issue_session(response: Response, user: User) -> dict:
    token = create_access_token(user.user_id, user.role)
    set_auth_cookie(response, token)
    return {
        "user": UserPublic.model_validate(user).model_dump(mode="json"),
        "token": token,
        "redirect": DASH_BY_ROLE.get(user.role, "/index.html"),
    }


@router.post("/register")
def register_customer(payload: UserCreate, response: Response, db: Session = Depends(get_db)):
    if db.query(User).filter(User.email == payload.email).first():
        raise HTTPException(status_code=400, detail="Email already registered")
    user = User(
        name=payload.name,
        email=payload.email,
        password=hash_password(payload.password),
        phone=payload.phone,
        address=payload.address,
        role="customer",
        status="active",  # customers auto-active as in the original
    )
    db.add(user)
    db.commit()
    db.refresh(user)
    return ok(_issue_session(response, user))


@router.post("/register-shopkeeper")
def register_shopkeeper(payload: ShopkeeperRegister, db: Session = Depends(get_db)):
    if db.query(User).filter(User.email == payload.email).first():
        raise HTTPException(status_code=400, detail="Email already registered")
    user = User(
        name=payload.name,
        email=payload.email,
        password=hash_password(payload.password),
        phone=payload.phone,
        role="shopkeeper",
        status="pending",
    )
    db.add(user)
    db.flush()
    shop = Shop(
        user_id=user.user_id,
        shop_name=payload.shop_name,
        description=payload.description,
        location=payload.location,
        contact=payload.contact,
        status="pending",
    )
    db.add(shop)
    db.commit()
    return ok({"message": "Application submitted. Wait for admin approval."})


@router.post("/register-workshop")
def register_workshop(payload: WorkshopRegister, db: Session = Depends(get_db)):
    if db.query(User).filter(User.email == payload.email).first():
        raise HTTPException(status_code=400, detail="Email already registered")
    user = User(
        name=payload.name,
        email=payload.email,
        password=hash_password(payload.password),
        phone=payload.phone,
        role="workshop",
        status="pending",
    )
    db.add(user)
    db.flush()
    ws = Workshop(
        user_id=user.user_id,
        workshop_name=payload.workshop_name,
        description=payload.description,
        location=payload.location,
        contact=payload.contact,
        services=payload.services,
        status="pending",
    )
    db.add(ws)
    db.commit()
    return ok({"message": "Application submitted. Wait for admin approval."})


@router.post("/login")
def login(payload: UserLogin, response: Response, db: Session = Depends(get_db)):
    user = db.query(User).filter(User.email == payload.email).first()
    if not user or not verify_password(payload.password, user.password):
        raise HTTPException(status_code=401, detail="Invalid email or password")
    status = user.status
    if status == "banned":
        raise HTTPException(status_code=403, detail="Your account has been banned. Contact admin.")
    if status in ("pending",) and user.role in ("shopkeeper", "workshop"):
        raise HTTPException(status_code=403, detail="Your account is waiting for admin approval.")
    if status == "rejected":
        raise HTTPException(status_code=403, detail="Your account has been rejected by the administrator.")
    if status not in ("active", "approved"):
        raise HTTPException(status_code=403, detail="Account not active.")
    return ok(_issue_session(response, user))


@router.post("/logout")
def logout(response: Response):
    clear_auth_cookie(response)
    return ok({"message": "Logged out"})


@router.get("/me")
def me(user: User = Depends(get_current_user)):
    return ok(UserPublic.model_validate(user).model_dump(mode="json"))


@router.get("/me/optional")
def me_optional(user=Depends(get_current_user_optional)):
    if not user:
        return ok(None)
    return ok(UserPublic.model_validate(user).model_dump(mode="json"))
