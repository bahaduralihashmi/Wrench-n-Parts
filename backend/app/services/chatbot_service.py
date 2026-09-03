"""MechBot chatbot service — KB retrieval + optional Gemini RAG.

Mirrors the original PHP chatbot/api.php functionality:
- Intent detection (emergency, diagnosis, info, cost, maintenance, booking, hours, chat)
- Multi-step conversation state per session_id
- KB retrieval from kb_problems, kb_articles, kb_dtc_codes, kb_faqs
- Optional Gemini API with fallback model list
- Feedback collection (helpful/not, star rating)
- Service history tracking
- Cost estimation
- Maintenance prediction
- Graceful KB-only fallback when no API key
"""
from __future__ import annotations

import json
import os
import re
import time
import uuid
from typing import Any, Optional

import requests

from app.core.config import get_settings


# ---------------------------------------------------------------------------
# Intent detection
# ---------------------------------------------------------------------------
INTENT_KEYWORDS = {
    "emergency": ["accident", "crash", "smoke", "fire", "leaking fuel", "stalled", "brake failure", "overheating", "sparking"],
    "diagnosis": ["why", "problem", "issue", "strange noise", "weird sound", "not working", "doesn't", "won't start", "vibrating", "rattling", "leaking"],
    "cost": ["how much", "cost", "price", "estimate", "repair cost", "expensive"],
    "maintenance": ["when to", "service interval", "schedule", "maintenance", "oil change interval", "when should"],
    "booking": ["book", "appointment", "schedule service", "reserve"],
    "hours": ["opening", "closing", "hours", "timing", "open today", "what time"],
    "info": ["what is", "explain", "info", "information", "tell me about"],
}


def detect_intent(message: str) -> dict:
    msg = message.lower()
    best_intent = "chat"
    best_score = 0
    for intent, kws in INTENT_KEYWORDS.items():
        score = sum(1 for k in kws if k in msg)
        if score > best_score:
            best_score = score
            best_intent = intent
    confidence = min(1.0, best_score / 3.0) if best_score else 0.2
    return {"intent": best_intent, "confidence": round(confidence, 2)}


# ---------------------------------------------------------------------------
# Knowledge base retrieval
# ---------------------------------------------------------------------------
STOPWORDS = {"a", "an", "the", "is", "are", "was", "were", "be", "been", "being",
             "have", "has", "had", "do", "does", "did", "will", "would", "should",
             "can", "could", "may", "might", "must", "shall", "i", "you", "he",
             "she", "it", "we", "they", "them", "their", "my", "your", "our",
             "and", "or", "but", "if", "then", "else", "when", "where", "why",
             "how", "what", "which", "who", "whom", "this", "that", "these",
             "those", "am", "of", "in", "on", "to", "for", "with", "by", "from",
             "as", "at", "into", "so", "than", "too", "very", "just"}


def _score_text(query_tokens: set, text: str) -> float:
    text_tokens = set(re.findall(r"\w+", text.lower())) - STOPWORDS
    if not query_tokens or not text_tokens:
        return 0
    return len(query_tokens & text_tokens) / (len(query_tokens | text_tokens) ** 0.5)


def retrieve_kb(db, message: str, limit: int = 3) -> list[dict]:
    q_tokens = set(re.findall(r"\w+", message.lower())) - STOPWORDS
    if not q_tokens:
        return []

    from app.models.chatbot import KbProblem, KbArticle, KbFaq, KbDtcCode
    candidates = []

    for p in db.query(KbProblem).all():
        s = _score_text(q_tokens, f"{p.problem} {p.symptoms} {p.causes} {p.solution} {p.system}")
        if s > 0:
            candidates.append({"source": "problem", "score": s, "data": p})

    for a in db.query(KbArticle).all():
        s = _score_text(q_tokens, f"{a.title} {a.keywords} {a.content}")
        if s > 0:
            candidates.append({"source": "article", "score": s, "data": a})

    for f in db.query(KbFaq).all():
        s = _score_text(q_tokens, f"{f.question} {f.answer} {f.category}")
        if s > 0:
            candidates.append({"source": "faq", "score": s, "data": f})

    for d in db.query(KbDtcCode).all():
        s = _score_text(q_tokens, f"{d.code} {d.system} {d.description} {d.causes} {d.fixes}")
        if s > 0:
            candidates.append({"source": "dtc", "score": s, "data": d})

    candidates.sort(key=lambda c: c["score"], reverse=True)
    return candidates[:limit]


