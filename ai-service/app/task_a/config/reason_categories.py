"""11 kategori alasan awal Task A.

CATATAN: daftar ini disusun sebagai titik awal yang konsisten dengan 10 aspek
pada taxonomy.py. Samakan nama/jumlahnya dengan proposal Task A sebelum dipakai
di prompt (Segment 04). Cukup ubah file ini; modul lain membaca dari sini.
"""
from dataclasses import dataclass

from app.task_a.config.labels import ReasonType

REASON_CATEGORY_VERSION = "1.0"

_SAT = ReasonType.SATISFACTION_DRIVER
_DIS = ReasonType.DISSATISFACTION_DRIVER
_NEU = ReasonType.NEUTRAL_OBSERVATION


@dataclass(frozen=True)
class ReasonCategory:
    code: str
    name: str
    description: str
    allowed_types: tuple[ReasonType, ...]


REASON_CATEGORIES: tuple[ReasonCategory, ...] = (
    ReasonCategory("unclear_instructions", "Instruksi tidak jelas",
                   "Langkah, prosedur, atau persyaratan sulit dipahami atau ambigu.", (_DIS,)),
    ReasonCategory("conflicting_information", "Informasi bertentangan",
                   "Informasi berbeda antar petugas, kanal, atau dokumen.", (_DIS,)),
    ReasonCategory("long_waiting_time", "Waktu tunggu lama",
                   "Proses atau antrean memakan waktu lebih lama dari yang wajar.", (_DIS,)),
    ReasonCategory("slow_or_no_response", "Respons lambat atau tidak ada",
                   "Pertanyaan atau permintaan lambat ditanggapi atau tidak dijawab.", (_DIS,)),
    ReasonCategory("unhelpful_or_unprofessional_staff", "Petugas kurang membantu/profesional",
                   "Sikap petugas tidak ramah, tidak peduli, atau kurang kompeten.", (_DIS,)),
    ReasonCategory("hard_to_access", "Sulit diakses",
                   "Layanan sulit dijangkau karena lokasi, jam, kanal, atau tidak tersedia.", (_DIS,)),
    ReasonCategory("system_technical_issue", "Masalah teknis sistem",
                   "Sistem/portal error, lambat, atau sulit digunakan.", (_DIS,)),
    ReasonCategory("clear_guidance", "Panduan jelas",
                   "Instruksi, prosedur, atau informasi mudah dipahami dan membantu.", (_SAT,)),
    ReasonCategory("fast_service", "Layanan cepat",
                   "Proses selesai cepat atau tanggapan diberikan tepat waktu.", (_SAT,)),
    ReasonCategory("helpful_professional_staff", "Petugas membantu dan profesional",
                   "Petugas ramah, sigap, dan kompeten.", (_SAT,)),
    ReasonCategory("other_reason", "Alasan lain",
                   "Alasan yang tidak masuk kategori lain, atau pengamatan netral.", (_SAT, _DIS, _NEU)),
)

REASON_CATEGORY_CODES: tuple[str, ...] = tuple(c.code for c in REASON_CATEGORIES)
_BY_CODE = {c.code: c for c in REASON_CATEGORIES}


def is_valid_reason_category(code: str) -> bool:
    return code in _BY_CODE


def get_reason_category(code: str) -> ReasonCategory:
    return _BY_CODE[code]


def is_type_allowed(code: str, reason_type: ReasonType) -> bool:
    """True bila kategori `code` boleh dipakai dengan `reason_type` tersebut."""
    cat = _BY_CODE.get(code)
    return cat is not None and reason_type in cat.allowed_types
