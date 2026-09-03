from sqlalchemy import Column, Integer, String, Text, Numeric, Enum, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class Product(Base):
    __tablename__ = "products"

    product_id = Column(Integer, primary_key=True, autoincrement=True)
    shop_id = Column(Integer, ForeignKey("shops.shop_id", ondelete="CASCADE"), nullable=False)
    category_id = Column(Integer, ForeignKey("categories.category_id", ondelete="SET NULL"))
    product_name = Column(String(200), nullable=False)
    description = Column(Text)
    price = Column(Numeric(10, 2), nullable=False)
    discount_price = Column(Numeric(10, 2))
    stock = Column(Integer, default=0)
    product_image = Column(String(255))
    brand = Column(String(100))
    car_brand = Column(String(100))
    car_model = Column(String(100))
    compatible_vehicles = Column(Text)
    status = Column(Enum("available", "unavailable", "discontinued"), default="available")
    created_at = Column(TIMESTAMP)
    updated_at = Column(TIMESTAMP)

    shop = relationship("Shop", back_populates="products")
    category = relationship("Category")
