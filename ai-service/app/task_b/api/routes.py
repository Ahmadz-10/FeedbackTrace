from fastapi import APIRouter, HTTPException, status
from typing import List, Dict, Any

from app.task_b.schemas.task_a_input import ApprovedFindingSchema
from app.task_b.services.survey_validator import SurveyValidator
from app.task_b.ipa.calculator import IPACalculator
from app.task_b.ipa.quadrant_classifier import QuadrantClassifier

router = APIRouter(prefix="/api/v1/task-b", tags=["task-b"])


@router.get("/health")
def task_b_health():
    return {"status": "ok", "module": "task_b"}


@router.post("/validate-findings", status_code=status.HTTP_200_OK)
def validate_approved_findings(findings: List[ApprovedFindingSchema]):
    for finding in findings:
        if finding.review_status.value.lower() != "approved":
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Finding ID {finding.finding_id} rejected. Task B only processes approved findings.",
            )
    return {
        "status": "success",
        "message": f"Successfully validated {len(findings)} approved findings from Task A.",
    }


@router.post("/calculate-ipa", status_code=status.HTTP_200_OK)
def calculate_importance_performance_analysis(responses: List[Dict[str, Any]]):
    validation_result = SurveyValidator.validate_rows(responses)
    if validation_result["valid_count"] == 0:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail={
                "message": "No valid survey data was submitted for analysis.",
                "errors": validation_result["errors"],
            },
        )

    raw_results = IPACalculator.calculate_ipa(validation_result["valid_rows"])
    classified_results = QuadrantClassifier.classify_quadrants(raw_results)
    return {
        "status": "success",
        "summary": {
            "total_submitted": validation_result["total_checked"],
            "valid_records": validation_result["valid_count"],
            "invalid_records": validation_result["error_count"],
        },
        "ipa_analysis_results": classified_results,
        "validation_errors": validation_result["errors"] if validation_result["error_count"] else [],
    }
