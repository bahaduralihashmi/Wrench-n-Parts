from datetime import time
from sqlalchemy import Column, Integer, String, Text, Numeric, Enum, Time, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class Workshop(Base):
    __tablename__ = "workshops"

    workshop_id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    workshop_name = Column(String(150), nullable=False)
    description = Column(Text)
    location = Column(String(255))
    contact = Column(String(20))
    services = Column(Text)
    logo = Column(String(255))
    rating = Column(Numeric(3, 2), default=0.00)
    total_reviews = Column(Integer, default=0)
    status = Column(Enum("active", "inactive", "pending"), default="pending")
    opening_time = Column(Time, default=time(8, 0))
    closing_time = Column(Time, default=time(18, 0))
    created_at = Column(TIMESTAMP)

    owner = relationship("User", back_populates="workshop")
