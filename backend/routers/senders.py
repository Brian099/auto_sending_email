from fastapi import APIRouter, Depends, HTTPException, Query
from sqlmodel import Session, select
from typing import List
from database import get_session
from models import Sender, SenderCreate, SenderRead, SenderUpdate, User
from routers.auth import get_current_user

from services.email_sender import EmailSenderService

router = APIRouter(prefix="/senders", tags=["senders"])

@router.post("/verify")
async def verify_sender_connection(sender: SenderCreate, current_user: User = Depends(get_current_user)):
    """Verify SMTP connection for a sender (without saving)"""
    # Create temporary sender object
    temp_sender = Sender(**sender.dict())
    success, message = await EmailSenderService.verify_sender(temp_sender)
    if not success:
        raise HTTPException(status_code=400, detail=f"Verification failed: {message}")
    return {"status": "success", "message": "Connection verified"}

@router.post("/", response_model=SenderRead)
async def create_sender(*, session: Session = Depends(get_session), sender: SenderCreate, current_user: User = Depends(get_current_user)):
    # Verify before create
    temp_sender = Sender(**sender.dict())
    success, message = await EmailSenderService.verify_sender(temp_sender)
    if not success:
         raise HTTPException(status_code=400, detail=f"Verification failed: {message}")

    db_sender = Sender.from_orm(sender)
    session.add(db_sender)
    session.commit()
    session.refresh(db_sender)
    return db_sender

@router.get("/", response_model=List[SenderRead])
def read_senders(*, session: Session = Depends(get_session), offset: int = 0, limit: int = Query(default=100, le=100), current_user: User = Depends(get_current_user)):
    senders = session.exec(select(Sender).offset(offset).limit(limit)).all()
    return senders

@router.get("/{sender_id}", response_model=SenderRead)
def read_sender(*, session: Session = Depends(get_session), sender_id: int, current_user: User = Depends(get_current_user)):
    sender = session.get(Sender, sender_id)
    if not sender:
        raise HTTPException(status_code=404, detail="Sender not found")
    return sender

@router.patch("/{sender_id}", response_model=SenderRead)
async def update_sender(*, session: Session = Depends(get_session), sender_id: int, sender: SenderUpdate, current_user: User = Depends(get_current_user)):
    db_sender = session.get(Sender, sender_id)
    if not db_sender:
        raise HTTPException(status_code=404, detail="Sender not found")
    
    sender_data = sender.dict(exclude_unset=True)
    
    # If critical fields changed, verify again
    if any(k in sender_data for k in ["smtp_host", "smtp_port", "smtp_user", "smtp_password"]):
        # Construct merged object for verification
        merged_data = db_sender.dict()
        merged_data.update(sender_data)
        temp_sender = Sender(**merged_data)
        
        success, message = await EmailSenderService.verify_sender(temp_sender)
        if not success:
             raise HTTPException(status_code=400, detail=f"Verification failed: {message}")

    for key, value in sender_data.items():
        setattr(db_sender, key, value)
    
    session.add(db_sender)
    session.commit()
    session.refresh(db_sender)
    return db_sender

@router.delete("/{sender_id}")
def delete_sender(*, session: Session = Depends(get_session), sender_id: int, current_user: User = Depends(get_current_user)):
    sender = session.get(Sender, sender_id)
    if not sender:
        raise HTTPException(status_code=404, detail="Sender not found")
    session.delete(sender)
    session.commit()
    return {"ok": True}
