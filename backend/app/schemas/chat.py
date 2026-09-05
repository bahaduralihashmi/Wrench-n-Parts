from typing import Optional, List
from pydantic import BaseModel, ConfigDict


class ChatMessageOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    message_id: int
    sender_id: int
    receiver_id: int
    message: str
    is_read: int
    created_at: Optional[str] = None
    sender_name: Optional[str] = None


class ChatSend(BaseModel):
    receiver_id: int
    message: str


class ChatbotMessage(BaseModel):
    message: str
    session_id: Optional[str] = None


class ChatbotFeedback(BaseModel):
    session_id: str
    message_sent: str
    response_given: str
    feedback: int  # 1 or 0
