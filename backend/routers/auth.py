from fastapi import APIRouter, Depends, HTTPException, Header
from sqlmodel import Session, select
from database import get_session, get_password_hash
from models import User, UserSession, UserLogin, UserRead, UserPasswordUpdate
from datetime import datetime, timedelta
import secrets

router = APIRouter(prefix="/auth", tags=["auth"])

# 依赖函数：获取当前用户
async def get_current_user(x_token: str = Header(None), session: Session = Depends(get_session)):
    if not x_token:
        raise HTTPException(status_code=401, detail="Authentication token missing")
    
    # 查找 Session
    user_session = session.exec(select(UserSession).where(UserSession.token == x_token)).first()
    if not user_session:
        raise HTTPException(status_code=401, detail="Invalid token")
    
    # 检查过期
    if user_session.expires_at < datetime.utcnow():
        session.delete(user_session)
        session.commit()
        raise HTTPException(status_code=401, detail="Token expired")
    
    # 获取用户
    user = session.get(User, user_session.user_id)
    if not user:
        raise HTTPException(status_code=401, detail="User not found")
        
    return user

@router.post("/login")
def login(login_data: UserLogin, session: Session = Depends(get_session)):
    # 1. 验证用户
    user = session.exec(select(User).where(User.username == login_data.username)).first()
    if not user:
        raise HTTPException(status_code=401, detail="Incorrect username or password")
        
    hashed_password = get_password_hash(login_data.password)
    if user.password_hash != hashed_password:
        raise HTTPException(status_code=401, detail="Incorrect username or password")
    
    # 2. 生成 Token
    token = secrets.token_hex(32)
    expires_at = datetime.utcnow() + timedelta(days=7) # 7天过期
    
    # 3. 创建 Session
    # 清理该用户旧的 session (可选，单点登录)
    # old_sessions = session.exec(select(UserSession).where(UserSession.user_id == user.id)).all()
    # for s in old_sessions:
    #    session.delete(s)
       
    new_session = UserSession(
        user_id=user.id,
        token=token,
        expires_at=expires_at
    )
    session.add(new_session)
    session.commit()
    session.refresh(new_session)
    
    return {"token": token, "username": user.username, "expires_at": expires_at}

@router.post("/logout")
def logout(x_token: str = Header(None), session: Session = Depends(get_session)):
    if x_token:
        user_session = session.exec(select(UserSession).where(UserSession.token == x_token)).first()
        if user_session:
            session.delete(user_session)
            session.commit()
    return {"status": "success"}

@router.get("/me", response_model=UserRead)
def get_me(user: User = Depends(get_current_user)):
    return user

@router.post("/change-password")
def change_password(
    password_data: UserPasswordUpdate,
    session: Session = Depends(get_session),
    current_user: User = Depends(get_current_user)
):
    # Verify old password
    if get_password_hash(password_data.old_password) != current_user.password_hash:
        raise HTTPException(status_code=400, detail="Incorrect old password")
    
    # Update password
    current_user.password_hash = get_password_hash(password_data.new_password)
    session.add(current_user)
    session.commit()
    session.refresh(current_user)
    
    return {"status": "success", "message": "Password updated successfully"}
