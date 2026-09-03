"""Chatbot HTTP routes."""
import uuid
from fastapi import APIRouter, Depends, HTTPException, Request
from sqlalchemy.orm import Session

from app.core.security import get_current_user_optional
from app.database.connection import get_db
from app.models.user import User
from app.services import chatbot_service
from app.schemas.chat import ChatbotMessage, ChatbotFeedback
from app.schemas.response import ok

router = APIRouter()


@router.post("/message")
def chat(payload: ChatbotMessage, request: Request, db: Session = Depends(get_db), user: User = Depends(get_current_user_optional)):
    from app.models.settings import SystemSetting
    settings_row = {s.setting_key: s.setting_value for s in db.query(SystemSetting).all()}
    if settings_row.get("chatbot_enabled", "1") in ("0", "disabled"):
        raise HTTPException(status_code=503, detail="Chatbot is currently disabled")

    message = (payload.message or "").strip()
    if not message or len(message) > 500:
        raise HTTPException(status_code=400, detail="Please enter a valid message (1-500 characters)")

    session_id = payload.session_id or request.cookies.get("wnp_chat_sid") or uuid.uuid4().hex
    user_id = user.user_id if user else None

    history = chatbot_service.load_history(db, session_id)
    state = chatbot_service.load_state(db, session_id)

    result = chatbot_service.generate_response(message, db, user_id, session_id, history, state)

    chatbot_service.save_message(db, session_id, user_id, "user", message)
    chatbot_service.save_message(db, session_id, user_id, "assistant", result["response"])
    chatbot_service.track_service(db, session_id, user_id, state, message, result["response"])

    if user_id:
        from app.models.chatbot import ChatbotLog
        db.add(ChatbotLog(user_id=user_id, question=message, response=result["response"]))
        db.commit()

    response = ok({
        "response": result["response"],
        "intent": result.get("intent"),
        "confidence": result.get("confidence"),
        "cost_estimate": result.get("cost_estimate"),
        "maintenance": result.get("maintenance"),
        "session_id": session_id,
    })
    # Make session sticky via cookie for 30 days
    from fastapi import Response
    resp_obj = Response(content=None)
    resp_obj.set_cookie("wnp_chat_sid", session_id, max_age=30 * 24 * 3600, httponly=True, samesite="lax", path="/")
    return response


@router.post("/feedback")
def feedback(payload: ChatbotFeedback, db: Session = Depends(get_db), user: User = Depends(get_current_user_optional)):
    if payload.feedback not in (0, 1):
        raise HTTPException(status_code=400, detail="feedback must be 0 or 1")
    chatbot_service.save_feedback(db, payload.session_id, user.user_id if user else None, payload.message_sent, payload.response_given, payload.feedback, None)
    return ok({"saved": True})


@router.get("/history")
def history(session_id: str, db: Session = Depends(get_db), _u: User = Depends(get_current_user_optional)):
    items = chatbot_service.load_history(db, session_id, limit=100)
    return ok(items)


@router.get("/session/{session_id}/state")
def get_state(session_id: str, db: Session = Depends(get_db)):
    return ok(chatbot_service.load_state(db, session_id))


@router.delete("/session/{session_id}/state")
def reset_state(session_id: str, db: Session = Depends(get_db)):
    from app.models.chatbot import ChatbotState
    row = db.query(ChatbotState).filter(ChatbotState.session_id == session_id).first()
    if row:
        db.delete(row)
        db.commit()
    return ok({"reset": True})
