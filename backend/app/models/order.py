from sqlalchemy import Column, Integer, String, Text, Numeric, Enum, TIMESTAMP, ForeignKey
from app.database.connection import Base


class Order(Base):
    __tablename__ = "orders"

    order_id = Column(Integer, primary_key=True, autoincrement=True)
    customer_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    total_amount = Column(Numeric(10, 2), nullable=False)
    shipping_address = Column(Text, nullable=False)
    contact_phone = Column(String(20), nullable=False)
    payment_method = Column(Enum("cod", "card", "upi", "netbanking"), default="cod")
    payment_status = Column(Enum("pending", "paid", "failed", "refunded"), default="pending")
    order_status = Column(Enum("pending", "confirmed", "processing", "shipped", "delivered", "cancelled"), default="pending")
    notes = Column(Text)
    created_at = Column(TIMESTAMP)
    updated_at = Column(TIMESTAMP)


class OrderItem(Base):
    __tablename__ = "order_items"

    item_id = Column(Integer, primary_key=True, autoincrement=True)
    order_id = Column(Integer, ForeignKey("orders.order_id", ondelete="CASCADE"), nullable=False)
    product_id = Column(Integer, ForeignKey("products.product_id", ondelete="CASCADE"), nullable=False)
    quantity = Column(Integer, nullable=False, default=1)
    price = Column(Numeric(10, 2), nullable=False)
