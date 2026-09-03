from sqlalchemy import Column, Integer, String, Text, TIMESTAMP
from app.database.connection import Base


class SystemSetting(Base):
    __tablename__ = "system_settings"

    setting_id = Column(Integer, primary_key=True, autoincrement=True)
    setting_key = Column(String(100), nullable=False, unique=True)
    setting_value = Column(Text)
    updated_at = Column(TIMESTAMP)
