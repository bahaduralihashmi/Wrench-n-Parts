from sqlalchemy import Column, Integer, String, Text, Numeric, Enum, Time, Date, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class Appointment(Base):
    __tablename__ = "appointments"

    appointment_id = Column(Integer, primary_key=True, autoincrement=True)
    customer_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    workshop_id = Column(Integer, ForeignKey("workshops.workshop_id", ondelete="CASCADE"), nullable=False)
    vehicle_make = Column(String(100))
    vehicle_model = Column(String(100))
    vehicle_year = Column(Integer)
    service_type = Column(String(200))
    description = Column(Text)
    appointment_date = Column(Date, nullable=False)
    appointment_time = Column(Time, nullable=False)
    status = Column(Enum("pending", "approved", "in_progress", "completed", "cancelled"), default="pending")
    workshop_notes = Column(Text)
    estimated_cost = Column(Numeric(10, 2), default=0.00)
    created_at = Column(TIMESTAMP)
    updated_at = Column(TIMESTAMP)

    customer = relationship("User", foreign_keys=[customer_id])
    workshop = relationship("Workshop")
