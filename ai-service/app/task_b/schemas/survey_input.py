from pydantic import BaseModel, Field

class SurveyInputSchema(BaseModel):
    response_id: str
    service_attribute: str
    importance_score: int = Field(..., ge=1, le=5, description="Skor kepentingan 1-5")
    performance_score: int = Field(..., ge=1, le=5, description="Skor kinerja 1-5")
    period: str
    source: str