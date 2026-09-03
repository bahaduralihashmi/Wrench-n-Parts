from sqlalchemy import Column, Integer, String, Text, Enum, TIMESTAMP, ForeignKey
from sqlalchemy.orm import relationship
from app.database.connection import Base


class ChatbotLog(Base):
    __tablename__ = "chatbot_logs"

    log_id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.user_id", ondelete="SET NULL"))
    question = Column(Text, nullable=False)
    response = Column(Text, nullable=False)
    created_at = Column(TIMESTAMP)


class Review(Base):
    __tablename__ = "reviews"

    review_id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    product_id = Column(Integer, ForeignKey("products.product_id", ondelete="SET NULL"))
    workshop_id = Column(Integer, ForeignKey("workshops.workshop_id", ondelete="SET NULL"))
    rating = Column(Integer, nullable=False)
    comment = Column(Text)
    created_at = Column(TIMESTAMP)

    user = relationship("User")


class Notification(Base):
    __tablename__ = "notifications"

    notification_id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, ForeignKey("users.user_id", ondelete="CASCADE"), nullable=False)
    title = Column(String(200), nullable=False)
    message = Column(Text, nullable=False)
    is_read = Column(Integer, default=0)
    link = Column(String(255))
    created_at = Column(TIMESTAMP)


class KbArticle(Base):
    __tablename__ = "kb_articles"

    id = Column(Integer, primary_key=True, autoincrement=True)
    title = Column(String(200), nullable=False)
    category = Column(Enum("repair_guide", "service_interval", "torque_spec", "general"), default="general")
    keywords = Column(String(255), default="")
    content = Column(Text, nullable=False)
    created_at = Column(TIMESTAMP)


class KbDtcCode(Base):
    __tablename__ = "kb_dtc_codes"

    id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(10), nullable=False, unique=True)
    system = Column(String(50), nullable=False)
    description = Column(String(255), nullable=False)
    causes = Column(Text)
    fixes = Column(Text)
    created_at = Column(TIMESTAMP)


class KbFaq(Base):
    __tablename__ = "kb_faqs"

    id = Column(Integer, primary_key=True, autoincrement=True)
    question = Column(String(255), nullable=False)
    answer = Column(Text, nullable=False)
    category = Column(String(50), default="general")
    created_at = Column(TIMESTAMP)


class KbProblem(Base):
    __tablename__ = "kb_problems"

    id = Column(Integer, primary_key=True, autoincrement=True)
    system = Column(String(60), nullable=False)
    problem = Column(String(255), nullable=False)
    symptoms = Column(Text)
    causes = Column(Text)
    solution = Column(Text)
    created_at = Column(TIMESTAMP)


class ChatbotConversation(Base):
    __tablename__ = "chatbot_conversations"

    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer)
    session_id = Column(String(64), nullable=False)
    role = Column(Enum("user", "assistant"), nullable=False)
    message = Column(Text, nullable=False)
    created_at = Column(TIMESTAMP)


class ChatbotState(Base):
    __tablename__ = "chatbot_state"

    session_id = Column(String(64), primary_key=True)
    state = Column(Text)  # JSON stored as text
    updated_at = Column(TIMESTAMP)


class KbEmbedding(Base):
    __tablename__ = "kb_embeddings"

    id = Column(Integer, primary_key=True, autoincrement=True)
    source_type = Column(Enum("problem", "article", "dtc", "faq"), nullable=False)
    source_id = Column(Integer, nullable=False)
    label = Column(String(255), nullable=False)
    embedding = Column(Text, nullable=False)
    created_at = Column(TIMESTAMP)
