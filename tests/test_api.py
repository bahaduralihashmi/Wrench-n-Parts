def test_health(client):
    r = client.get("/api/health")
    assert r.status_code == 200
    assert r.json()["success"] is True


def test_categories_list(client, seeded_db):
    r = client.get("/api/categories")
    assert r.status_code == 200
    body = r.json()
    assert body["success"] is True
    assert any(c["category_name"] == "Engine" for c in body["data"])


def test_products_list(client, seeded_db):
    r = client.get("/api/products")
    assert r.status_code == 200
    items = r.json()["data"]
    assert any(p["product_name"] == "Piston" for p in items)


def test_product_detail(client, seeded_db):
    r = client.get("/api/products")
    pid = r.json()["data"][0]["product_id"]
    r2 = client.get(f"/api/products/{pid}")
    assert r2.status_code == 200
    assert r2.json()["data"]["product_name"] == "Piston"


def test_login_success(client, seeded_db):
    r = client.post("/api/auth/login", json={"email": "cust@test.com", "password": "cust123"})
    assert r.status_code == 200, r.text
    body = r.json()
    assert body["success"] is True
    assert body["data"]["user"]["email"] == "cust@test.com"


def test_login_bad_password(client, seeded_db):
    r = client.post("/api/auth/login", json={"email": "cust@test.com", "password": "wrong"})
    assert r.status_code == 401


def test_me_requires_auth(client):
    r = client.get("/api/auth/me")
    assert r.status_code == 401


def test_register_customer_and_login(client):
    r = client.post("/api/auth/register", json={
        "name": "New User", "email": "new@test.com", "password": "secret123", "phone": "123", "address": "St"
    })
    assert r.status_code == 200, r.text
    r2 = client.post("/api/auth/login", json={"email": "new@test.com", "password": "secret123"})
    assert r2.status_code == 200


def test_me_after_login(authed_customer):
    r = authed_customer.get("/api/auth/me")
    assert r.status_code == 200, f"/me failed: {r.text}"


def test_cart_flow(authed_customer, seeded_db):
    pid = authed_customer.get("/api/products").json()["data"][0]["product_id"]
    r = authed_customer.post("/api/cart", json={"product_id": pid, "quantity": 2})
    assert r.status_code == 200, r.text
    r2 = authed_customer.get("/api/cart")
    assert r2.json()["data"]["count"] == 2
    r3 = authed_customer.post("/api/checkout", json={"shipping_address": "123 St", "contact_phone": "555", "payment_method": "cod"})
    assert r3.status_code == 200, r3.text
    r4 = authed_customer.get("/api/cart")
    assert r4.json()["data"]["count"] == 0


def test_admin_forbidden_for_customer(authed_customer):
    r = authed_customer.get("/api/admin/dashboard")
    assert r.status_code == 403, f"Expected 403, got {r.status_code}: {r.text}"


def test_admin_dashboard_for_admin(authed_admin):
    r = authed_admin.get("/api/admin/dashboard")
    assert r.status_code == 200, r.text
    assert "total_users" in r.json()["data"]


def test_chatbot_kb_fallback(client, seeded_db):
    r = client.post("/api/chatbot/message", json={"message": "hello", "session_id": "test-sid"})
    assert r.status_code == 200, r.text
    body = r.json()
    assert "response" in body["data"]


def test_upload_rejects_oversized(client):
    big = b"\0" * (6 * 1024 * 1024)
    r = client.post("/api/uploads/image", files={"file": ("big.bin", big, "image/jpeg")})
    assert r.status_code == 400


def test_search(client, seeded_db):
    r = client.get("/api/search?q=Piston")
    assert r.status_code == 200
    body = r.json()["data"]
    assert any(p["product_name"] == "Piston" for p in body["products"])


def test_workshop_list(client, seeded_db):
    r = client.get("/api/workshops")
    assert r.status_code == 200
    items = r.json()["data"]
    assert any(w["workshop_name"] == "Test WS" for w in items)


def test_appointment_booking_flow(authed_customer, seeded_db):
    r = authed_customer.post("/api/appointments", json={
        "workshop_id": 1, "service_type": "Oil Change",
        "appointment_date": "2026-12-01", "appointment_time": "10:00:00",
        "vehicle_make": "Toyota", "vehicle_model": "Corolla", "vehicle_year": 2020,
    })
    assert r.status_code == 200, r.text
    r2 = authed_customer.get("/api/appointments")
    assert r2.json()["data"][0]["service_type"] == "Oil Change"


def test_reviews_create_and_list(client, seeded_db):
    pid = client.get("/api/products").json()["data"][0]["product_id"]
    # Need authed user
    from tests.conftest import authed_customer as _make
    # Use the authed_customer fixture via login_as helper directly
    from tests.conftest import login_as
    token = login_as(client, "cust@test.com", "cust123")
    r = client.post("/api/reviews", json={"product_id": pid, "rating": 5, "comment": "Great!"}, headers={"cookie": f"wnp_session={token}"})
    assert r.status_code == 200, r.text
    r2 = client.get(f"/api/reviews?product_id={pid}")
    assert any(rv["comment"] == "Great!" for rv in r2.json()["data"])
