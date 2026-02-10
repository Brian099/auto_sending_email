from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
import database
import models # Ensure models are loaded

router = APIRouter(prefix="/install", tags=["install"])

class DBConfig(BaseModel):
    host: str
    port: int
    user: str
    password: str
    db_name: str

@router.get("/status")
def get_install_status():
    return {"installed": database.is_installed()}

@router.post("/setup_db")
def setup_database(config: DBConfig):
    # Construct config dict
    new_config = {
        "MYSQL_HOST": config.host,
        "MYSQL_PORT": str(config.port),
        "MYSQL_USER": config.user,
        "MYSQL_PASSWORD": config.password,
        "MYSQL_DB": config.db_name
    }
    
    # Temporarily update config in memory to test
    original_config = database.db_config.copy()
    database.db_config = new_config
    
    # Try to init engine and create tables
    success = database.create_db_and_tables()
    
    if success:
        # Save config to file
        saved = database.save_config(new_config)
        if saved:
            return {"status": "success", "message": "Database configured and installed successfully"}
        else:
            # Revert
            database.db_config = original_config
            raise HTTPException(status_code=500, detail="Database connected but failed to save config file")
    else:
        # Revert
        database.db_config = original_config
        raise HTTPException(status_code=400, detail="Failed to connect to database or create tables. Check credentials.")
