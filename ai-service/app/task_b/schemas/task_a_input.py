from pydantic import BaseModel, Field
from typing import Dict, List, Optional

class ApprovedFindingSchema(BaseModel):
    finding_id: str
    aspect_code: str
    aspect_name: str
    issue_title: str
    issue_summary: str
    finding_type: str
    sentiment_distribution: Dict[str, float]
    supporting_feedback_count: int
    supporting_evidence: List[str]
    review_status: str = Field(..., description="Status review harus bernilai 'approved'")