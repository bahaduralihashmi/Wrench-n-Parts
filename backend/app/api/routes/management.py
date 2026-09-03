"""Management-team endpoints: analytics, knowledge base CRUD, feedback review, reports."""
from decimal import Decimal
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.database.connection import get_db
from app.models.user import User
from app.models.product import Product
from app.models.order import Order, OrderItem
from app.models.workshop import Workshop
from app.models.shop import Shop
from app.models.category import Category
from app.models.appointment import Appointment
from app.models.kb import (
    ChatbotFeedback,
    KbPendingReview,
    VehicleServiceHistory,
    ChatbotIntent,
    ChatbotEmergency,
    ChatbotCostEstimate,
)
from app.models.chatbot import KbArticle, KbProblem, KbDtcCode, KbFaq
from app.core.security import require_roles
from app.schemas.response import ok

router = APIRouter()


@router.get("/dashboard")
def mgmt_dashboard(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    return ok({
        "total_shops": db.query(Shop).count(),
        "active_shops": db.query(Shop).filter(Shop.status == "active").count(),
        "total_workshops": db.query(Workshop).count(),
        "active_workshops": db.query(Workshop).filter(Workshop.status == "active").count(),
        "total_products": db.query(Product).count(),
        "total_categories": db.query(Category).count(),
        "total_orders": db.query(Order).count(),
        "pending_orders": db.query(Order).filter(Order.order_status == "pending").count(),
        "total_appointments": db.query(Appointment).count(),
        "pending_feedback": db.query(ChatbotFeedback).filter(ChatbotFeedback.admin_action == "pending").count(),
        "pending_kb_review": db.query(KbPendingReview).filter(KbPendingReview.status == "pending").count(),
    })


@router.get("/analytics")
def analytics(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    orders_by_status = (
        db.query(Order.order_status, func.count(Order.order_id))
        .group_by(Order.order_status)
        .all()
    )
    top_categories = (
        db.query(Category.category_name, func.count(OrderItem.item_id).label("sold"))
        .join(Product, Product.category_id == Category.category_id)
        .join(OrderItem, OrderItem.product_id == Product.product_id)
        .group_by(Category.category_id)
        .order_by(func.count(OrderItem.item_id).desc())
        .limit(10)
        .all()
    )
    revenue = db.query(func.coalesce(func.sum(Order.total_amount), 0)).filter(Order.payment_status == "paid").scalar()
    return ok({
        "revenue_total": str(revenue),
        "orders_by_status": {k: v for k, v in orders_by_status},
        "top_categories": [{"category": c, "sold": s} for c, s in top_categories],
    })


@router.get("/reports")
def reports(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    paid = db.query(func.coalesce(func.sum(Order.total_amount), 0)).filter(Order.payment_status == "paid").scalar()
    avg = db.query(func.coalesce(func.avg(Order.total_amount), 0)).scalar()
    return ok({
        "total_revenue": str(paid),
        "average_order_value": str(avg),
        "total_orders": db.query(Order).count(),
        "cancelled_orders": db.query(Order).filter(Order.order_status == "cancelled").count(),
    })


# KB articles
@router.get("/kb/articles")
def list_kb_articles(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management")), category: Optional[str] = None):
    q = db.query(KbArticle)
    if category:
        q = q.filter(KbArticle.category == category)
    return ok([{"id": a.id, "title": a.title, "category": a.category, "keywords": a.keywords, "content": a.content} for a in q.all()])


@router.post("/kb/articles")
def create_kb_article(payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    a = KbArticle(**payload)
    db.add(a)
    db.commit()
    db.refresh(a)
    return ok({"id": a.id})


@router.put("/kb/articles/{article_id}")
def update_kb_article(article_id: int, payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    a = db.query(KbArticle).filter(KbArticle.id == article_id).first()
    if not a:
        raise HTTPException(status_code=404, detail="Article not found")
    for k, v in payload.items():
        setattr(a, k, v)
    db.commit()
    return ok({"id": a.id})


@router.delete("/kb/articles/{article_id}")
def delete_kb_article(article_id: int, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    a = db.query(KbArticle).filter(KbArticle.id == article_id).first()
    if not a:
        raise HTTPException(status_code=404, detail="Article not found")
    db.delete(a)
    db.commit()
    return ok({"deleted": article_id})


# KB problems
@router.get("/kb/problems")
def list_kb_problems(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    items = db.query(KbProblem).all()
    return ok([{"id": p.id, "system": p.system, "problem": p.problem, "symptoms": p.symptoms, "causes": p.causes, "solution": p.solution} for p in items])


@router.post("/kb/problems")
def create_kb_problem(payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    p = KbProblem(**payload)
    db.add(p)
    db.commit()
    db.refresh(p)
    return ok({"id": p.id})


# Feedback review
@router.get("/feedback")
def list_feedback(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management")), action: Optional[str] = "pending"):
    q = db.query(ChatbotFeedback)
    if action:
        q = q.filter(ChatbotFeedback.admin_action == action)
    return ok([{
        "id": f.id, "session_id": f.session_id, "user_id": f.user_id,
        "message_sent": f.message_sent, "response_given": f.response_given,
        "feedback": f.feedback, "admin_action": f.admin_action,
        "created_at": str(f.created_at) if f.created_at else None,
    } for f in q.order_by(ChatbotFeedback.created_at.desc()).all()])


@router.put("/feedback/{feedback_id}")
def review_feedback(feedback_id: int, payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    f = db.query(ChatbotFeedback).filter(ChatbotFeedback.id == feedback_id).first()
    if not f:
        raise HTTPException(status_code=404, detail="Feedback not found")
    if "admin_action" in payload:
        f.admin_action = payload["admin_action"]
    f.admin_reviewed = 1
    db.commit()
    return ok({"id": f.id, "admin_action": f.admin_action})


# Pending KB review
@router.get("/kb/pending")
def list_kb_pending(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    items = db.query(KbPendingReview).filter(KbPendingReview.status == "pending").all()
    return ok([{
        "id": r.id, "source_type": r.source_type, "system": r.system, "problem": r.problem,
        "symptoms": r.symptoms, "causes": r.causes, "solution": r.solution,
    } for r in items])


@router.put("/kb/pending/{review_id}")
def review_kb_pending(review_id: int, payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    r = db.query(KbPendingReview).filter(KbPendingReview.id == review_id).first()
    if not r:
        raise HTTPException(status_code=404, detail="Pending review not found")
    if payload.get("status") == "approved":
        # Move to KB problems
        new_problem = KbProblem(
            system=r.system or "general",
            problem=r.problem or "Untitled",
            symptoms=r.symptoms,
            causes=r.causes,
            solution=r.solution,
        )
        db.add(new_problem)
    if "status" in payload:
        r.status = payload["status"]
    db.commit()
    return ok({"id": r.id, "status": r.status})


@router.get("/chatbot-config")
def get_chatbot_config(db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    from app.models.settings import SystemSetting
    s = {x.setting_key: x.setting_value for x in db.query(SystemSetting).all()}
    return ok({
        "chatbot_enabled": s.get("chatbot_enabled", "1"),
        "chatbot_name": s.get("chatbot_name", "MechBot"),
        "gemini_model": s.get("gemini_model", "gemini-1.5-flash"),
        "has_api_key": bool(s.get("gemini_api_key")),
    })


@router.put("/chatbot-config")
def update_chatbot_config(payload: dict, db: Session = Depends(get_db), _u: User = Depends(require_roles("admin", "management"))):
    from app.models.settings import SystemSetting
    for k in ("chatbot_enabled", "chatbot_name", "gemini_model", "gemini_api_key"):
        if k in payload:
            s = db.query(SystemSetting).filter(SystemSetting.setting_key == k).first()
            if s:
                s.setting_value = str(payload[k])
            else:
                db.add(SystemSetting(setting_key=k, setting_value=str(payload[k])))
    db.commit()
    return ok({"updated": True})
