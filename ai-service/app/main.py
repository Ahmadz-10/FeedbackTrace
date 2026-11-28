from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.core.config import settings
from app.api.routes.health import router as health_router
from app.api.routes.shared import router as shared_router
from app.task_a.api.routes import router as task_a_router
from app.task_b.api.routes import router as task_b_router
app=FastAPI(title=settings.app_name,version=settings.app_version)
app.add_middleware(CORSMiddleware,allow_origins=settings.cors_origin_list,allow_credentials=False,allow_methods=['GET','POST'],allow_headers=['Content-Type','X-Service-Key'])
app.include_router(health_router)
app.include_router(shared_router)
app.include_router(task_a_router)
app.include_router(task_b_router)