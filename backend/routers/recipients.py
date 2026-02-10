from fastapi import APIRouter, Depends, HTTPException, Query, UploadFile, File, Body
from sqlmodel import Session, select, col
from typing import List, Optional, Any
from database import get_session
from models import Recipient, RecipientCreate, RecipientRead, RecipientUpdate, RecipientGroup, RecipientGroupCreate, RecipientGroupRead, RecipientGroupUpdate, Template, User
from routers.auth import get_current_user
import csv
import io
import codecs
from pydantic import BaseModel

router = APIRouter(prefix="/recipients", tags=["recipients"])

class BatchOperation(BaseModel):
    recipient_ids: List[int]
    action: str  # "set_group", "set_status", "delete"
    value: Optional[Any] = None

# --- Groups ---

@router.post("/groups/", response_model=RecipientGroupRead)
def create_group(*, session: Session = Depends(get_session), group: RecipientGroupCreate, current_user: User = Depends(get_current_user)):
    db_group = RecipientGroup.from_orm(group)
    
    # Handle templates
    if group.template_ids:
        templates = session.exec(select(Template).where(col(Template.id).in_(group.template_ids))).all()
        db_group.templates = templates

    session.add(db_group)
    session.commit()
    session.refresh(db_group)
    
    # Manually populate template_ids and template_names for response
    response_group = RecipientGroupRead.from_orm(db_group)
    response_group.template_ids = [t.id for t in db_group.templates]
    response_group.template_names = [t.subject for t in db_group.templates]
    return response_group

@router.get("/groups/", response_model=List[RecipientGroupRead])
def read_groups(*, session: Session = Depends(get_session), offset: int = 0, limit: int = Query(default=100, le=100), current_user: User = Depends(get_current_user)):
    groups = session.exec(select(RecipientGroup).offset(offset).limit(limit)).all()
    
    # Populate template_ids and template_names for each group
    result = []
    for group in groups:
        group_read = RecipientGroupRead.from_orm(group)
        group_read.template_ids = [t.id for t in group.templates]
        group_read.template_names = [t.subject for t in group.templates]
        result.append(group_read)
        
    return result

@router.patch("/groups/{group_id}", response_model=RecipientGroupRead)
def update_group(*, session: Session = Depends(get_session), group_id: int, group: RecipientGroupUpdate, current_user: User = Depends(get_current_user)):
    db_group = session.get(RecipientGroup, group_id)
    if not db_group:
        raise HTTPException(status_code=404, detail="Group not found")
    
    group_data = group.dict(exclude_unset=True)
    
    # Handle template_ids separately
    if "template_ids" in group_data:
        template_ids = group_data.pop("template_ids")
        if template_ids is not None:
             templates = session.exec(select(Template).where(col(Template.id).in_(template_ids))).all()
             db_group.templates = templates
        else:
             db_group.templates = []

    for key, value in group_data.items():
        setattr(db_group, key, value)
        
    session.add(db_group)
    session.commit()
    session.refresh(db_group)
    
    response_group = RecipientGroupRead.from_orm(db_group)
    response_group.template_ids = [t.id for t in db_group.templates]
    response_group.template_names = [t.subject for t in db_group.templates]
    return response_group

@router.delete("/groups/{group_id}")
def delete_group(*, session: Session = Depends(get_session), group_id: int, current_user: User = Depends(get_current_user)):
    group = session.get(RecipientGroup, group_id)
    if not group:
        raise HTTPException(status_code=404, detail="Group not found")
    
    # Optional: Check if used by recipients?
    # For now, just set recipients' group_id to null or let database handle it (if cascade not set, manual update needed)
    # SQLAlchemy relationship default is usually nullify if not specified.
    # Let's manually nullify to be safe
    statement = select(Recipient).where(Recipient.group_id == group_id)
    recipients = session.exec(statement).all()
    for recipient in recipients:
        recipient.group_id = None
        session.add(recipient)
        
    session.delete(group)
    session.commit()
    return {"ok": True}

# --- Recipients ---

@router.post("/batch")
def batch_operation(*, session: Session = Depends(get_session), operation: BatchOperation, current_user: User = Depends(get_current_user)):
    """
    Perform batch operations on recipients.
    Actions:
    - set_group: value = group_id (int) or None
    - set_status: value = "active" or "inactive"
    - delete: value ignored
    """
    if not operation.recipient_ids:
        return {"count": 0}

    statement = select(Recipient).where(col(Recipient.id).in_(operation.recipient_ids))
    recipients = session.exec(statement).all()
    
    count = 0
    for recipient in recipients:
        if operation.action == "set_group":
            try:
                # Handle None or int
                group_id = int(operation.value) if operation.value is not None else None
                recipient.group_id = group_id
                session.add(recipient)
                count += 1
            except (ValueError, TypeError):
                continue
                
        elif operation.action == "set_status":
            if operation.value in ["active", "inactive"]:
                recipient.status = operation.value
                session.add(recipient)
                count += 1
                
        elif operation.action == "delete":
            session.delete(recipient)
            count += 1
            
    session.commit()
    return {"count": count, "action": operation.action}

