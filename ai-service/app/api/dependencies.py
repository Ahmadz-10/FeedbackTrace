import secrets
from fastapi import Header,HTTPException
from app.core.config import settings
async def require_service_key(supplied_key:str|None=Header(default=None,alias='X-Service-Key')):
    expected=settings.service_api_key
    if expected is None or not expected.get_secret_value(): return
    if supplied_key is None or not secrets.compare_digest(supplied_key,expected.get_secret_value()):
        raise HTTPException(status_code=401,detail='Missing or invalid service API key.')
