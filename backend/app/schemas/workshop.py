from typing import Optional
from decimal import Decimal
from pydantic import BaseModel, Field, ConfigDict


class ShopOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    shop_id: int
    user_id: int
    shop_name: str
    description: Optional[str] = None
    location: Optional[str] = None
    contact: Optional[str] = None
    logo: Optional[str] = None
    status: str


class WorkshopOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    workshop_id: int
    user_id: int
    workshop_name: str
    description: Optional[str] = None
    location: Optional[str] = None
    contact: Optional[str] = None
    services: Optional[str] = None
    logo: Optional[str] = None
    rating: Optional[Decimal] = None
    total_reviews: int
    status: str
    opening_time: Optional[str] = None
    closing_time: Optional[str] = None

    @classmethod
    def from_orm_flex(cls, obj):
        """Tolerate TIME columns which come back as datetime.time."""
        from datetime import time as _time
        def _strify(v):
            if isinstance(v, _time):
                return v.strftime("%H:%M:%S")
            return v
        d = {c: getattr(obj, c, None) for c in cls.model_fields.keys()}
        for k in ("opening_time", "closing_time"):
            if d.get(k) is not None:
                d[k] = _strify(d[k])
        if d.get("rating") is not None:
            d["rating"] = Decimal(str(d["rating"]))
        return cls(**d)


class AppointmentIn(BaseModel):
    workshop_id: int
    vehicle_make: Optional[str] = None
    vehicle_model: Optional[str] = None
    vehicle_year: Optional[int] = None
    service_type: str
    description: Optional[str] = None
    appointment_date: str  # YYYY-MM-DD
    appointment_time: str  # HH:MM:SS


class AppointmentUpdate(BaseModel):
    status: Optional[str] = None
    workshop_notes: Optional[str] = None
    estimated_cost: Optional[Decimal] = None


class ReviewIn(BaseModel):
    product_id: Optional[int] = None
    workshop_id: Optional[int] = None
    rating: int = Field(..., ge=1, le=5)
    comment: Optional[str] = None
