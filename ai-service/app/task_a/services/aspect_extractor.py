"""Membangun prompt dan menguraikan bagian aspek dari keluaran LLM."""
from __future__ import annotations
import hashlib, json, re, secrets
from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from typing import Any
from app.task_a.config import reason_categories as rc, taxonomy
from app.task_a.config.versions import PROMPT_VERSION
from app.task_a.services.analysis_types import LLMOutputError, ParseIssue

_PROMPT_DIR = Path(__file__).resolve().parents[1] / "prompts"
MAX_LABEL_LENGTH = 60

@lru_cache
def load_prompt_template(version: str = PROMPT_VERSION) -> str:
    path = _PROMPT_DIR / f"{version}.txt"
    if not path.is_file():
        raise FileNotFoundError(f"Prompt '{version}' tidak ditemukan di {path}")
    return path.read_text(encoding="utf-8")

def prompt_fingerprint(version: str = PROMPT_VERSION) -> str:
    return hashlib.sha256(load_prompt_template(version).encode("utf-8")).hexdigest()[:12]

def render_system_prompt(*, max_aspects: int, allow_proposed: bool, version: str = PROMPT_VERSION) -> str:
    aspects = "\n".join(f"- {a.code}: {a.name}. {a.description}" for a in taxonomy.ASPECTS)
    reasons = "\n".join(f"  - {c.code} ({'/'.join(t.value for t in c.allowed_types)}): {c.description}" for c in rc.REASON_CATEGORIES)
    rule = "" if allow_proposed else 'Proposing new aspects is DISABLED: never set "is_proposed_aspect" to true; use "other" with "proposed_aspect_label": null for anything that fits no code.'
    return (load_prompt_template(version).replace("{{ASPECTS}}", aspects)
        .replace("{{REASON_CATEGORIES}}", reasons).replace("{{PROPOSAL_RULE}}", rule)
        .replace("{{MAX_MENTIONS}}", str(max_aspects)))

def _unique_delimiter(text: str) -> str:
    while True:
        candidate = f"=====COMMENT-{secrets.token_hex(4)}====="
        if candidate not in text:
            return candidate

def build_messages(analysis_text: str, *, max_aspects: int, allow_proposed: bool,
                   language_hint: str | None = None) -> list[dict[str, str]]:
    delimiter = _unique_delimiter(analysis_text)
    hint = f"Language hint: {language_hint}\n" if language_hint else ""
    user = f"{hint}Comment (between the two delimiter lines):\n{delimiter}\n{analysis_text}\n{delimiter}"
    return [{"role": "system", "content": render_system_prompt(max_aspects=max_aspects, allow_proposed=allow_proposed)},
            {"role": "user", "content": user}]

def parse_llm_json(content: str) -> list[Any]:
    text = (content or "").strip()
    candidates = []
    fence = re.search(r"```(?:json)?\s*(.*?)```", text, re.S | re.I)
    if fence:
        candidates.append(fence.group(1).strip())
    candidates.append(text)
    first, last = text.find("{"), text.rfind("}")
    if first != -1 and last > first:
        candidates.append(text[first:last + 1])
    saw_json = False
    for candidate in candidates:
        try:
            data = json.loads(candidate)
        except (ValueError, TypeError):
            continue
        saw_json = True
        if isinstance(data, dict) and isinstance(data.get("mentions"), list):
            return data["mentions"]
        if isinstance(data, list):
            return data
    raise LLMOutputError("JSON tidak memiliki daftar 'mentions'." if saw_json else "Keluaran model bukan JSON yang valid.")

@dataclass(frozen=True)
class AspectResolution:
    aspect_code: str | None
    is_proposed: bool
    proposed_label: str | None
    issues: list[ParseIssue]

def normalize_code(value: Any) -> str:
    if not isinstance(value, str):
        return ""
    return re.sub(r"[\s\-]+", "_", value.strip().lower())

def _truthy(value: Any) -> bool:
    return value is True or (isinstance(value, str) and value.strip().lower() == "true")

def resolve_aspect(raw: dict, *, allow_proposed: bool, index: int) -> AspectResolution:
    issues = []
    code = normalize_code(raw.get("aspect_code"))
    flagged = _truthy(raw.get("is_proposed_aspect"))
    label = normalize_code(raw.get("proposed_aspect_label"))[:MAX_LABEL_LENGTH] or None
    if not code:
        return AspectResolution(None, False, None, [ParseIssue("missing_aspect_code", "aspect_code hilang atau bukan teks.", index)])
    if taxonomy.is_valid_aspect(code) and code != "other":
        if flagged or label:
            issues.append(ParseIssue("proposal_flag_ignored", "Aspek sudah ada di taxonomy; penanda usulan diabaikan.", index))
        return AspectResolution(code, False, None, issues)
    if code != "other":
        label = label or code[:MAX_LABEL_LENGTH]
        wants = True
    else:
        wants = flagged
        if flagged and not label:
            issues.append(ParseIssue("proposal_without_label", "Usulan aspek tanpa label; diperlakukan sebagai 'other'.", index))
            wants = False
    if wants and not allow_proposed:
        issues.append(ParseIssue("proposal_suppressed", f"Usulan aspek '{label}' dinonaktifkan; dicatat sebagai 'other'.", index))
        return AspectResolution("other", False, None, issues)
    return AspectResolution("other", True, label, issues) if wants else AspectResolution("other", False, None, issues)
