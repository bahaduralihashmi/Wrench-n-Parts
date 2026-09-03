# Wrench n Parts

> A complete marketplace for automobile spare parts and verified workshops — migrated from PHP/MySQL/Apache to **Python FastAPI + MySQL + Vercel**.

Customers browse parts, add to cart, checkout, and book workshops. Shopkeepers manage inventory and orders. Workshops manage appointments and reviews. Admins manage everything. A 24/7 AI chatbot (MechBot) answers automotive questions.

---

## Architecture

```
Wrench_n_Parts/
├── frontend/                # Static HTML + vanilla JS (converted from PHP pages)
│   ├── index.html, products.html, product-detail.html, cart.html, checkout.html
│   ├── login.html, register*.html, about.html, support.html, privacy-policy.html
│   ├── admin/         (14 dashboards)
│   ├── shopkeeper/    (8 dashboards)
│   ├── workshop/      (5 dashboards)
│   ├── customer/      (7 pages)
│   ├── management/    (7 dashboards)
│   ├── css/, js/, images/, uploads/, manifest.json
│
├── backend/app/             # FastAPI application
│   ├── main.py, core/, database/, models/, schemas/, services/, repositories/, api/routes/
│
├── api/index.py             # Vercel serverless entry point
├── tests/                   # pytest + httpx (18 tests, all passing)
├── legacy_php/              # Original PHP/MySQL/Apache code (preserved, not used)
├── database/                # Original SQL schema files (unchanged)
├── vercel.json              # Vercel deployment configuration
├── requirements.txt, .env.example, .gitignore
├── run_dev.bat              # Local development launcher
├── MIGRATION_REPORT.md      # Detailed migration report
```

The original PHP code is preserved in `legacy_php/` and is not invoked at runtime. The migrated application has **no PHP or Apache dependency**.

---

## Local development

### Requirements
- Python 3.10+
- MySQL 5.7+ or 8.x (local or hosted)
- Git

### Steps (Windows)

```powershell
git clone https://github.com/bahaduralihashmi/Wrench-n-Parts
cd Wrench_n_Parts

python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt

copy .env.example .env
# edit .env with your MySQL credentials and secrets
```

### Run the dev server

```powershell
run_dev.bat
```

Or manually:

```powershell
cd backend
..\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8000 --reload
```

Open <http://127.0.0.1:8000/>.

### Run tests

```powershell
cd backend
..\.venv\Scripts\python.exe -m pytest tests
```

The test suite spins up an in-memory SQLite copy of the schema, seeds minimal data, and exercises 18 end-to-end API scenarios.

---

## Database setup

The application uses the **same MySQL schema** as the original PHP application. Three SQL files live in `database/`:

```
database/database.sql         -- core tables (users, shops, products, workshops, orders, …)
database/advanced_features.sql -- KB + chatbot tables (chatbot_feedback, vehicle_service_history, intents, …)
database/knowledge_base.sql   -- KB seed (kb_articles, kb_problems, kb_dtc_codes, kb_faqs)
database/rag_vectors.sql     -- RAG vector storage
```

To import:

```bash
mysql -u root -p wrench_parts_db < database/database.sql
mysql -u root -p wrench_parts_db < database/advanced_features.sql
mysql -u root -p wrench_parts_db < database/knowledge_base.sql
mysql -u root -p wrench_parts_db < database/rag_vectors.sql
mysql -u root -p wrench_parts_db < database/kb_problems_seed.sql
```

The application does **not** create tables. It expects them to exist.

---

## Environment variables

See `.env.example` for the full list. The app accepts **both** naming conventions for the database:

```env
# Option A — generic MySQL (PlanetScale, AWS RDS, local, …)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=wrench_parts_db
DB_USER=root
DB_PASSWORD=

# Option B — Railway MySQL (auto-populated from Railway service variables)
MYSQLHOST=mysql.railway.internal
MYSQLPORT=3306
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=

SITE_URL=http://localhost:8000
FRONTEND_URL=http://localhost:8000
SECRET_KEY=change-me-to-a-long-random-string

# Optional — chatbot AI
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash

# Optional — file uploads
STORAGE_PROVIDER=local           # local | s3
STORAGE_LOCAL_DIR=backend/uploads
STORAGE_PUBLIC_PREFIX=/uploads

# S3-compatible (only when STORAGE_PROVIDER=s3)
STORAGE_ENDPOINT=
STORAGE_REGION=auto
STORAGE_ACCESS_KEY=
STORAGE_SECRET_KEY=
STORAGE_BUCKET=
STORAGE_PUBLIC_URL=

ENVIRONMENT=development          # development | production
```

If `GEMINI_API_KEY` is missing, the chatbot still works using the local knowledge base (graceful fallback).

### Railway MySQL quick-start

