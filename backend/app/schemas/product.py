from typing import Optional, List, Union
from datetime import datetime
from pydantic import BaseModel, Field, ConfigDict
from decimal import Decimal


class CategoryOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    category_id: int
    category_name: str
    category_image: Optional[str] = None
    description: Optional[str] = None


class CategoryCreate(BaseModel):
    category_name: str
    description: Optional[str] = None
    category_image: Optional[str] = None


class ProductOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    product_id: int
    shop_id: int
    category_id: Optional[int] = None
    product_name: str
    description: Optional[str] = None
    price: Decimal
    discount_price: Optional[Decimal] = None
    stock: int
    product_image: Optional[str] = None
    brand: Optional[str] = None
    car_brand: Optional[str] = None
    car_model: Optional[str] = None
    compatible_vehicles: Optional[str] = None
    status: str
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    shop_name: Optional[str] = None
    category_name: Optional[str] = None


class ProductCreate(BaseModel):
    category_id: Optional[int] = None
    product_name: str
    description: Optional[str] = None
    price: Decimal
    discount_price: Optional[Decimal] = None
    stock: int = 0
    brand: Optional[str] = None
    car_brand: Optional[str] = None
    car_model: Optional[str] = None
    compatible_vehicles: Optional[str] = None
    status: str = "available"


class ProductUpdate(BaseModel):
    category_id: Optional[int] = None
    product_name: Optional[str] = None
    description: Optional[str] = None
    price: Optional[Decimal] = None
    discount_price: Optional[Decimal] = None
    stock: Optional[int] = None
    brand: Optional[str] = None
    car_brand: Optional[str] = None
    car_model: Optional[str] = None
    compatible_vehicles: Optional[str] = None
    status: Optional[str] = None
