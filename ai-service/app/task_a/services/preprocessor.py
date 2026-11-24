"""Pembersihan format tanpa mengubah negasi, slang, kapitalisasi, atau teks asli."""
from dataclasses import dataclass
import unicodedata

_INVISIBLE = {"\u200b", "\u200c", "\u200d", "\u2060", "\ufeff", "\u00ad"}

@dataclass(frozen=True)
class PreprocessResult:
    original: str
    normalized: str
    index_map: tuple[int, ...]
    def to_original_span(self, start: int, end: int) -> tuple[int, int]:
        if not (0 <= start < end <= len(self.normalized)):
            raise ValueError("Rentang di luar normalized_text.")
        return self.index_map[start], self.index_map[end - 1] + 1

def preprocess(original: str) -> PreprocessResult:
    chars: list[str] = []
    idx: list[int] = []
    pending_space_at: int | None = None
    for i, ch in enumerate(original):
        if ch in _INVISIBLE:
            continue
        if ch.isspace() or unicodedata.category(ch) == "Cc":
            if chars and pending_space_at is None:
                pending_space_at = i
            continue
        if pending_space_at is not None:
            chars.append(" ")
            idx.append(pending_space_at)
            pending_space_at = None
        chars.append(ch)
        idx.append(i)
    return PreprocessResult(original, "".join(chars), tuple(idx))