1. In your Railway project, add a **MySQL** plugin.
2. Copy the auto-generated `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` into your `.env` (or paste them into Vercel environment variables).
3. Import your schema once:
   ```bash
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> wrench_parts_db < database/database.sql
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> wrench_parts_db < database/advanced_features.sql
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> wrench_parts_db < database/knowledge_base.sql
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> wrench_parts_db < database/rag_vectors.sql
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> wrench_parts_db < database/kb_problems_seed.sql
   ```
   (Railway's default database name is `railway`; substitute it for `wrench_parts_db` above if you kept the default.)

---

## Vercel deployment

### Steps

1. Push the repo to GitHub.
2. Go to <https://vercel.com/new> and import the repository.
3. Vercel auto-detects `vercel.json`. Override the Python runtime if needed (default is Python 3.11).
4. Add the environment variables from `.env.example` in **Project Settings → Environment Variables**.
5. For the database, point `DB_*` to a hosted MySQL (PlanetScale, Railway, Aiven, AWS RDS, etc.).
6. For uploads, configure `STORAGE_PROVIDER=s3` and provide S3-compatible credentials (Cloudflare R2, AWS S3, Supabase S3, etc.).
7. Deploy.

### What `vercel.json` does

- Builds `api/index.py` with `@vercel/python`.
- Routes `/api/*` and `/uploads/*` to the FastAPI app.
- Routes everything else to `frontend/` static files.

### Persistent storage

**Vercel serverless functions have ephemeral filesystems.** The `StorageService` abstraction defaults to local disk (fine for dev) and switches to S3-compatible object storage in production. Plug in Cloudflare R2, AWS S3, or Supabase Storage by setting `STORAGE_PROVIDER=s3` and the corresponding credentials.

---

## Security

- Passwords are hashed with bcrypt (compatible with the original PHP `password_hash`).
- Sessions use signed JWT tokens in `HttpOnly`, `Secure` (production) cookies.
- Role-based authorization via FastAPI dependencies (`require_roles(...)`).
- All SQL uses SQLAlchemy parameterized queries (no injection).
- Pydantic validates every request body.
- File uploads are type- and size-validated.
- Global exception handler returns generic errors in production (no stack traces leaked).
- `.gitignore` blocks `.env`, `__pycache__`, `.venv/`, etc.

---

## Testing checklist

Manual checklist:

- [x] Anonymous user can browse, search, view products
- [x] Registration works for customer / shopkeeper / workshop
- [x] Login issues cookie and `/api/auth/me` works
- [x] Wrong password returns 401
- [x] Customer can add to cart, view cart, checkout, see orders
- [x] Shopkeeper can manage products, see only their orders
- [x] Workshop can approve/cancel appointments
- [x] Admin can list users, set status, approve shops/workshops
- [x] Management can view analytics, KB, chatbot feedback
- [x] Chatbot returns KB-based answers with no API key (fallback)
- [x] File upload rejects >5MB files and unsupported types
- [x] All 52 HTML pages return 200 on the dev server
- [x] 18 pytest tests pass

---

## API endpoints (highlights)

See `/api/docs` on the running server for full OpenAPI docs.

| Module | Examples |
|---|---|
| Auth | `POST /api/auth/login`, `POST /api/auth/register`, `POST /api/auth/logout`, `GET /api/auth/me` |
| Products | `GET /api/products`, `GET /api/products/{id}`, `GET /api/products/hot-deals` |
| Cart | `GET /api/cart`, `POST /api/cart`, `PUT /api/cart/{pid}`, `DELETE /api/cart/{pid}` |
| Checkout/Orders | `POST /api/checkout`, `GET /api/orders`, `PUT /api/orders/{id}` |
| Workshops | `GET /api/workshops`, `GET /api/workshops/{id}`, `PUT /api/workshops/mine` |
| Appointments | `GET /api/appointments`, `POST /api/appointments`, `PUT /api/appointments/{id}` |
| Reviews | `GET /api/reviews`, `POST /api/reviews` |
| Notifications | `GET /api/notifications`, `PUT /api/notifications/{id}/read` |
| Search | `GET /api/search?q=` |
| Chatbot | `POST /api/chatbot/message`, `POST /api/chatbot/feedback`, `GET /api/chatbot/history` |
| Admin | `GET /api/admin/dashboard`, `GET /api/admin/users`, `PUT /api/admin/users/{id}/status` |
| Management | `GET /api/management/analytics`, `GET /api/management/kb/articles` |
| Uploads | `POST /api/uploads/image`, `POST /api/uploads/file` |
| Profile | `GET /api/profile`, `PUT /api/profile`, `POST /api/profile/change-password` |
| Settings | `GET /api/settings`, `GET /api/settings/all` (admin), `PUT /api/settings` (admin) |

---

## Migration from PHP

See `MIGRATION_REPORT.md` for the full migration plan, completed work, API mapping table, and any remaining items.
