from datetime import datetime
from typing import Optional, List, Any
from pydantic import BaseModel, EmailStr, Field, ConfigDict


class UserCreate(BaseModel):
    name: str = Field(..., max_length=100)
    email: EmailStr
    password: str = Field(..., min_length=6, max_length=128)
    phone: Optional[str] = Field(None, max_length=20)
    address: Optional[str] = None
    role: str = "customer"


class UserLogin(BaseModel):
    email: EmailStr
    password: str


class UserPublic(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    user_id: int
    name: str
    email: EmailStr
    phone: Optional[str] = None
    address: Optional[str] = None
    role: str
    status: str
    profile_image: Optional[str] = None
    created_at: Optional[Any] = None


class ShopkeeperRegister(BaseModel):
    name: str
    email: EmailStr
    password: str
    phone: Optional[str] = None
    shop_name: str
    description: Optional[str] = None
    location: Optional[str] = None
    contact: Optional[str] = None


class WorkshopRegister(BaseModel):
    name: str
    email: EmailStr
    password: str
    phone: Optional[str] = None
    workshop_name: str
    description: Optional[str] = None
    location: Optional[str] = None
    contact: Optional[str] = None
    services: Optional[str] = None


class ProfileUpdate(BaseModel):
    name: Optional[str] = None
    phone: Optional[str] = None
    address: Optional[str] = None
    profile_image: Optional[str] = None


class PasswordChange(BaseModel):
    current_password: str
    new_password: str = Field(..., min_length=6)
