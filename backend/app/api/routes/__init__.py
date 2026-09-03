from fastapi import FastAPI

from app.api.routes.auth import router as auth_router
from app.api.routes.products import router as products_router
from app.api.routes.categories import router as categories_router
from app.api.routes.cart import router as cart_router
from app.api.routes.wishlist import router as wishlist_router
from app.api.routes.orders import router as orders_router
from app.api.routes.checkout import router as checkout_router
from app.api.routes.shop import router as shop_router
from app.api.routes.workshop import router as workshop_router
from app.api.routes.appointments import router as appointments_router
from app.api.routes.reviews import router as reviews_router
from app.api.routes.notifications import router as notifications_router
from app.api.routes.search import router as search_router
from app.api.routes.uploads import router as uploads_router
from app.api.routes.settings import router as settings_router
from app.api.routes.profile import router as profile_router
from app.api.routes.admin import router as admin_router
from app.api.routes.management import router as management_router
from app.api.routes.chatbot import router as chatbot_router
from app.api.routes.returns import router as returns_router
from app.api.routes.chat import router as chat_router


def register_routers(app: FastAPI) -> None:
    app.include_router(auth_router, prefix="/api/auth", tags=["auth"])
    app.include_router(products_router, prefix="/api/products", tags=["products"])
    app.include_router(categories_router, prefix="/api/categories", tags=["categories"])
    app.include_router(cart_router, prefix="/api/cart", tags=["cart"])
    app.include_router(wishlist_router, prefix="/api/wishlist", tags=["wishlist"])
    app.include_router(orders_router, prefix="/api/orders", tags=["orders"])
    app.include_router(checkout_router, prefix="/api/checkout", tags=["checkout"])
    app.include_router(shop_router, prefix="/api/shops", tags=["shops"])
    app.include_router(workshop_router, prefix="/api/workshops", tags=["workshops"])
    app.include_router(appointments_router, prefix="/api/appointments", tags=["appointments"])
    app.include_router(reviews_router, prefix="/api/reviews", tags=["reviews"])
    app.include_router(notifications_router, prefix="/api/notifications", tags=["notifications"])
    app.include_router(search_router, prefix="/api/search", tags=["search"])
    app.include_router(uploads_router, prefix="/api/uploads", tags=["uploads"])
    app.include_router(settings_router, prefix="/api/settings", tags=["settings"])
    app.include_router(profile_router, prefix="/api/profile", tags=["profile"])
    app.include_router(admin_router, prefix="/api/admin", tags=["admin"])
    app.include_router(management_router, prefix="/api/management", tags=["management"])
    app.include_router(chatbot_router, prefix="/api/chatbot", tags=["chatbot"])
    app.include_router(returns_router, prefix="/api/returns", tags=["returns"])
    app.include_router(chat_router, prefix="/api/chat", tags=["chat"])
