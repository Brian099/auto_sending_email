from sqlmodel import SQLModel, create_engine, Session, select
import os
import time
import json
import hashlib
from sqlalchemy import text
from sqlalchemy.exc import OperationalError

# Import models for initialization
from models import User, SystemSetting

CONFIG_FILE = "db_config.json"

# Global engine variable
engine = None
db_config = {}

def load_config():
    global db_config
    # 1. Try to load from file
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, 'r') as f:
                db_config = json.load(f)
            print("Loaded database config from file.")
            return
        except Exception as e:
            print(f"Error loading config file: {e}")

    # 2. Fallback to env vars if file not present
    # Only use env vars if MYSQL_HOST is set and not empty
    env_host = os.getenv("MYSQL_HOST", "")
    if env_host and env_host.strip():
        db_config = {
            "MYSQL_USER": os.getenv("MYSQL_USER", "root"),
            "MYSQL_PASSWORD": os.getenv("MYSQL_PASSWORD", "password"),
            "MYSQL_HOST": env_host,
            "MYSQL_PORT": os.getenv("MYSQL_PORT", "3306"),
            "MYSQL_DB": os.getenv("MYSQL_DATABASE", "email_system")
        }
        print("Loaded database config from environment variables.")
    else:
        # No config available
        db_config = {}
        print("No database configuration found (waiting for installation).")

def get_db_url(with_db=True):
    if not db_config:
        return None
        
    user = db_config.get("MYSQL_USER")
    password = db_config.get("MYSQL_PASSWORD")
    host = db_config.get("MYSQL_HOST")
    port = db_config.get("MYSQL_PORT")
    db = db_config.get("MYSQL_DB")
    
    if not host: # Basic validation
        return None
        
    url = f"mysql+pymysql://{user}:{password}@{host}:{port}"
    if with_db:
        url += f"/{db}"
    return url

def init_engine():
    global engine
    load_config()
    database_url = get_db_url()
    
    if not database_url:
        print("Database URL not available. Engine not initialized.")
        engine = None
        return

    try:
        engine = create_engine(database_url, echo=False)
    except Exception as e:
        print(f"Failed to create engine: {e}")
        engine = None

# Initialize on module load
init_engine()

def save_config(new_config):
    global db_config
    try:
        with open(CONFIG_FILE, 'w') as f:
            json.dump(new_config, f, indent=4)
        db_config = new_config
        init_engine() # Re-initialize engine with new config
        return True
    except Exception as e:
        print(f"Error saving config: {e}")
        return False

def create_database_if_not_exists():
    """尝试连接数据库服务器，如果数据库不存在则创建"""
    if not db_config:
        return False

    max_retries = 5 # Reduced retries for faster feedback in UI
    retry_interval = 1
    
    server_url = get_db_url(with_db=False)
    if not server_url:
        return False
        
    db_name = db_config.get("MYSQL_DB")
    if not db_name or db_name == "None":
        print("Database name is invalid/missing in config.")
        return False
        
    host = db_config.get("MYSQL_HOST")
    port = db_config.get("MYSQL_PORT")
    user = db_config.get("MYSQL_USER")

    print(f"Connecting to MySQL at {host}:{port} (User: {user})...")
    
    for i in range(max_retries):
        try:
            # 创建一个临时引擎连接到 MySQL 服务器
            temp_engine = create_engine(server_url)
            with temp_engine.connect() as conn:
                # 检查并创建数据库
                conn.execute(text(f"CREATE DATABASE IF NOT EXISTS {db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
                print(f"Database '{db_name}' ensured.")
            return True
        except OperationalError as e:
            print(f"Database connection failed (attempt {i+1}/{max_retries}): {e}")
            time.sleep(retry_interval)
        except Exception as e:
            print(f"Unexpected error ensuring database: {e}")
            time.sleep(retry_interval)
            
    print("Could not connect to database after multiple attempts.")
    return False

def get_password_hash(password: str) -> str:
    return hashlib.sha256(password.encode()).hexdigest()

def init_default_data(engine):
    """Initialize default data (user, settings) if not exists"""
    try:
        with Session(engine) as session:
            # 1. Init User
            user = session.exec(select(User).where(User.username == "admin")).first()
            if not user:
                print("Initializing default admin user...")
                admin_user = User(
                    username="admin",
                    password_hash=get_password_hash("admin123")
                )
                session.add(admin_user)
                print("Default user 'admin' created.")
            
            # 2. Init System Settings
            default_settings = {
                "default_interval_min": ("10", "Minimum sending interval in seconds"),
                "default_interval_max": ("60", "Maximum sending interval in seconds"),
                "default_concurrency": ("1", "Number of concurrent sending tasks")
            }
            
            for key, (value, desc) in default_settings.items():
                setting = session.exec(select(SystemSetting).where(SystemSetting.key == key)).first()
                if not setting:
                    print(f"Initializing setting '{key}'...")
                    new_setting = SystemSetting(key=key, value=value, description=desc)
                    session.add(new_setting)
            
            session.commit()
            print("Default data initialization completed.")
    except Exception as e:
        print(f"Error initializing default data: {e}")

def create_db_and_tables():
    # If not installed, we try to create if config is available (e.g. from setup_db call)
    # But setup_db calls this function AFTER setting db_config manually.
    # So we should check if db_config is set.
    if not db_config:
        print("System not installed/configured (no config). Skipping table creation.")
        return False

    if create_database_if_not_exists():
        try:
            # Ensure models are imported (redundant but safe)
            import models
            print(f"Creating tables for models: {SQLModel.metadata.tables.keys()}")
            
            # Use current config to ensure engine is ready
            current_url = get_db_url()
            current_engine = engine
            if not current_engine:
                 current_engine = create_engine(current_url, echo=False)

            SQLModel.metadata.create_all(current_engine)
            print("Tables created successfully.")

            # Initialize default data
            init_default_data(current_engine)
            
            return True
        except Exception as e:
            print(f"Error creating tables: {e}")
            import traceback
            traceback.print_exc()
            return False
    else:
        print("Skipping table creation due to connection failure.")
        return False

def get_session():
    if engine is None:
        # Try to re-init
        init_engine()
        if engine is None:
            raise Exception("Database engine not initialized. Please configure database.")
            
    with Session(engine) as session:
        yield session

def is_installed():
    """Check if config file exists and we can connect"""
    # Simply check if engine is initialized. 
    # Engine is only initialized if config file exists OR env vars are present.
    return engine is not None
