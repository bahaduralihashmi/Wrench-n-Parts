"""Pytest fixtures: spin up a FastAPI TestClient backed by SQLite in-memory."""
import os
import sys
from pathlib import Path

import pytest

# Configure test environment BEFORE importing app modules
ROOT = Path(__file__).resolve().parent.parent
BACKEND = ROOT / "backend"
sys.path.insert(0, str(BACKEND))

os.environ.setdefault("SECRET_KEY", "test-secret-key-do-not-use-in-prod")
os.environ.setdefault("ENVIRONMENT", "test")
os.environ.setdefault("DB_NAME", ":memory:")  # ignored when override is in place
os.environ.setdefault("STORAGE_PROVIDER", "local")
os.environ.setdefault("STORAGE_LOCAL_DIR", str(ROOT / "backend" / "uploads"))
os.environ.setdefault("GEMINI_API_KEY", "")  # force KB-only fallback


@pytest.fixture(scope="session")
def test_engine():
    """SQLite in-memory engine with shared StaticPool so data persists across connections."""
    from sqlalchemy import create_engine
    from sqlalchemy.pool import StaticPool
    from app.database.connection import Base
    from app.database import base as _base  # noqa — registers all models

    engine = create_engine(
        "sqlite+pysqlite:///:memory:",
        connect_args={"check_same_thread": False},
        poolclass=StaticPool,
        future=True,
    )
    Base.metadata.create_all(engine)
    return engine


@pytest.fixture
def db_session(test_engine):
    from sqlalchemy.orm import sessionmaker
    Session = sessionmaker(bind=test_engine, autoflush=False, autocommit=False, future=True)
    s = Session()
    try:
        yield s
    finally:
        s.close()


@pytest.fixture
def app(monkeypatch, test_engine):
    """FastAPI app with database session dependency overridden to use SQLite."""
    from sqlalchemy.orm import sessionmaker
    from sqlalchemy import event
    from app.main import create_app
    from app.database.connection import get_db

    TestSession = sessionmaker(bind=test_engine, autoflush=False, autocommit=False, future=True)

    def _override():
        s = TestSession()
        try:
            yield s
        finally:
            s.close()

    app = create_app()
    app.dependency_overrides[get_db] = _override
    yield app


@pytest.fixture
def client(app):
    from fastapi.testclient import TestClient
    c = TestClient(app)
    c.__enter__()
    yield c
    c.__exit__(None, None, None)


def login_as(client, email, password):
    """Helper: log in and return the session cookie value."""
    r = client.post("/api/auth/login", json={"email": email, "password": password})
    assert r.status_code == 200, r.text
    return r.cookies.get("wnp_session")


@pytest.fixture
def authed_customer(client, seeded_db):
    """Returns a wrapper that sets the auth cookie via headers for subsequent requests."""
    token = login_as(client, "cust@test.com", "cust123")
    # Monkey-patch the client's request methods to inject the cookie header
    original_get = client.get
    original_post = client.post
    original_put = client.put
    original_delete = client.delete

    def _with(method):
        def wrapper(url, **kw):
            kw.setdefault("headers", {})
            if isinstance(kw["headers"], dict):
                kw["headers"]["cookie"] = f"wnp_session={token}"
            return method(url, **kw)
        return wrapper

    client.get = _with(original_get)
    client.post = _with(original_post)
    client.put = _with(original_put)
    client.delete = _with(original_delete)
    return client


@pytest.fixture
def authed_admin(client, seeded_db):
    token = login_as(client, "admin@test.com", "admin123")
    original_get = client.get
    original_post = client.post
    original_put = client.put
    original_delete = client.delete

    def _with(method):
        def wrapper(url, **kw):
            kw.setdefault("headers", {})
            if isinstance(kw["headers"], dict):
                kw["headers"]["cookie"] = f"wnp_session={token}"
            return method(url, **kw)
        return wrapper

    client.get = _with(original_get)
    client.post = _with(original_post)
    client.put = _with(original_put)
    client.delete = _with(original_delete)
    return client


@pytest.fixture
def seeded_db(test_engine):
    """Seed minimal data: admin, shopkeeper, customer, workshop, product, category.
    Drops and re-creates schema between sessions to avoid leftover data from previous tests."""
    from sqlalchemy.orm import sessionmaker
    from app.database.connection import Base
    from app.core.security import hash_password
    from app.models.user import User
    from app.models.shop import Shop
    from app.models.workshop import Workshop
    from app.models.category import Category
    from app.models.product import Product

    Base.metadata.drop_all(test_engine)
    Base.metadata.create_all(test_engine)

    Session = sessionmaker(bind=test_engine)
    s = Session()
    try:
        admin = User(name="Admin", email="admin@test.com", password=hash_password("admin123"), role="admin", status="active")
        customer = User(name="Cust", email="cust@test.com", password=hash_password("cust123"), role="customer", status="active")
        shopkeeper = User(name="Shop", email="shop@test.com", password=hash_password("shop123"), role="shopkeeper", status="active")
        workshop = User(name="Work", email="work@test.com", password=hash_password("work123"), role="workshop", status="active")
        s.add_all([admin, customer, shopkeeper, workshop]); s.flush()

        shop = Shop(user_id=shopkeeper.user_id, shop_name="Test Shop", status="active")
        s.add(shop); s.flush()
        ws = Workshop(user_id=workshop.user_id, workshop_name="Test WS", status="active")
        s.add(ws); s.flush()

        cat = Category(category_name="Engine", description="Engine parts")
        s.add(cat); s.flush()
        prod = Product(shop_id=shop.shop_id, category_id=cat.category_id, product_name="Piston", price=99.99, stock=10, status="available", brand="Bosch")
        s.add(prod)
        s.commit()
    finally:
        s.close()
