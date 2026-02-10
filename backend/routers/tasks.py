from fastapi import APIRouter, Depends, HTTPException, Query
from sqlmodel import Session, select
from typing import List, Dict, Any
from database import get_session
from models import SendingTask, SendingTaskCreate, SendingTaskRead, SendingLog, SendingLogRead, Recipient, Sender, Template

router = APIRouter(prefix="/tasks", tags=["tasks"])

@router.post("/", response_model=SendingTaskRead)
def create_task(*, session: Session = Depends(get_session), task: SendingTaskCreate):
    """
    Create a new sending task.
    Config can include:
    - interval: seconds between sends (default 1)
    - concurrency: number of parallel sends (default 1)
    - group_ids: list of recipient group IDs to target (optional)
    - template_ids: list of template IDs to use (optional)
    """
    db_task = SendingTask.from_orm(task)
    session.add(db_task)
    session.commit()
    session.refresh(db_task)
    return db_task

@router.get("/", response_model=List[SendingTaskRead])
def read_tasks(*, session: Session = Depends(get_session), offset: int = 0, limit: int = Query(default=100, le=100)):
    tasks = session.exec(select(SendingTask).order_by(SendingTask.created_at.desc()).offset(offset).limit(limit)).all()
    return tasks

@router.get("/{task_id}", response_model=SendingTaskRead)
def read_task(*, session: Session = Depends(get_session), task_id: int):
    task = session.get(SendingTask, task_id)
    if not task:
        raise HTTPException(status_code=404, detail="Task not found")
    return task

@router.post("/{task_id}/start", response_model=SendingTaskRead)
def start_task(*, session: Session = Depends(get_session), task_id: int):
    task = session.get(SendingTask, task_id)
    if not task:
        raise HTTPException(status_code=404, detail="Task not found")
    
    task.status = "running"
    session.add(task)
    session.commit()
    session.refresh(task)
    return task

@router.post("/{task_id}/pause", response_model=SendingTaskRead)
def pause_task(*, session: Session = Depends(get_session), task_id: int):
    task = session.get(SendingTask, task_id)
    if not task:
        raise HTTPException(status_code=404, detail="Task not found")
    
    if task.status == "running":
        task.status = "paused"
        session.add(task)
        session.commit()
        session.refresh(task)
    return task

@router.post("/{task_id}/stop", response_model=SendingTaskRead)
def stop_task(*, session: Session = Depends(get_session), task_id: int):
    task = session.get(SendingTask, task_id)
    if not task:
        raise HTTPException(status_code=404, detail="Task not found")
    
    task.status = "cancelled"
    session.add(task)
    session.commit()
    session.refresh(task)
    return task

@router.get("/{task_id}/logs", response_model=List[SendingLogRead])
def read_task_logs(
    *, 
    session: Session = Depends(get_session), 
    task_id: int, 
    offset: int = 0, 
    limit: int = Query(default=100, le=100)
):
    # Join with Recipient, Sender, and Template to get details
    statement = (
        select(SendingLog, Recipient, Sender, Template)
        .where(SendingLog.task_id == task_id)
        .join(Recipient, SendingLog.recipient_id == Recipient.id, isouter=True)
        .join(Sender, SendingLog.sender_id == Sender.id, isouter=True)
        .join(Template, SendingLog.template_id == Template.id, isouter=True)
        .order_by(SendingLog.sent_at.desc())
        .offset(offset)
        .limit(limit)
    )
    
    results = session.exec(statement).all()
    
    logs = []
    for log, recipient, sender, template in results:
        log_read = SendingLogRead.from_orm(log)
        if recipient:
            log_read.recipient_email = recipient.email
        if sender:
            log_read.sender_email = sender.email
        if template:
            log_read.template_subject = template.subject
        logs.append(log_read)
        
    return logs
