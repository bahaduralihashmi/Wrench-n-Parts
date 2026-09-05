from typing import Optional, List
from decimal import Decimal
from pydantic import BaseModel, Field, ConfigDict


class CartItemOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    cart_id: int
    user_id: int
    product_id: int
    quantity: int
    product_name: Optional[str] = None
    price: Optional[Decimal] = None
    discount_price: Optional[Decimal] = None
    product_image: Optional[str] = None
    stock: Optional[int] = None
    brand: Optional[str] = None
    subtotal: Optional[Decimal] = None


class CartAdd(BaseModel):
    product_id: int
    quantity: int = 1


class CartUpdate(BaseModel):
    quantity: int


class CheckoutIn(BaseModel):
    shipping_address: str
    contact_phone: str
    payment_method: str = "cod"
    notes: Optional[str] = None


class OrderItemOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    item_id: int
    order_id: int
    product_id: int
    quantity: int
    price: Decimal
    product_name: Optional[str] = None
    product_image: Optional[str] = None


class OrderOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    order_id: int
    customer_id: int
    total_amount: Decimal
    shipping_address: str
    contact_phone: str
    payment_method: str
    payment_status: str
    order_status: str
    notes: Optional[str] = None
    created_at: Optional[str] = None
    updated_at: Optional[str] = None
    items: List[OrderItemOut] = []
    customer_name: Optional[str] = None
    customer_email: Optional[str] = None


class OrderStatusUpdate(BaseModel):
    order_status: Optional[str] = None
    payment_status: Optional[str] = None
