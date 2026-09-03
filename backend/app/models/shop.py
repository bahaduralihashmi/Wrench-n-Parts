from sqlalchemy import Column, Integer, String, Text, Enum, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class Shop(Base):
    __tablename__ = "shops"

    shop_id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    shop_name = Column(String(150), nullable=False)
    description = Column(Text)
    location = Column(String(255))
    contact = Column(String(20))
    logo = Column(String(255))
    status = Column(Enum("active", "inactive", "pending"), default="pending")
    created_at = Column(TIMESTAMP)

    owner = relationship("User", back_populates="shop")
    products = relationship("Product", back_populates="shop", cascade="all, delete-orphan")
