from fastapi import APIRouter, Depends, HTTPException
from sqlmodel import Session, select
from typing import List
from database import get_session
from models import SystemSetting, SystemSettingCreate, SystemSettingRead, SystemSettingUpdate

router = APIRouter(prefix="/settings", tags=["settings"])

@router.get("/", response_model=List[SystemSettingRead])
def read_settings(*, session: Session = Depends(get_session)):
    settings = session.exec(select(SystemSetting)).all()
    return settings

@router.get("/{key}", response_model=SystemSettingRead)
def read_setting(*, session: Session = Depends(get_session), key: str):
    setting = session.exec(select(SystemSetting).where(SystemSetting.key == key)).first()
    if not setting:
        # Return defaults if not found and auto-create them in DB
        new_setting = None
        if key == "default_interval_min":
             new_setting = SystemSetting(key="default_interval_min", value="5", description="Default minimum sending interval")
        elif key == "default_interval_max":
             new_setting = SystemSetting(key="default_interval_max", value="10", description="Default maximum sending interval")
        elif key == "default_concurrency":
             new_setting = SystemSetting(key="default_concurrency", value="1", description="Default concurrency")
        elif key == "system_base_url":
             new_setting = SystemSetting(key="system_base_url", value="http://localhost:18088", description="System Base URL for images/links")
        
        if new_setting:
            try:
                session.add(new_setting)
                session.commit()
                session.refresh(new_setting)
                return new_setting
            except Exception as e:
                # Fallback if creation fails (e.g. race condition)
                print(f"Error auto-creating setting: {e}")
                if key == "default_interval_min":
                      return SystemSetting(id=0, key="default_interval_min", value="5", description="Default minimum sending interval")
                if key == "default_interval_max":
                      return SystemSetting(id=0, key="default_interval_max", value="10", description="Default maximum sending interval")
                if key == "default_concurrency":
                      return SystemSetting(id=0, key="default_concurrency", value="1", description="Default concurrency")
                if key == "system_base_url":
                      return SystemSetting(id=0, key="system_base_url", value="http://localhost:18088", description="System Base URL for images/links")

        raise HTTPException(status_code=404, detail="Setting not found")
    return setting

@router.put("/{key}", response_model=SystemSettingRead)
def update_setting(*, session: Session = Depends(get_session), key: str, setting_in: SystemSettingUpdate):
    setting = session.exec(select(SystemSetting).where(SystemSetting.key == key)).first()
    
    if not setting:
        # Create if not exists
        setting = SystemSetting(key=key, value=setting_in.value, description=setting_in.description)
    else:
        setting.value = setting_in.value
        if setting_in.description:
            setting.description = setting_in.description
            
    session.add(setting)
    session.commit()
    session.refresh(setting)
    return setting