def format_kb_context(items: list[dict]) -> str:
    lines = []
    for c in items:
        d = c["data"]
        if c["source"] == "problem":
            lines.append(f"[PROBLEM] {d.system}: {d.problem}\nSymptoms: {d.symptoms}\nCauses: {d.causes}\nSolution: {d.solution}")
        elif c["source"] == "article":
            lines.append(f"[ARTICLE] {d.title} ({d.category})\n{d.content}")
        elif c["source"] == "faq":
            lines.append(f"[FAQ] {d.question}\n{d.answer}")
        elif c["source"] == "dtc":
            lines.append(f"[DTC {d.code}] {d.system}: {d.description}\nCauses: {d.causes}\nFixes: {d.fixes}")
    return "\n\n".join(lines)


# ---------------------------------------------------------------------------
# Gemini API (with fallback models)
# ---------------------------------------------------------------------------
def _gemini_request(api_key: str, model: str, prompt: str, timeout: int = 25) -> Optional[str]:
    try:
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent"
        r = requests.post(
            f"{url}?key={api_key}",
            json={
                "contents": [{"parts": [{"text": prompt}]}],
                "generationConfig": {"temperature": 0.4, "maxOutputTokens": 600},
            },
            timeout=timeout,
        )
        if r.status_code != 200:
            return None
        data = r.json()
        parts = data.get("candidates", [{}])[0].get("content", {}).get("parts", [])
        if parts:
            return parts[0].get("text", "").strip() or None
    except Exception:
        return None
    return None


def get_gemini_response(prompt: str, db, fallback_only: bool = False) -> tuple[str, str]:
    """Return (response_text, model_used). Falls back to KB-only."""
    settings = get_settings()
    api_key = settings.gemini_api_key
    # also allow override in DB system_settings
    if db is not None:
        from app.models.settings import SystemSetting
        s = {x.setting_key: x.setting_value for x in db.query(SystemSetting).all()}
        api_key = api_key or s.get("gemini_api_key") or None
    model = settings.gemini_model or "gemini-1.5-flash"
    if db is not None:
        from app.models.settings import SystemSetting
        s = {x.setting_key: x.setting_value for x in db.query(SystemSetting).all()}
        model = s.get("gemini_model") or model

    if not api_key:
        return ("", "")

    fallback_models = ["gemini-1.5-flash", "gemini-1.5-flash-latest", "gemini-flash-latest"]
    text = _gemini_request(api_key, model, prompt)
    if text:
        return text, model
    for m in fallback_models:
        if m == model:
            continue
        text = _gemini_request(api_key, m, prompt)
        if text:
            return text, m
    return ("", "")


# ---------------------------------------------------------------------------
# Session state helpers
# ---------------------------------------------------------------------------
def load_state(db, session_id: str) -> dict:
    from app.models.chatbot import ChatbotState
    row = db.query(ChatbotState).filter(ChatbotState.session_id == session_id).first()
    if row and row.state:
        try:
            return json.loads(row.state)
        except Exception:
            return {}
    return {}


def save_state(db, session_id: str, state: dict) -> None:
    from app.models.chatbot import ChatbotState
    row = db.query(ChatbotState).filter(ChatbotState.session_id == session_id).first()
    payload = json.dumps(state)
    if row:
        row.state = payload
    else:
        db.add(ChatbotState(session_id=session_id, state=payload))
    db.commit()


def load_history(db, session_id: str, limit: int = 10) -> list[dict]:
    from app.models.chatbot import ChatbotConversation
    rows = (
        db.query(ChatbotConversation)
        .filter(ChatbotConversation.session_id == session_id)
        .order_by(ChatbotConversation.id.desc())
        .limit(limit)
        .all()
    )
    rows = list(reversed(rows))
    return [{"role": r.role, "message": r.message} for r in rows]


def save_message(db, session_id: str, user_id: Optional[int], role: str, message: str) -> None:
    from app.models.chatbot import ChatbotConversation
    db.add(ChatbotConversation(session_id=session_id, user_id=user_id, role=role, message=message))
    db.commit()


