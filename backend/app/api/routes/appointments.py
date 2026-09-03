"""Appointment / booking endpoints for workshops."""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session

from app.database.connection import get_db
from app.models.workshop import Workshop
from app.models.appointment import Appointment
from app.models.user import User
from app.models.notification import Notification
from app.core.security import get_current_user, require_roles
from app.schemas.workshop import AppointmentIn, AppointmentUpdate
from app.schemas.response import ok

router = APIRouter()


def _serialize(a: Appointment) -> dict:
    return {
        "appointment_id": a.appointment_id,
        "customer_id": a.customer_id,
        "workshop_id": a.workshop_id,
        "vehicle_make": a.vehicle_make,
        "vehicle_model": a.vehicle_model,
        "vehicle_year": a.vehicle_year,
        "service_type": a.service_type,
        "description": a.description,
        "appointment_date": str(a.appointment_date) if a.appointment_date else None,
        "appointment_time": str(a.appointment_time) if a.appointment_time else None,
        "status": a.status,
        "workshop_notes": a.workshop_notes,
        "estimated_cost": str(a.estimated_cost) if a.estimated_cost else None,
        "created_at": str(a.created_at) if a.created_at else None,
        "updated_at": str(a.updated_at) if a.updated_at else None,
        "customer_name": a.customer.name if a.customer else None,
        "workshop_name": a.workshop.workshop_name if a.workshop else None,
    }


@router.get("")
def list_appointments(
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
    status_filter: Optional[str] = Query(None, alias="status"),
):
    q = db.query(Appointment)
    if user.role == "customer":
        q = q.filter(Appointment.customer_id == user.user_id)
    elif user.role == "workshop":
        ws = db.query(Workshop).filter(Workshop.user_id == user.user_id).first()
        if not ws:
            return ok([])
        q = q.filter(Appointment.workshop_id == ws.workshop_id)
    elif user.role not in ("admin", "management"):
        return ok([])
    if status_filter:
        q = q.filter(Appointment.status == status_filter)
    q = q.order_by(Appointment.appointment_date.desc(), Appointment.appointment_time.desc())
    return ok([_serialize(a) for a in q.all()])


@router.post("")
def create_appointment(payload: AppointmentIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    from datetime import date as _date, time as _time
    ws = db.query(Workshop).filter(Workshop.workshop_id == payload.workshop_id).first()
    if not ws:
        raise HTTPException(status_code=404, detail="Workshop not found")
    # Normalize date/time — accept either string or object depending on backend
    ap_date = payload.appointment_date
    if isinstance(ap_date, str):
        try:
            ap_date = _date.fromisoformat(ap_date)
        except ValueError:
            pass
    ap_time = payload.appointment_time
    if isinstance(ap_time, str):
        try:
            parts = ap_time.split(":")
            ap_time = _time(int(parts[0]), int(parts[1]) if len(parts) > 1 else 0, int(parts[2]) if len(parts) > 2 else 0)
        except Exception:
            pass
    a = Appointment(
        customer_id=user.user_id,
        workshop_id=payload.workshop_id,
        vehicle_make=payload.vehicle_make,
        vehicle_model=payload.vehicle_model,
        vehicle_year=payload.vehicle_year,
        service_type=payload.service_type,
        description=payload.description,
        appointment_date=ap_date,
        appointment_time=ap_time,
        status="pending",
    )
    db.add(a)
    if ws.user_id:
        db.add(Notification(
            user_id=ws.user_id,
            title="New appointment",
            message=f"New booking for {payload.service_type} on {payload.appointment_date}",
            link="/workshop/appointments.html",
        ))
    db.commit()
    db.refresh(a)
    return ok(_serialize(a))


@router.put("/{appointment_id}")
def update_appointment(appointment_id: int, payload: AppointmentUpdate, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    a = db.query(Appointment).filter(Appointment.appointment_id == appointment_id).first()
    if not a:
        raise HTTPException(status_code=404, detail="Appointment not found")
    if user.role == "customer":
        if a.customer_id != user.user_id:
            raise HTTPException(status_code=403, detail="Forbidden")
        if payload.status and payload.status not in ("cancelled",):
            raise HTTPException(status_code=403, detail="Customers can only cancel")
    elif user.role == "workshop":
        ws = db.query(Workshop).filter(Workshop.user_id == user.user_id).first()
        if not ws or ws.workshop_id != a.workshop_id:
            raise HTTPException(status_code=403, detail="Forbidden")
    if payload.status:
        a.status = payload.status
    if payload.workshop_notes is not None:
        a.workshop_notes = payload.workshop_notes
    if payload.estimated_cost is not None:
        a.estimated_cost = payload.estimated_cost
    db.commit()
    return ok(_serialize(a))


@router.delete("/{appointment_id}")
def delete_appointment(appointment_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    a = db.query(Appointment).filter(Appointment.appointment_id == appointment_id).first()
    if not a:
        raise HTTPException(status_code=404, detail="Appointment not found")
    db.delete(a)
    db.commit()
    return ok({"deleted": appointment_id})
