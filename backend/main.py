from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from contextlib import asynccontextmanager
from database import create_db_and_tables
from routers import recipients, senders, templates, tasks, settings, install, upload, dashboard, auth
from services.task_runner import task_runner
import os

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Create tables (try, but don't crash if unconfigured)
    try:
        create_db_and_tables()
    except Exception as e:
        print(f"Startup DB init failed (expected if not installed): {e}")
        
    # Startup: Start Task Runner
    task_runner.start()
    yield
    # Shutdown: Stop Task Runner
    task_runner.stop()

app = FastAPI(lifespan=lifespan)

# Mount uploads directory
# Ensure directory exists (assuming running from backend directory, so data is at ../data)
UPLOAD_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), "data", "uploads")
os.makedirs(UPLOAD_DIR, exist_ok=True)
app.mount("/uploads", StaticFiles(directory=UPLOAD_DIR), name="uploads")

app.include_router(recipients.router)
app.include_router(senders.router)
app.include_router(templates.router)
app.include_router(tasks.router)
app.include_router(settings.router)
app.include_router(install.router)
app.include_router(upload.router)
app.include_router(dashboard.router)
app.include_router(auth.router)

@app.get("/")
def read_root():
    return {"message": "Email System Backend is running"}

@app.get("/health")
def health_check():
    return {"status": "ok"}