def log_intent(db, session_id: str, message: str, intent: dict) -> None:
    from app.models.kb import ChatbotIntent
    db.add(ChatbotIntent(session_id=session_id, message=message, detected_intent=intent["intent"], confidence=intent["confidence"]))
    db.commit()


def track_service(db, session_id: str, user_id: Optional[int], state: dict, message: str, response: str) -> None:
    from app.models.kb import VehicleServiceHistory
    if not state:
        return
    service_type = state.get("service_type") or "general"
    if any(k in (message + " " + response).lower() for k in ["service", "repair", "fix", "diagnos"]):
        # Only track once per session unless details change
        last = db.query(VehicleServiceHistory).filter(VehicleServiceHistory.session_id == session_id).order_by(VehicleServiceHistory.id.desc()).first()
        if last and (last.service_type or "").lower() == service_type.lower():
            return
        db.add(VehicleServiceHistory(
            session_id=session_id,
            user_id=user_id,
            vehicle_brand=state.get("brand"),
            vehicle_model=state.get("model"),
            vehicle_year=state.get("year"),
            engine_size=state.get("engine"),
            fuel_type=state.get("fuel"),
            mileage=state.get("mileage"),
            service_type=service_type,
            problem_description=message,
            diagnosis=response[:500],
        ))
        db.commit()


def estimate_cost(message: str, intent: dict, db) -> Optional[dict]:
    """Naive cost estimator from KB problems — find matching problem and assign ballpark."""
    if intent["intent"] not in ("diagnosis", "cost"):
        return None
    candidates = retrieve_kb(db, message, limit=1)
    if not candidates:
        return None
    c = candidates[0]["data"]
    text = f"{c.problem} {c.causes} {c.solution}".lower()
    parts_cost = 0
    labor_cost = 0
    if "brake" in text:
        parts_cost, labor_cost = 3000, 1500
    elif "battery" in text:
        parts_cost, labor_cost = 8000, 500
    elif "oil" in text:
        parts_cost, labor_cost = 1500, 500
    elif "tire" in text:
        parts_cost, labor_cost = 5000, 800
    elif "engine" in text or "piston" in text:
        parts_cost, labor_cost = 25000, 8000
    else:
        parts_cost, labor_cost = 2000, 1000
    total = parts_cost + labor_cost
    from app.models.kb import ChatbotCostEstimate
    db.add(ChatbotCostEstimate(session_id="", problem=c.problem, parts_cost=parts_cost, labor_cost=labor_cost, total_cost=total))
    return {"parts": parts_cost, "labor": labor_cost, "total": total, "currency": "PKR"}


def predict_maintenance(km: str, db) -> str:
    try:
        mileage = int(re.findall(r"\d+", km or "")[0]) if km else 0
    except Exception:
        return ""
    if mileage < 0 or mileage > 1_000_000:
        return ""
    km_int = mileage
    intervals = []
    if km_int >= 5000:
        intervals.append("Oil change recommended every 5,000 km — check engine oil & filter")
    if km_int >= 20000:
        intervals.append("Air filter inspection (every 20,000 km)")
    if km_int >= 40000:
        intervals.append("Brake pads and disc inspection (every 40,000 km)")
    if km_int >= 60000:
        intervals.append("Transmission fluid service (every 60,000 km)")
    if km_int >= 80000:
        intervals.append("Timing belt inspection (every 80,000 km)")
    return " | ".join(intervals) if intervals else ""


def handle_emergency(message: str, db, session_id: str, user_id: Optional[int]) -> str:
    from app.models.kb import ChatbotEmergency
    db.add(ChatbotEmergency(session_id=session_id, user_id=user_id, message=message, emergency_type="vehicle", status="active"))
    db.commit()
    return (
        "🚨 **Emergency guidance**\n\n"
        "1. Move to a safe location and turn on hazard lights.\n"
        "2. If you smell fuel or see smoke, exit the vehicle and move away.\n"
        "3. Call local emergency services if anyone is injured.\n"
        "4. For vehicle breakdown, request a tow: contact your insurer or roadside assistance.\n"
        "5. Once safe, book a workshop appointment via the Appointments page."
    )


