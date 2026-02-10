from fastapi import APIRouter, Depends
from sqlmodel import Session, select, func
from database import get_session
from models import Recipient, SendingTask, Sender, Template, User
from routers.auth import get_current_user

router = APIRouter(prefix="/dashboard", tags=["dashboard"])

@router.get("/stats")
def get_stats(session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    recipients_count = session.exec(select(func.count(Recipient.id))).one()
    tasks_count = session.exec(select(func.count(SendingTask.id))).one()
    senders_count = session.exec(select(func.count(Sender.id))).one()
    templates_count = session.exec(select(func.count(Template.id))).one()
    
    return {
        "total_recipients": recipients_count,
        "total_tasks": tasks_count,
        "total_senders": senders_count,
        "total_templates": templates_count
    }
