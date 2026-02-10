from fastapi import APIRouter, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import shutil
import os
import uuid
from pathlib import Path

router = APIRouter(prefix="/upload", tags=["upload"])

# Configuration for upload directory
# Assuming running from backend/ directory, and data is in ../data/uploads
# But we should be careful about paths.
# Let's try to find a good absolute path or relative to project root.
BASE_DIR = Path(__file__).resolve().parent.parent.parent
UPLOAD_DIR = BASE_DIR / "data" / "uploads"

# Ensure directory exists
os.makedirs(UPLOAD_DIR, exist_ok=True)

@router.post("/image")
async def upload_image(file: UploadFile = File(...)):
    try:
        # Validate file type
        if not file.content_type.startswith("image/"):
            raise HTTPException(status_code=400, detail="Invalid file type")
        
        # Generate unique filename
        file_ext = os.path.splitext(file.filename)[1]
        if not file_ext:
            # Default to .jpg if no extension (or guess from content type)
            file_ext = ".jpg" 
        
        unique_filename = f"{uuid.uuid4()}{file_ext}"
        file_path = UPLOAD_DIR / unique_filename
        
        # Save file
        with open(file_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
            
        # Return URL for TinyMCE
        # location field is required by TinyMCE
        # We assume the frontend accesses backend via /api proxy
        # So we return /api/uploads/filename
        return {"location": f"/api/uploads/{unique_filename}"}
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