# ---------------------------------------------------------------------------
# State machine updates for diagnosis
# ---------------------------------------------------------------------------
DIAGNOSIS_FIELDS = [
    ("brand", r"brand\s*(?:is)?\s*([\w\-]+)"),
    ("model", r"model\s*(?:is)?\s*([\w\-]+)"),
    ("year", r"(\d{4})\s*model"),
    ("mileage", r"(\d{2,7})\s*(?:km|kilometers|kms|miles|mi)"),
    ("engine", r"(\d+\.\d+|\d+)\s*(?:L|liter|litre|cc)"),
    ("fuel", r"(petrol|gasoline|diesel|cng|lpg|hybrid|electric)"),
]


def update_state_from_message(state: dict, message: str) -> dict:
    msg = message.lower()
    for key, pattern in DIAGNOSIS_FIELDS:
        m = re.search(pattern, msg, re.IGNORECASE)
        if m:
            state[key] = m.group(1)
    if "service" in msg or "repair" in msg or "fix" in msg:
        state.setdefault("service_type", "general service")
    return state


# ---------------------------------------------------------------------------
# Top-level response builder
# ---------------------------------------------------------------------------
def generate_response(message: str, db, user_id: Optional[int], session_id: str, history: list[dict], state: dict) -> dict:
    intent = detect_intent(message)
    log_intent(db, session_id, message, intent)

    if intent["intent"] == "emergency":
        response = handle_emergency(message, db, session_id, user_id)
        return {"response": response, "intent": intent["intent"], "confidence": intent["confidence"]}

    # Update state with diagnostic info
    state = update_state_from_message(state, message)
    save_state(db, session_id, state)

    kb_items = retrieve_kb(db, message, limit=3)
    kb_context = format_kb_context(kb_items)

    prompt_parts = [
        "You are MechBot, an automotive assistant for Wrench n Parts. Answer concisely and helpfully.",
        "Use the knowledge base below when relevant. Cite 'Based on our knowledge base:' before sourced facts.",
        "If unsure, say so and recommend booking a workshop.",
    ]
    if kb_context:
        prompt_parts.append("Knowledge base:\n" + kb_context[:3500])
    if state:
        prompt_parts.append("Vehicle context: " + json.dumps({k: v for k, v in state.items() if v}))
    if history:
        recent = history[-6:]
        prompt_parts.append("Recent conversation:\n" + "\n".join(f"{h['role'].upper()}: {h['message']}" for h in recent))
    prompt_parts.append(f"User: {message}")
    prompt_parts.append("Assistant:")
    prompt = "\n\n".join(prompt_parts)

    text, used_model = get_gemini_response(prompt, db)
    if text:
        response = text
    else:
        # KB-only fallback
        if not kb_items:
            response = (
                "I couldn't find a specific answer in my knowledge base. "
                "Could you provide more details (vehicle brand, model, year, mileage)? "
                "Or book a workshop appointment for hands-on diagnosis."
            )
        else:
            top = kb_items[0]["data"]
            if intent["intent"] == "diagnosis":
                response = (
                    f"Based on our knowledge base, this sounds like **{getattr(top, 'problem', '')}**.\n\n"
                    f"**Likely causes:** {getattr(top, 'causes', 'N/A')}\n\n"
                    f"**Recommended solution:** {getattr(top, 'solution', 'N/A')}\n\n"
                    "If symptoms persist, please book a workshop appointment."
                )
            elif intent["intent"] == "cost":
                response = (
                    f"Based on the issue '{getattr(top, 'problem', '')}', "
                    "typical costs vary by vehicle and parts. Book an appointment for an exact quote."
                )
            else:
                response = (
                    f"Based on our knowledge base:\n\n"
                    f"{getattr(top, 'content', '') or getattr(top, 'answer', '') or getattr(top, 'description', '')}"
                )

    cost_est = estimate_cost(message, intent, db) if intent["intent"] in ("diagnosis", "cost") else None
    maint = predict_maintenance(state.get("mileage", ""), db) if state.get("mileage") else ""
    confidence = intent["confidence"]

    return {
        "response": response,
        "intent": intent["intent"],
        "confidence": confidence,
        "cost_estimate": cost_est,
        "maintenance": maint,
        "model_used": used_model,
    }


def save_feedback(db, session_id: str, user_id: Optional[int], message_sent: str, response_given: str, feedback: int, star_rating: Optional[int]) -> None:
    from app.models.kb import ChatbotFeedback
    db.add(ChatbotFeedback(
        session_id=session_id,
        user_id=user_id,
        message_sent=message_sent,
        response_given=response_given,
        feedback=feedback,
    ))
    db.commit()
