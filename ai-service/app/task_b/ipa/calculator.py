from typing import List, Dict, Any

class IPACalculator:
    @staticmethod
    def calculate_ipa(responses: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Menghitung rata-rata Importance, Performance, dan Gap per atribut layanan.
        Rumus:
        - I_i = Total Importance / Jumlah Respon Valid
        - P_i = Total Performance / Jumlah Respon Valid
        - G_i = P_i - I_i
        """
        attribute_data: Dict[str, Dict[str, Any]] = {}

        # 1. Agregasi skor berdasarkan atribut layanan
        for resp in responses:
            attr = resp.get("service_attribute")
            imp = float(resp.get("importance_score", 0))
            perf = float(resp.get("performance_score", 0))

            if attr not in attribute_data:
                attribute_data[attr] = {
                    "service_attribute": attr,
                    "total_importance": 0.0,
                    "total_performance": 0.0,
                    "response_count": 0
                }

            attribute_data[attr]["total_importance"] += imp
            attribute_data[attr]["total_performance"] += perf
            attribute_data[attr]["response_count"] += 1

        # 2. Hitung Rata-rata & Gap
        results = []
        for attr, data in attribute_data.items():
            count = data["response_count"]
            mean_importance = data["total_importance"] / count if count > 0 else 0.0
            mean_performance = data["total_performance"] / count if count > 0 else 0.0
            gap = mean_performance - mean_importance

            results.append({
                "service_attribute": attr,
                "mean_importance": round(mean_importance, 2),
                "mean_performance": round(mean_performance, 2),
                "gap": round(gap, 2),
                "response_count": count
            })

        return results