@router.post("/", response_model=RecipientRead)
def create_recipient(*, session: Session = Depends(get_session), recipient: RecipientCreate, current_user: User = Depends(get_current_user)):
    db_recipient = Recipient.from_orm(recipient)
    session.add(db_recipient)
    session.commit()
    session.refresh(db_recipient)
    return db_recipient

@router.get("/", response_model=List[RecipientRead])
def read_recipients(
    *, 
    session: Session = Depends(get_session), 
    offset: int = 0, 
    limit: int = Query(default=100, le=100),
    group_id: Optional[int] = None,
    q: Optional[str] = None,
    current_user: User = Depends(get_current_user)
):
    query = select(Recipient)
    if group_id:
        query = query.where(Recipient.group_id == group_id)
    
    if q:
        search_term = f"%{q}%"
        query = query.where(
            (col(Recipient.email).like(search_term)) |
            (col(Recipient.name).like(search_term)) |
            (col(Recipient.company).like(search_term)) |
            (col(Recipient.note).like(search_term))
        )

    query = query.offset(offset).limit(limit)
    recipients = session.exec(query).all()
    return recipients

@router.get("/{recipient_id}", response_model=RecipientRead)
def read_recipient(*, session: Session = Depends(get_session), recipient_id: int, current_user: User = Depends(get_current_user)):
    recipient = session.get(Recipient, recipient_id)
    if not recipient:
        raise HTTPException(status_code=404, detail="Recipient not found")
    return recipient

@router.patch("/{recipient_id}", response_model=RecipientRead)
def update_recipient(*, session: Session = Depends(get_session), recipient_id: int, recipient: RecipientUpdate, current_user: User = Depends(get_current_user)):
    db_recipient = session.get(Recipient, recipient_id)
    if not db_recipient:
        raise HTTPException(status_code=404, detail="Recipient not found")
    
    recipient_data = recipient.dict(exclude_unset=True)
    for key, value in recipient_data.items():
        setattr(db_recipient, key, value)
    
    session.add(db_recipient)
    session.commit()
    session.refresh(db_recipient)
    return db_recipient

@router.delete("/{recipient_id}")
def delete_recipient(*, session: Session = Depends(get_session), recipient_id: int):
    recipient = session.get(Recipient, recipient_id)
    if not recipient:
        raise HTTPException(status_code=404, detail="Recipient not found")
    session.delete(recipient)
    session.commit()
    return {"ok": True}

@router.post("/import")
async def import_recipients(
    *, 
    session: Session = Depends(get_session), 
    file: UploadFile = File(...), 
    group_id: Optional[int] = None,
    current_user: User = Depends(get_current_user)
):
    """
    Import recipients from CSV file.
    Expected CSV columns: email, name, company, note
    """
    if not file.filename.endswith('.csv'):
        raise HTTPException(status_code=400, detail="Only CSV files are supported")

    content = await file.read()
    # Handle different encodings (try utf-8, then gbk)
    try:
        decoded_content = content.decode('utf-8')
    except UnicodeDecodeError:
        try:
            decoded_content = content.decode('gbk')
        except UnicodeDecodeError:
            raise HTTPException(status_code=400, detail="Could not decode file. Please save as UTF-8 or GBK.")

    csv_reader = csv.DictReader(io.StringIO(decoded_content))
    
    count = 0
    errors = []
    
    for row in csv_reader:
        email = row.get('email')
        if not email:
            continue # Skip rows without email
        
        # Check duplicate
        existing = session.exec(select(Recipient).where(Recipient.email == email)).first()
        if existing:
            # Optional: Update existing or skip? Let's skip for now or make it configurable.
            # Design doc says "De-duplication", implies we shouldn't create duplicates.
            continue
            
        try:
            recipient = Recipient(
                email=email,
                name=row.get('name'),
                company=row.get('company'),
                note=row.get('note'),
                group_id=group_id,
                status="active"
            )
            session.add(recipient)
            count += 1
        except Exception as e:
            errors.append(f"Error importing {email}: {str(e)}")

    session.commit()
    
    return {"imported_count": count, "errors": errors}
