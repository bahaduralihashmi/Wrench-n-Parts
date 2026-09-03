from sqlalchemy import Column, Integer, String, Text, Enum, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class User(Base):
    __tablename__ = "users"

    user_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(100), nullable=False)
    email = Column(String(150), nullable=False, unique=True)
    password = Column(String(255), nullable=False)
    phone = Column(String(20))
    address = Column(Text)
    role = Column(Enum("customer", "shopkeeper", "workshop", "admin", "management"), default="customer")
    status = Column(Enum("pending", "active", "inactive", "banned", "approved", "rejected"), default="pending")
    profile_image = Column(String(255))
    created_at = Column(TIMESTAMP)
    updated_at = Column(TIMESTAMP)

    shop = relationship("Shop", back_populates="owner", uselist=False, cascade="all, delete-orphan")
    workshop = relationship("Workshop", back_populates="owner", uselist=False, cascade="all, delete-orphan")
