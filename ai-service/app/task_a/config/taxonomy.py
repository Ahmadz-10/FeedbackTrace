"""Taksonomi aspek awal Task A (10 kode). Aspek di luar daftar ini hanya boleh
muncul sebagai USULAN (is_proposed_aspect=true), tidak pernah dibuat diam-diam."""
from dataclasses import dataclass

TAXONOMY_VERSION = "1.0"


@dataclass(frozen=True)
class Aspect:
    code: str
    name: str
    description: str


ASPECTS: tuple[Aspect, ...] = (
    Aspect("procedure_clarity", "Kejelasan prosedur",
           "Seberapa jelas langkah/alur layanan dijelaskan dan mudah diikuti."),
    Aspect("information_consistency", "Konsistensi informasi",
           "Kesesuaian informasi antar petugas, kanal, atau dokumen."),
    Aspect("service_speed", "Kecepatan layanan",
           "Lama proses layanan atau waktu tunggu sampai selesai."),
    Aspect("staff_responsiveness", "Daya tanggap petugas",
           "Kecepatan dan kesediaan petugas menanggapi pertanyaan atau permintaan."),
    Aspect("staff_professionalism", "Profesionalisme petugas",
           "Sikap, keramahan, dan kompetensi petugas dalam melayani."),
    Aspect("service_accessibility", "Aksesibilitas layanan",
           "Kemudahan menjangkau layanan: lokasi, jam, kanal, dan akses bagi mahasiswa."),
    Aspect("digital_service_usability", "Kemudahan layanan digital",
           "Kemudahan dan keandalan sistem/portal/aplikasi layanan."),
    Aspect("requirement_clarity", "Kejelasan persyaratan",
           "Kejelasan dokumen dan syarat yang harus dipenuhi mahasiswa."),
    Aspect("service_availability", "Ketersediaan layanan",
           "Apakah layanan tersedia saat dibutuhkan (jadwal, kuota, petugas)."),
    Aspect("other", "Lainnya",
           "Aspek relevan yang tidak termasuk sembilan kode lain."),
)

ASPECT_CODES: tuple[str, ...] = tuple(a.code for a in ASPECTS)
_BY_CODE = {a.code: a for a in ASPECTS}


def is_valid_aspect(code: str) -> bool:
    return code in _BY_CODE


def get_aspect(code: str) -> Aspect:
    return _BY_CODE[code]
