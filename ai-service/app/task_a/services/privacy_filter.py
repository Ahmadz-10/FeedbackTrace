"""Redaksi data pribadi sebelum komentar dikirim ke layanan LLM."""
from __future__ import annotations
import re
from dataclasses import dataclass
from typing import Iterable

_HONORIFIC = r"(?:Bapak|Pak|Ibu|Bu|Mas|Mbak|Kak|Bang|Dr|Prof|Drs|Dra|Ir)\.?"
_NAME_WORD = r"[A-Z][A-Za-z'’\-]{1,}"
_ROLE_WORDS = frozenset({
    "dekan", "rektor", "kepala", "ketua", "wakil", "sekretaris", "koordinator", "staf", "staff",
    "petugas", "admin", "dosen", "layanan", "pelayanan", "bagian", "prodi", "jurusan", "fakultas",
    "kaprodi", "kajur", "tu", "akademik", "keuangan", "perpustakaan",
})
_PATTERNS: list[tuple[str, re.Pattern[str], int]] = [
    ("EMAIL", re.compile(r"[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}"), 0),
    ("URL", re.compile(r"(?:https?://|www\.)[^\s<>\"']+", re.I), 0),
    ("PHONE", re.compile(r"(?<![\w])(?:\+?62|0)[\s.\-]?8\d{1,3}(?:[\s.\-]?\d{2,5}){2,3}(?!\d)"), 0),
    ("PHONE", re.compile(r"(?<!\d)0\d{2,3}[\s.\-]\d{6,8}(?!\d)"), 0),
    ("NATIONAL_ID", re.compile(r"(?<!\d)\d{16}(?!\d)"), 0),
    ("STUDENT_ID", re.compile(r"\b(?:NIM|NPM|NRP)\s*[:.\-]?\s*((?=[A-Za-z0-9.\-/]*\d)[A-Za-z0-9][A-Za-z0-9.\-/]{3,23}[A-Za-z0-9])", re.I), 1),
    ("STUDENT_ID", re.compile(r"(?<![\d.,])(?<!rp)(?<!rp\s)\d{8,15}(?![\d])", re.I), 0),
    ("NAME", re.compile(r"\b(?i:nama(?:\s+saya|\s+lengkap)?|my\s+name\s+is|name)\s*[:\-]?\s+"
                        rf"({_NAME_WORD}(?:\s+{_NAME_WORD}){{0,2}})"), 1),
    ("NAME", re.compile(rf"\b{_HONORIFIC}\s+({_NAME_WORD}(?:\s+{_NAME_WORD}){{0,2}})"), 1),
]

@dataclass(frozen=True)
class Redaction:
    kind: str
    src_start: int
    src_end: int
    out_start: int
    out_end: int

@dataclass(frozen=True)
class RedactionResult:
    source: str
    text: str
    redactions: tuple[Redaction, ...]
    @property
    def kinds(self) -> list[str]:
        return sorted({r.kind for r in self.redactions})
    @property
    def redacted(self) -> bool:
        return bool(self.redactions)
    def to_source_span(self, start: int, end: int) -> tuple[int, int]:
        if not (0 <= start < end <= len(self.text)):
            raise ValueError("Rentang di luar teks hasil redaksi.")
        return self._pos(start, False), self._pos(end, True)
    def _pos(self, pos: int, is_end: bool) -> int:
        shift = 0
        for r in self.redactions:
            if is_end:
                if pos <= r.out_start:
                    break
                if pos <= r.out_end:
                    return r.src_end
            else:
                if pos < r.out_start:
                    break
                if pos < r.out_end:
                    return r.src_start
            shift = r.src_end - r.out_end
        return pos + shift

class PrivacyFilter:
    def __init__(self, extra_terms: Iterable[str] = ()) -> None:
        terms = sorted({t.strip() for t in extra_terms if t and t.strip()}, key=len, reverse=True)
        self._extra = re.compile("|".join(rf"(?<!\w){re.escape(t)}(?!\w)" for t in terms), re.I) if terms else None
    def redact(self, text: str) -> RedactionResult:
        spans: list[tuple[int, int, str]] = []
        for kind, pattern, group in _PATTERNS:
            for m in pattern.finditer(text):
                if kind == "NAME" and self._is_not_a_name(m.group(group)):
                    continue
                spans.append((m.start(group), m.end(group), kind))
        if self._extra:
            spans.extend((m.start(), m.end(), "NAME") for m in self._extra.finditer(text))
        spans.sort(key=lambda s: (s[0], -(s[1] - s[0])))
        chosen: list[tuple[int, int, str]] = []
        for span in spans:
            if not chosen or span[0] >= chosen[-1][1]:
                chosen.append(span)
        output: list[str] = []
        redactions: list[Redaction] = []
        cursor = 0
        out_len = 0
        for start, end, kind in chosen:
            keep = text[cursor:start]
            output.append(keep)
            out_len += len(keep)
            placeholder = f"[{kind}]"
            redactions.append(Redaction(kind, start, end, out_len, out_len + len(placeholder)))
            output.append(placeholder)
            out_len += len(placeholder)
            cursor = end
        output.append(text[cursor:])
        return RedactionResult(text, "".join(output), tuple(redactions))
    @staticmethod
    def _is_not_a_name(candidate: str) -> bool:
        return candidate.split()[0].lower().strip(".'’-'") in _ROLE_WORDS

_DEFAULT_FILTER = PrivacyFilter()
def redact_pii(text: str) -> RedactionResult:
    return _DEFAULT_FILTER.redact(text)
def contains_pii(text: str) -> bool:
    return _DEFAULT_FILTER.redact(text).redacted
