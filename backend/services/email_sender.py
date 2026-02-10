import aiosmtplib
from email.message import EmailMessage
from jinja2 import Template
from models import Sender, Recipient, Template as EmailTemplate
import logging
import re

logger = logging.getLogger(__name__)

class EmailSenderService:
    @staticmethod
    async def verify_sender(sender: Sender) -> tuple[bool, str]:
        """
        Verifies sender credentials by connecting to SMTP server.
        """
        try:
            use_tls = False
            start_tls = False
            
            if sender.smtp_port == 465:
                use_tls = True
            elif sender.smtp_port == 587:
                start_tls = True
                
            # Just try to connect and login
            smtp = aiosmtplib.SMTP(
                hostname=sender.smtp_host,
                port=sender.smtp_port,
                use_tls=use_tls,
                start_tls=start_tls,
                timeout=10
            )
            
            await smtp.connect()
            if start_tls and not use_tls:
                await smtp.starttls()
            
            await smtp.login(sender.smtp_user, sender.smtp_password)
            await smtp.quit()
            
            return True, "Connection successful"
        except Exception as e:
            logger.error(f"Failed to verify sender {sender.email}: {str(e)}")
            return False, str(e)

    @staticmethod
    async def send_email(
        sender: Sender,
        recipient: Recipient,
        template: EmailTemplate,
        base_url: str = None
    ) -> tuple[bool, str]:
        """
        Sends an email.
        Returns: (success: bool, error_message: str)
        """
        try:
            # 1. Prepare Content (Variable Substitution)
            subject_tmpl = Template(template.subject)
            content_tmpl = Template(template.content)
            
            context = {
                "name": recipient.name or "",
                "company": recipient.company or "",
                "email": recipient.email
            }
            
            subject = subject_tmpl.render(context)
            content = content_tmpl.render(context)
            
            # Replace relative URLs with absolute URLs if base_url is provided
            if base_url:
                # Ensure base_url doesn't end with /
                if base_url.endswith("/"):
                    base_url = base_url[:-1]
                
                # Replace src="/api/..." with src="{base_url}/api/..."
                # Handle both src and href, single and double quotes
                def replace_match(match):
                    prefix = match.group(1)
                    quote = match.group(2)
                    path = match.group(3)
                    end_quote = match.group(4)
                    
                    full_path = path if path.startswith("/") else "/" + path
                    return f"{prefix}{quote}{base_url}{full_path}{end_quote}"

                # Pattern: (src=|href=)(['"])((?:/)?(?:api|uploads)/[^'"]*)(['"])
                content = re.sub(r'(src=|href=)([\'"])((?:/)?(?:api|uploads)/[^\'"]*)([\'"])', replace_match, content)
            
            # 2. Construct Message
            message = EmailMessage()
            message["From"] = sender.email
            message["To"] = recipient.email
            message["Subject"] = subject
            message.set_content(content, subtype="html")
            
            # 3. Send via SMTP
            # Note: Depending on provider, might need starttls=True or use_tls=True
            # For simplicity, trying standard port combinations or inferring.
            # Usually 465 is SSL, 587 is STARTTLS.
            
            use_tls = False
            start_tls = False
            
            if sender.smtp_port == 465:
                use_tls = True
            elif sender.smtp_port == 587:
                start_tls = True
                
            await aiosmtplib.send(
                message,
                hostname=sender.smtp_host,
                port=sender.smtp_port,
                username=sender.smtp_user,
                password=sender.smtp_password,
                use_tls=use_tls,
                start_tls=start_tls,
                timeout=30 # seconds
            )
            
            return True, "Success"
            
        except Exception as e:
            logger.error(f"Failed to send email to {recipient.email}: {str(e)}")
            return False, str(e)
