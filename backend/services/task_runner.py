import asyncio
import logging
import random
from typing import List, Optional
from sqlmodel import Session, select, func
from database import engine
from models import SendingTask, Recipient, Sender, Template, SendingLog, SystemSetting, RecipientGroup
from services.email_sender import EmailSenderService

logger = logging.getLogger(__name__)

class TaskRunnerService:
    def __init__(self):
        self.is_running = False
        self._task = None

    def start(self):
        if not self.is_running:
            self.is_running = True
            self._task = asyncio.create_task(self.run_loop())
            logger.info("TaskRunner started")

    def stop(self):
        self.is_running = False
        if self._task:
            self._task.cancel()
            logger.info("TaskRunner stopped")

    async def run_loop(self):
        while self.is_running:
            try:
                await self.process_tasks()
            except Exception as e:
                logger.error(f"Error in TaskRunner loop: {e}")
            
            await asyncio.sleep(5) # Check for new tasks every 5 seconds

    async def process_tasks(self):
        # We need to manually manage session here since we are in a background loop
        with Session(engine) as session:
            # Find running tasks
            tasks = session.exec(
                select(SendingTask).where(SendingTask.status == "running")
            ).all()

            for task in tasks:
                await self.execute_task_step(session, task)

    async def execute_task_step(self, session: Session, task: SendingTask):
        # 1. Parse Config
        config = task.config or {}
        
        # Support old 'interval' or new 'interval_min/max'
        base_interval = float(config.get("interval", 5.0))
        interval_min = float(config.get("interval_min", base_interval))
        interval_max = float(config.get("interval_max", base_interval))
        
        # Ensure min <= max
        if interval_min > interval_max:
            interval_min, interval_max = interval_max, interval_min
            
        concurrency = int(config.get("concurrency", 1))
        target_group_ids = config.get("group_ids", [])
        
        # 2. Select Recipient
        # Strategy: Find recipients who haven't received an email for this task yet.
        # This requires checking logs. For performance, this might be slow for huge lists.
        # Optimization: We could store a 'last_processed_id' in task config or similar, 
        # but random selection is requested.
        # Let's use a NOT IN subquery for simplicity now, but be aware of perf.
        
        # Get IDs already sent for this task
        sent_recipient_ids_stmt = select(SendingLog.recipient_id).where(
            SendingLog.task_id == task.id,
            SendingLog.status == "success" # Retrying failures? Optional. Let's assume we only skip successes.
        )
        
        query = select(Recipient).where(
            Recipient.status == "active",
            func.coalesce(Recipient.id).not_in(sent_recipient_ids_stmt)
        )
        
        if target_group_ids:
            query = query.where(Recipient.group_id.in_(target_group_ids))
            
        # Select N recipients based on concurrency
        # Note: 'limit' in subquery is tricky in some SQL dialects, but here it's main query.
        recipients_to_process = session.exec(query.limit(concurrency)).all()
        
        if not recipients_to_process:
            # Task complete?
            # Double check if any pending recipients exist. If not, mark completed.
            # (The above query returns empty if all done)
            task.status = "completed"
            session.add(task)
            session.commit()
            logger.info(f"Task {task.id} completed")
            return

        # Get System Base URL
        base_url_setting = session.exec(select(SystemSetting).where(SystemSetting.key == "system_base_url")).first()
        base_url = base_url_setting.value if base_url_setting else "http://localhost:18088"
        if not base_url_setting:
            logger.warning("System Base URL not configured. Using default: http://localhost:18088")

        # 3. Process Batch
        send_tasks = []
        for recipient in recipients_to_process:
            send_tasks.append(self.process_single_email(session, task, recipient, base_url=base_url))
            
        # Run batch concurrently
        await asyncio.gather(*send_tasks)
        
        # Wait for interval
        # Use random interval between min and max
        sleep_time = random.uniform(interval_min, interval_max)
        logger.info(f"Task {task.id}: Processed batch. Sleeping for {sleep_time:.2f}s")
        await asyncio.sleep(sleep_time)

    async def process_single_email(self, session: Session, task: SendingTask, recipient: Recipient, base_url: str = None):
        # Re-check task status in case it was paused mid-batch (optional optimization)
        
        # 4. Select Sender
        active_senders = session.exec(select(Sender).where(Sender.status == "active")).all()
        if not active_senders:
            logger.error("No active senders available")
            return
        
        sender = random.choice(active_senders)
        
        # 5. Select Template
        # If template is bound to recipient's group, prioritize it.
        template = None
        if recipient.group_id:
             # Check for bound templates
             # Logic: Recipient.group.templates (via Many-to-Many)
             recipient_group = session.get(RecipientGroup, recipient.group_id)
             if recipient_group and recipient_group.templates:
                 template = random.choice(recipient_group.templates)
        
        if not template:
            # Fallback to any template if no specific binding found or group has no templates
            # NOTE: Logic here might need adjustment based on user requirement. 
            # If the user wants STRICT binding (only send if group has bound templates), we should return here.
            # But the previous logic was "Fallback to unbound". 
            # New logic: Fallback to ALL templates? Or only those NOT bound to other groups?
            # User said "同一个模板可以被绑定在多个分组中".
            # Simplest fallback: Pick any template.
            all_templates = session.exec(select(Template)).all()
            if not all_templates:
                logger.error("No templates available")
                return
            template = random.choice(all_templates)

        # 6. Send
        success, message = await EmailSenderService.send_email(sender, recipient, template, base_url=base_url)
        
        # 7. Log
        log = SendingLog(
            task_id=task.id,
            sender_id=sender.id,
            recipient_id=recipient.id,
            template_id=template.id,
            status="success" if success else "failure",
            error_message=message if not success else None
        )
        session.add(log)
        session.commit()

task_runner = TaskRunnerService()
