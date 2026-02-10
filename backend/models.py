from typing import Optional, List, Dict
from datetime import datetime
from sqlmodel import Field, SQLModel, Relationship, JSON

# --- Link Models ---

class GroupTemplateLink(SQLModel, table=True):
    __tablename__ = "group_template_links"
    group_id: Optional[int] = Field(default=None, foreign_key="recipient_groups.id", primary_key=True)
    template_id: Optional[int] = Field(default=None, foreign_key="templates.id", primary_key=True)

# --- Recipient Group ---

class RecipientGroupBase(SQLModel):
    group_name: str = Field(index=True)
    description: Optional[str] = None

class RecipientGroup(RecipientGroupBase, table=True):
    __tablename__ = "recipient_groups"
    id: Optional[int] = Field(default=None, primary_key=True)
    recipients: List["Recipient"] = Relationship(back_populates="group")
    templates: List["Template"] = Relationship(back_populates="groups", link_model=GroupTemplateLink)

class RecipientGroupCreate(RecipientGroupBase):
    template_ids: List[int] = []

class RecipientGroupRead(RecipientGroupBase):
    id: int
    template_ids: List[int] = []
    template_names: List[str] = []

class RecipientGroupUpdate(SQLModel):
    group_name: Optional[str] = None
    description: Optional[str] = None
    template_ids: Optional[List[int]] = None

# --- Recipient ---

class RecipientBase(SQLModel):
    email: str = Field(unique=True, index=True)
    name: Optional[str] = None
    company: Optional[str] = None
    note: Optional[str] = None
    status: str = Field(default="active") # active, inactive
    group_id: Optional[int] = Field(default=None, foreign_key="recipient_groups.id")

class Recipient(RecipientBase, table=True):
    __tablename__ = "recipients"
    id: Optional[int] = Field(default=None, primary_key=True)
    created_at: datetime = Field(default_factory=datetime.utcnow)
    updated_at: datetime = Field(default_factory=datetime.utcnow)
    
    group: Optional[RecipientGroup] = Relationship(back_populates="recipients")

class RecipientCreate(RecipientBase):
    pass

class RecipientRead(RecipientBase):
    id: int
    created_at: datetime
    updated_at: datetime
    group_name: Optional[str] = None # Helper field for API response if needed

class RecipientUpdate(SQLModel):
    email: Optional[str] = None
    name: Optional[str] = None
    company: Optional[str] = None
    note: Optional[str] = None
    status: Optional[str] = None
    group_id: Optional[int] = None

# --- Sender ---

class SenderBase(SQLModel):
    email: str = Field(unique=True, index=True)
    smtp_host: str
    smtp_port: int
    smtp_user: str
    smtp_password: str
    status: str = Field(default="active") # active, inactive

class Sender(SenderBase, table=True):
    __tablename__ = "senders"
    id: Optional[int] = Field(default=None, primary_key=True)

class SenderCreate(SenderBase):
    pass

class SenderRead(SenderBase):
    id: int
    # Don't return password usually, but for admin panel we might need to verify or just mask it
    # smtp_password: str 

class SenderUpdate(SQLModel):
    email: Optional[str] = None
    smtp_host: Optional[str] = None
    smtp_port: Optional[int] = None
    smtp_user: Optional[str] = None
    smtp_password: Optional[str] = None
    status: Optional[str] = None

# --- Template ---

class TemplateBase(SQLModel):
    subject: str
    content: str # HTML content

class Template(TemplateBase, table=True):
    __tablename__ = "templates"
    id: Optional[int] = Field(default=None, primary_key=True)
    groups: List["RecipientGroup"] = Relationship(back_populates="templates", link_model=GroupTemplateLink)

# --- System Settings ---

class SystemSettingBase(SQLModel):
    key: str = Field(index=True, unique=True)
    value: str
    description: Optional[str] = None

class SystemSetting(SystemSettingBase, table=True):
    __tablename__ = "system_settings"
    id: Optional[int] = Field(default=None, primary_key=True)

class SystemSettingCreate(SystemSettingBase):
    pass

class SystemSettingRead(SystemSettingBase):
    id: int

class SystemSettingUpdate(SQLModel):
    value: Optional[str] = None
    description: Optional[str] = None

class TemplateCreate(TemplateBase):
    pass

class TemplateRead(TemplateBase):
    id: int

class TemplateUpdate(SQLModel):
    subject: Optional[str] = None
    content: Optional[str] = None

# --- Task & Logs ---

class SendingTaskBase(SQLModel):
    status: str = Field(default="pending")
    config: Dict = Field(default={}, sa_type=JSON)

class SendingTask(SendingTaskBase, table=True):
    __tablename__ = "sending_tasks"
    id: Optional[int] = Field(default=None, primary_key=True)
    created_at: datetime = Field(default_factory=datetime.utcnow)

class SendingTaskCreate(SendingTaskBase):
    pass

class SendingTaskRead(SendingTaskBase):
    id: int
    created_at: datetime

class SendingLog(SQLModel, table=True):
    __tablename__ = "sending_logs"
    id: Optional[int] = Field(default=None, primary_key=True)
    task_id: int = Field(foreign_key="sending_tasks.id")
    sender_id: int = Field(foreign_key="senders.id")
    recipient_id: int = Field(foreign_key="recipients.id")
    template_id: int = Field(foreign_key="templates.id")
    status: str # success, failure
    error_message: Optional[str] = None
    sent_at: datetime = Field(default_factory=datetime.utcnow)

class SendingLogRead(SQLModel):
    id: int
    task_id: int
    sender_id: int
    recipient_id: int
    template_id: int
    status: str
    error_message: Optional[str] = None
    sent_at: datetime
    sender_email: Optional[str] = None
    recipient_email: Optional[str] = None
    template_subject: Optional[str] = None

class User(SQLModel, table=True):
    __tablename__ = "users"
    id: Optional[int] = Field(default=None, primary_key=True)
    username: str = Field(unique=True, index=True)
    password_hash: str
