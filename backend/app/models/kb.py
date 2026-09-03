from sqlalchemy import Column, Integer, String, Text, Date, TIMESTAMP, ForeignKey, Enum
from app.database.connection import Base


class ChatbotFeedback(Base):
    __tablename__ = "chatbot_feedback"

    id = Column(Integer, primary_key=True, autoincrement=True)
    session_id = Column(String(64), nullable=False)
    user_id = Column(Integer)
    message_sent = Column(Text, nullable=False)
    response_given = Column(Text, nullable=False)
    feedback = Column(Integer, nullable=False)  # 1=helpful, 0=not
    admin_reviewed = Column(Integer, default=0)
    admin_action = Column(Enum("pending", "approved", "rejected", "added_to_kb"), default="pending")
    created_at = Column(TIMESTAMP)


class VehicleServiceHistory(Base):
    __tablename__ = "vehicle_service_history"

    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer)
    session_id = Column(String(64), nullable=False)
    vehicle_brand = Column(String(50))
    vehicle_model = Column(String(50))
    vehicle_year = Column(String(4))
    engine_size = Column(String(20))
    fuel_type = Column(String(20))
    mileage = Column(String(30))
    service_type = Column(String(100), nullable=False)
    problem_description = Column(Text)
    diagnosis = Column(Text)
    parts_used = Column(Text)
    cost_pkr = Column(Integer, default=0)
    workshop_name = Column(String(100))
    service_date = Column(Date)
    created_at = Column(TIMESTAMP)


class ChatbotIntent(Base):
    __tablename__ = "chatbot_intents"

    id = Column(Integer, primary_key=True, autoincrement=True)
    session_id = Column(String(64), nullable=False)
    message = Column(Text, nullable=False)
    detected_intent = Column(String(50), nullable=False)
    confidence = Column(Integer, default=0)  # FLOAT in MySQL but kept simple
    sub_intent = Column(String(50))
    created_at = Column(TIMESTAMP)


class ChatbotEmergency(Base):
    __tablename__ = "chatbot_emergency"

    id = Column(Integer, primary_key=True, autoincrement=True)
    session_id = Column(String(64), nullable=False)
    user_id = Column(Integer)
    message = Column(Text, nullable=False)
    emergency_type = Column(String(50))
    location = Column(Text)
    contact = Column(String(30))
    status = Column(Enum("active", "resolved", "escalated"), default="active")
    created_at = Column(TIMESTAMP)


class KbPendingReview(Base):
    __tablename__ = "kb_pending_review"

    id = Column(Integer, primary_key=True, autoincrement=True)
    source_type = Column(Enum("user_feedback", "admin_entry", "auto_extract"), nullable=False)
    source_id = Column(Integer)
    system = Column(String(60))
    problem = Column(String(255))
    symptoms = Column(Text)
    causes = Column(Text)
    solution = Column(Text)
    reviewer_id = Column(Integer)
    status = Column(Enum("pending", "approved", "rejected"), default="pending")
    created_at = Column(TIMESTAMP)


class ChatbotCostEstimate(Base):
    __tablename__ = "chatbot_cost_estimates"

    id = Column(Integer, primary_key=True, autoincrement=True)
    session_id = Column(String(64), nullable=False)
    problem = Column(Text)
    parts_cost = Column(Integer, default=0)
    labor_cost = Column(Integer, default=0)
    total_cost = Column(Integer, default=0)
    confidence = Column(Integer, default=0)
    created_at = Column(TIMESTAMP)
