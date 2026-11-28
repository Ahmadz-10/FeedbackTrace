from fastapi import APIRouter, HTTPException, status
from typing import List, Dict, Any
from app.task_b.schemas.task_a_input import ApprovedFindingSchema
from app.task_b.schemas.survey_input import SurveyInputSchema
from app.task_b.services.survey_validator import SurveyValidator
from app.task_b.ipa.calculator import IPACalculator
from app.task_b.ipa.quadrant_classifier import QuadrantClassifier

router = APIRouter(prefix="/task-b", tags=["Task B - Decision Intelligence"])

@router.get("/health")
def task_b_health():
    """Endpoint health check khusus modul Task B."""
    return {"status": "active", "module": "Task B - Decision Intelligence"}

@router.post("/validate-findings", status_code=status.HTTP_200_OK)
def validate_approved_findings(findings: List[ApprovedFindingSchema]):
    """Memvalidasi bahwa temuan Task A yang dikirimkan berstatus approved."""
    for finding in findings:
        if finding.review_status.lower() != "approved":
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Finding ID {finding.finding_id} ditolak. Task B hanya memproses temuan berstatus 'approved'."
            )
    return {
        "status": "success",
        "message": f"Berhasil memvalidasi {len(findings)} approved findings dari Task A."
    }

@router.post("/calculate-ipa", status_code=status.HTTP_200_OK)
def calculate_importance_performance_analysis(responses: List[Dict[str, Any]]):
    """
    Endpoint untuk memproses data survei mentah:
    1. Memvalidasi baris data (skor 1-5, field kosong).
    2. Menghitung rata-rata Importance, Performance, dan Gap (IPACalculator).
    3. Mengklasifikasikan hasil ke dalam 4 Kuadran IPA (QuadrantClassifier).
    """
    # 1. Validasi baris data survei
    validation_result = SurveyValidator.validate_rows(responses)
    
    if validation_result["valid_count"] == 0:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail={
                "message": "Tidak ada data survei yang valid untuk diproses.",
                "errors": validation_result["errors"]
            }
        )

    # 2. Proses perhitungan IPA dari baris yang valid
    ipa_raw_results = IPACalculator.calculate_ipa(validation_result["valid_rows"])

    # 3. Klasifikasikan ke dalam 4 Kuadran
    classified_results = QuadrantClassifier.classify_quadrants(ipa_raw_results)

    return {
        "status": "success",
        "summary": {
            "total_submitted": validation_result["total_checked"],
            "valid_records": validation_result["valid_count"],
            "invalid_records": validation_result["error_count"]
        },
        "ipa_analysis_results": classified_results,
        "validation_errors": validation_result["errors"] if validation_result["error_count"] > 0 else []
    }