from typing import List, Dict, Any

class QuadrantClassifier:
    @staticmethod
    def classify_quadrants(ipa_results: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Mengklasifikasikan atribut ke dalam 4 Kuadran IPA:
        - Kuadran I: Keep Up the Good Work (Importance Tinggi, Performance Tinggi)
        - Kuadran II: Concentrate Here (Importance Tinggi, Performance Rendah)
        - Kuadran III: Low Priority (Importance Rendah, Performance Rendah)
        - Kuadran IV: Possible Overkill (Importance Rendah, Performance Tinggi)
        """
        if not ipa_results:
            return []

        # Hitung threshold (rata-rata keseluruhan dari seluruh atribut)
        total_imp = sum(item["mean_importance"] for item in ipa_results)
        total_perf = sum(item["mean_performance"] for item in ipa_results)
        
        avg_importance_threshold = total_imp / len(ipa_results)
        avg_performance_threshold = total_perf / len(ipa_results)

        classified_results = []
        for item in ipa_results:
            imp = item["mean_importance"]
            perf = item["mean_performance"]

            # Logika penentuan kuadran
            if imp >= avg_importance_threshold and perf >= avg_performance_threshold:
                quadrant = "I - Keep Up the Good Work"
            elif imp >= avg_importance_threshold and perf < avg_performance_threshold:
                quadrant = "II - Concentrate Here"
            elif imp < avg_importance_threshold and perf < avg_performance_threshold:
                quadrant = "III - Low Priority"
            else:
                quadrant = "IV - Possible Overkill"

            item_copy = item.copy()
            item_copy["quadrant"] = quadrant
            classified_results.append(item_copy)

        return classified_results