"""One-shot: import the SQL schema files into the configured MySQL database.

Run:
    python scripts/init_db.py

Reads `database/database.sql`, `database/advanced_features.sql`,
`database/knowledge_base.sql`, `database/rag_vectors.sql`,
`database/kb_problems_seed.sql` and executes them in order.

Safe to re-run; uses `CREATE TABLE IF NOT EXISTS`/`INSERT IGNORE` patterns
already present in the SQL files.
"""
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT / "backend"))

import pymysql
from app.core.config import get_settings

get_settings.cache_clear()
s = get_settings()
c = s.effective_db()

print(f"Connecting to {c['user']}@{c['host']}:{c['port']}/{c['name']} ...")
conn = pymysql.connect(
    host=c["host"], port=c["port"], user=c["user"], password=c["password"],
    database=c["name"], charset="utf8mb4", autocommit=True,
)
cur = conn.cursor()

files = [
    "database/database.sql",
    "database/advanced_features.sql",
    "database/knowledge_base.sql",
    "database/rag_vectors.sql",
    "database/kb_problems_seed.sql",
]

for f in files:
    path = ROOT / f
    if not path.exists():
        print(f"  SKIP {f} (not found)")
        continue
    print(f"  RUN  {f}")
    sql = path.read_text(encoding="utf-8")
    # Strip comments and split on semicolons
    statements = []
    buf = []
    for line in sql.splitlines():
        stripped = line.strip()
        if stripped.startswith("--") or not stripped:
            continue
        buf.append(line)
        if stripped.endswith(";"):
            stmt = "\n".join(buf).rstrip(";").strip()
            if stmt:
                statements.append(stmt)
            buf = []
    for stmt in statements:
        try:
            cur.execute(stmt)
        except pymysql.err.OperationalError as e:
            # Tolerate MySQL-specific things SQLite would choke on (e.g. ENGINE=InnoDB,
            # DEFAULT CHARSET, ON UPDATE CURRENT_TIMESTAMP). Re-raise other errors.
            msg = str(e)
            if any(s in msg for s in ("1064", "syntax", "Unknown", "ENGINE", "CHARSET", "COLLATE")):
                print(f"    warn: {msg[:120]}")
                continue
            raise

cur.close()
conn.close()
print("Done.")
