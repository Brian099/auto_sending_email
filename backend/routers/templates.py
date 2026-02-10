from fastapi import APIRouter, Depends, HTTPException, Query
from sqlmodel import Session, select
from typing import List
from database import get_session
from models import Template, TemplateCreate, TemplateRead, TemplateUpdate

router = APIRouter(prefix="/templates", tags=["templates"])

@router.post("/", response_model=TemplateRead)
def create_template(*, session: Session = Depends(get_session), template: TemplateCreate):
    db_template = Template.from_orm(template)
    session.add(db_template)
    session.commit()
    session.refresh(db_template)
    return db_template

@router.get("/", response_model=List[TemplateRead])
def read_templates(*, session: Session = Depends(get_session), offset: int = 0, limit: int = Query(default=100, le=100)):
    templates = session.exec(select(Template).offset(offset).limit(limit)).all()
    return templates

@router.get("/{template_id}", response_model=TemplateRead)
def read_template(*, session: Session = Depends(get_session), template_id: int):
    template = session.get(Template, template_id)
    if not template:
        raise HTTPException(status_code=404, detail="Template not found")
    return template

@router.patch("/{template_id}", response_model=TemplateRead)
def update_template(*, session: Session = Depends(get_session), template_id: int, template: TemplateUpdate):
    db_template = session.get(Template, template_id)
    if not db_template:
        raise HTTPException(status_code=404, detail="Template not found")
    
    template_data = template.dict(exclude_unset=True)
    for key, value in template_data.items():
        setattr(db_template, key, value)
    
    session.add(db_template)
    session.commit()
    session.refresh(db_template)
    return db_template

@router.delete("/{template_id}")
def delete_template(*, session: Session = Depends(get_session), template_id: int):
    template = session.get(Template, template_id)
    if not template:
        raise HTTPException(status_code=404, detail="Template not found")
    session.delete(template)
    session.commit()
    return {"ok": True}
