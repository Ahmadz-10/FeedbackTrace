from typing import List, Dict, Any

class SurveyValidator:
    @staticmethod
    def validate_rows(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Memvalidasi daftar baris data survei importance-performance.
        Memeriksa rentang skor (1-5), field kosong, dan integritas dasar.
        """
        valid_rows = []
        errors = []

        for index, row in enumerate(rows, start=1):
            row_errors = []
            
            # Cek field wajib
            response_id = row.get("response_id")
            service_attribute = row.get("service_attribute")
            importance = row.get("importance_score")
            performance = row.get("performance_score")

            if not response_id:
                row_errors.append("response_id tidak boleh kosong")
            if not service_attribute:
                row_errors.append("service_attribute tidak boleh kosong")
            
            # Validasi skor importance (rentang 1 - 5)
            try:
                imp_val = int(importance)
                if not (1 <= imp_val <= 5):
                    row_errors.append("importance_score harus di antara 1 dan 5")
            except (TypeError, ValueError):
                row_errors.append("importance_score tidak valid (harus angka)")

            # Validasi skor performance (rentang 1 - 5)
            try:
                perf_val = int(performance)
                if not (1 <= perf_val <= 5):
                    row_errors.append("performance_score harus di antara 1 dan 5")
            except (TypeError, ValueError):
                row_errors.append("performance_score tidak valid (harus angka)")

            if row_errors:
                errors.append({"row_number": index, "data": row, "errors": row_errors})
            else:
                valid_rows.append(row)

        return {
            "total_checked": len(rows),
            "valid_count": len(valid_rows),
            "error_count": len(errors),
            "valid_rows": valid_rows,
            "errors": errors
        }