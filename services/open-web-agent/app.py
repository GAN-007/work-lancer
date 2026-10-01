from __future__ import annotations

import asyncio
import base64
import hashlib
import hmac
import ipaddress
import json
import os
import re
import socket
import sqlite3
from importlib.metadata import version
from pathlib import Path
from tempfile import TemporaryDirectory
from typing import Any, Literal
from urllib.parse import urlparse

import httpx
from browser_use import Agent, Browser, ChatOllama, ChatOpenAI
from crawl4ai import AsyncWebCrawler, BrowserConfig, CacheMode, CrawlerRunConfig
from fastapi import Depends, FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field, HttpUrl
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(case_sensitive=False)

    open_web_agent_token: str = ""
    open_web_agent_max_steps: int = 120
    open_web_agent_concurrency: int = 2
    open_web_agent_state_db: str = "/data/open-web-agent.sqlite"
    open_web_agent_use_vision: bool = False
    browser_use_headless: bool = True

    local_llm_provider: Literal["ollama", "openai_compatible"] = "ollama"
    ollama_base_url: str = "http://ollama:11434"
    ollama_model: str = "qwen3:8b"
    ollama_num_ctx: int = 65536

    openai_compatible_base_url: str = "http://browser-model:8000/v1"
    openai_compatible_model: str = "browser-use/bu-30b-a3b-preview"
    openai_compatible_api_key: str = "not-needed"

    searxng_url: str = "http://searxng:8080"
    fetch_timeout_seconds: int = 90
    max_attachment_bytes: int = 12_582_912


settings = Settings()
app = FastAPI(
    title="Worklancer Open Web Agent",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
)
execution_slots = asyncio.Semaphore(max(1, settings.open_web_agent_concurrency))
_key_locks: dict[str, asyncio.Lock] = {}
_key_locks_guard = asyncio.Lock()


class Attachment(BaseModel):
    kind: str = "attachment"
    filename: str
    mime: str = "application/octet-stream"
    sha256: str
    content_base64: str


class ApplicationRequest(BaseModel):
    application_id: int | None = None
    idempotency_key: str = Field(min_length=8, max_length=255)
    url: HttpUrl
    ats_type: str | None = None
    candidate: dict[str, Any]
    answers: dict[str, Any] = Field(default_factory=dict)
    attachments: list[Attachment] = Field(default_factory=list)
    rules: dict[str, Any] = Field(default_factory=dict)


class ConfirmationEvidence(BaseModel):
    page_url: str
    page_title: str | None = None
    visible_text: str = Field(min_length=1, max_length=4000)
    reference: str | None = None


FailureClass = Literal[
    "CAPTCHA",
    "UNKNOWN_ANSWER",
    "CV_UPLOAD",
    "AUTH",
    "SESSION_EXPIRED",
    "RATE_LIMIT",
    "SITE_ERROR",
    "CLOSED",
    "LOCATION",
    "OTHER",
]


class SubmissionResult(BaseModel):
    submitted: bool = False
    failure_class: FailureClass | None = None
    confirmation: ConfirmationEvidence | None = None
    confirmation_id: str | None = None
    route: str = "open_web_agent"
    notes: list[str] = Field(default_factory=list)


class SearchRequest(BaseModel):
    query: str = Field(min_length=1, max_length=1000)
    page: int = Field(default=1, ge=1, le=20)
    language: str = "all"
    safe_search: int = Field(default=1, ge=0, le=2)
    limit: int = Field(default=20, ge=1, le=100)


class FetchRequest(BaseModel):
    url: HttpUrl
    word_count_threshold: int = Field(default=1, ge=0, le=1000)
    max_chars: int = Field(default=200_000, ge=1_000, le=2_000_000)


def init_state_db() -> None:
    path = Path(settings.open_web_agent_state_db)
    path.parent.mkdir(parents=True, exist_ok=True)
    with sqlite3.connect(path) as db:
        db.execute(
            """
            CREATE TABLE IF NOT EXISTS idempotent_submissions (
                idempotency_key TEXT PRIMARY KEY,
                application_id INTEGER NULL,
                target_url TEXT NOT NULL,
                result_json TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
            """
        )
        db.commit()


@app.on_event("startup")
async def startup() -> None:
    init_state_db()


async def require_token(authorization: str | None = Header(default=None)) -> None:
    expected = settings.open_web_agent_token.strip()
    if not expected:
        return
    if authorization != f"Bearer {expected}":
        raise HTTPException(status_code=401, detail="Invalid Open Web Agent token")


async def idempotency_lock(key: str) -> asyncio.Lock:
    async with _key_locks_guard:
        lock = _key_locks.get(key)
        if lock is None:
            lock = asyncio.Lock()
            _key_locks[key] = lock
        return lock


def load_cached_submission(key: str) -> SubmissionResult | None:
    with sqlite3.connect(settings.open_web_agent_state_db) as db:
        row = db.execute(
            "SELECT result_json FROM idempotent_submissions WHERE idempotency_key = ?",
            (key,),
        ).fetchone()
    if not row:
        return None
    return SubmissionResult.model_validate_json(row[0])


def cache_submission(request: ApplicationRequest, result: SubmissionResult) -> None:
    if not result.submitted:
        return
    with sqlite3.connect(settings.open_web_agent_state_db) as db:
        db.execute(
            """
            INSERT OR REPLACE INTO idempotent_submissions
                (idempotency_key, application_id, target_url, result_json)
            VALUES (?, ?, ?, ?)
            """,
            (
                request.idempotency_key,
                request.application_id,
                str(request.url),
                result.model_dump_json(),
            ),
        )
        db.commit()


async def ensure_public_url(raw_url: str) -> None:
    parsed = urlparse(raw_url)
    if parsed.scheme not in {"http", "https"} or not parsed.hostname:
        raise HTTPException(status_code=400, detail="Only public HTTP/HTTPS URLs are allowed")

    hostname = parsed.hostname.lower().rstrip(".")
    if hostname == "localhost" or hostname.endswith(".localhost") or hostname.endswith(".local"):
        raise HTTPException(status_code=400, detail="Local network targets are not allowed")

    try:
        literal = ipaddress.ip_address(hostname)
        addresses = [literal]
    except ValueError:
        try:
            records = await asyncio.to_thread(socket.getaddrinfo, hostname, None)
        except socket.gaierror as exc:
            raise HTTPException(status_code=400, detail=f"Target hostname cannot be resolved: {exc}") from exc
        addresses = []
        for record in records:
            try:
                addresses.append(ipaddress.ip_address(record[4][0]))
            except ValueError:
                continue

    if not addresses:
        raise HTTPException(status_code=400, detail="Target hostname resolved to no usable addresses")

    for address in addresses:
        if (
            address.is_private
            or address.is_loopback
            or address.is_link_local
            or address.is_multicast
            or address.is_reserved
            or address.is_unspecified
        ):
            raise HTTPException(status_code=400, detail="Private or non-routable targets are not allowed")


def llm():
    if settings.local_llm_provider == "openai_compatible":
        return ChatOpenAI(
            model=settings.openai_compatible_model,
            base_url=settings.openai_compatible_base_url,
            api_key=settings.openai_compatible_api_key or "not-needed",
            temperature=0.0,
        )

    os.environ["OLLAMA_HOST"] = settings.ollama_base_url
    return ChatOllama(
        model=settings.ollama_model,
        num_ctx=settings.ollama_num_ctx,
        temperature=0.0,
    )


def write_attachments(directory: str, attachments: list[Attachment]) -> list[str]:
    paths: list[str] = []
    root = Path(directory).resolve()

    for attachment in attachments:
        safe_name = re.sub(r"[^A-Za-z0-9._-]+", "_", Path(attachment.filename).name)
        if not safe_name:
            safe_name = "attachment.bin"

        try:
            content = base64.b64decode(attachment.content_base64, validate=True)
        except Exception as exc:
            raise HTTPException(status_code=422, detail=f"Invalid base64 attachment {safe_name}") from exc

        if len(content) > settings.max_attachment_bytes:
            raise HTTPException(status_code=413, detail=f"Attachment {safe_name} exceeds configured size limit")

        actual = hashlib.sha256(content).hexdigest()
        if not attachment.sha256 or not hmac.compare_digest(actual, attachment.sha256.lower()):
            raise HTTPException(status_code=422, detail=f"Attachment integrity check failed for {safe_name}")

        path = (root / safe_name).resolve()
        if root not in path.parents:
            raise HTTPException(status_code=422, detail="Unsafe attachment filename")
        path.write_bytes(content)
        paths.append(str(path))

    return paths


def application_task(request: ApplicationRequest, file_paths: list[str]) -> str:
    payload = {
        "target_url": str(request.url),
        "ats_type": request.ats_type,
        "candidate": request.candidate,
        "verified_answers": request.answers,
        "available_files": file_paths,
    }
    return f"""
You are executing an authorized job-application workflow for the candidate represented by the supplied verified data.

TARGET AND VERIFIED DATA:
{json.dumps(payload, ensure_ascii=False, indent=2)}

MANDATORY RULES:
1. Navigate only as needed to complete the application that starts at target_url.
2. Use candidate fields and verified_answers exactly as the factual source of truth. Never invent, infer, embellish, or guess a material answer.
3. You may derive harmless formatting from supplied facts (for example joining first and last name), but may not create a new factual claim.
4. Upload the supplied resume/cover-letter files when requested, using the exact available file paths.
5. If a required material question cannot be answered from candidate or verified_answers, stop before final submission and return submitted=false with failure_class="UNKNOWN_ANSWER".
6. If authentication, expired session, CAPTCHA, anti-bot challenge, or another user-only step prevents completion, stop and return the corresponding failure class. Do not bypass or defeat a CAPTCHA.
7. Before any consequential final submit action, re-check that required fields are populated only from verified data.
8. Do not submit twice. If the site already shows that this requisition was submitted, treat that visible state as confirmation rather than creating another application.
9. submitted=true is allowed only after the site visibly confirms the application was received. A button click, network request, or navigation by itself is not confirmation.
10. For submitted=true, confirmation.visible_text must quote or accurately transcribe the visible confirmation message, confirmation.page_url must be the confirmation page URL, and confirmation.reference should contain an application/reference identifier when visible.
11. If the role is closed or unavailable, return submitted=false with failure_class="CLOSED".
12. Return only the structured SubmissionResult required by the agent schema.
""".strip()


@app.get("/health")
async def health() -> JSONResponse:
    checks: dict[str, Any] = {
        "browser_use": {"ok": True, "version": version("browser-use")},
        "crawl4ai": {"ok": True, "version": version("Crawl4AI")},
        "provider": settings.local_llm_provider,
    }

    async with httpx.AsyncClient(timeout=10.0) as client:
        try:
            if settings.local_llm_provider == "ollama":
                response = await client.get(settings.ollama_base_url.rstrip("/") + "/api/tags")
                tags = response.json().get("models", []) if response.is_success else []
                names = {str(item.get("name", "")) for item in tags}
                model_present = any(
                    name == settings.ollama_model or name.startswith(settings.ollama_model + ":")
                    for name in names
                )
                checks["llm"] = {
                    "ok": response.is_success and model_present,
                    "status": response.status_code,
                    "model": settings.ollama_model,
                    "model_present": model_present,
                }
            else:
                response = await client.get(
                    settings.openai_compatible_base_url.rstrip("/") + "/models",
                    headers={"Authorization": f"Bearer {settings.openai_compatible_api_key or 'not-needed'}"},
                )
                checks["llm"] = {
                    "ok": response.is_success,
                    "status": response.status_code,
                    "model": settings.openai_compatible_model,
                }
        except Exception as exc:
            checks["llm"] = {"ok": False, "error": str(exc)}

        try:
            response = await client.get(settings.searxng_url.rstrip("/") + "/")
            checks["searxng"] = {"ok": response.is_success, "status": response.status_code}
        except Exception as exc:
            checks["searxng"] = {"ok": False, "error": str(exc)}

    ok = all(check.get("ok", False) for key, check in checks.items() if isinstance(check, dict))
    return JSONResponse(status_code=200 if ok else 503, content={"ok": ok, "checks": checks})


@app.post("/v1/search", dependencies=[Depends(require_token)])
async def search(request: SearchRequest) -> dict[str, Any]:
    params = {
        "q": request.query,
        "format": "json",
        "pageno": request.page,
        "language": request.language,
        "safesearch": request.safe_search,
    }
    async with httpx.AsyncClient(timeout=30.0) as client:
        response = await client.get(settings.searxng_url.rstrip("/") + "/search", params=params)
    if not response.is_success:
        raise HTTPException(status_code=502, detail=f"SearXNG returned {response.status_code}")
    payload = response.json()
    results = payload.get("results", [])
    return {
        "query": request.query,
        "number_of_results": payload.get("number_of_results"),
        "results": results[: request.limit],
        "suggestions": payload.get("suggestions", []),
    }


@app.post("/v1/fetch", dependencies=[Depends(require_token)])
async def fetch(request: FetchRequest) -> dict[str, Any]:
    target = str(request.url)
    await ensure_public_url(target)

    browser_config = BrowserConfig(headless=True)
    run_config = CrawlerRunConfig(
        cache_mode=CacheMode.BYPASS,
        word_count_threshold=request.word_count_threshold,
        page_timeout=settings.fetch_timeout_seconds * 1000,
        check_robots_txt=True,
        max_retries=2,
    )

    async with AsyncWebCrawler(config=browser_config) as crawler:
        result = await crawler.arun(url=target, config=run_config)

    if not result.success:
        raise HTTPException(status_code=502, detail=result.error_message or "Crawl4AI fetch failed")

    markdown = str(result.markdown or "")
    return {
        "url": result.url,
        "status_code": result.status_code,
        "markdown": markdown[: request.max_chars],
        "links": result.links,
        "metadata": result.metadata,
    }


@app.post("/v1/apply", response_model=SubmissionResult, dependencies=[Depends(require_token)])
async def apply(request: ApplicationRequest) -> SubmissionResult:
    target = str(request.url)
    await ensure_public_url(target)

    lock = await idempotency_lock(request.idempotency_key)
    async with lock:
        cached = load_cached_submission(request.idempotency_key)
        if cached is not None:
            cached.notes.append("Returned from durable idempotency cache; no second submission was attempted.")
            return cached

        async with execution_slots:
            with TemporaryDirectory(prefix="worklancer-agent-") as directory:
                file_paths = write_attachments(directory, request.attachments)
                browser = Browser(
                    headless=settings.browser_use_headless,
                    downloads_path=directory,
                )
                agent = Agent(
                    task=application_task(request, file_paths),
                    llm=llm(),
                    browser=browser,
                    output_model_schema=SubmissionResult,
                    available_file_paths=file_paths,
                    use_vision=settings.open_web_agent_use_vision,
                    max_actions_per_step=5,
                    max_failures=4,
                    directly_open_url=True,
                    final_response_after_failure=True,
                )

                try:
                    history = await agent.run(max_steps=settings.open_web_agent_max_steps)
                    result = history.structured_output
                    if result is None:
                        final = history.final_result()
                        if final:
                            try:
                                result = SubmissionResult.model_validate_json(final)
                            except Exception:
                                result = None

                    if result is None:
                        result = SubmissionResult(
                            submitted=False,
                            failure_class="OTHER",
                            notes=["Browser agent finished without a valid structured result."],
                        )

                    if result.submitted and result.confirmation is None:
                        result = SubmissionResult(
                            submitted=False,
                            failure_class="OTHER",
                            notes=["Agent claimed submission without confirmation evidence; result was rejected."],
                        )

                    if result.submitted and result.confirmation is not None:
                        try:
                            current_url = await agent.browser_session.get_current_page_url()
                            current_title = await agent.browser_session.get_current_page_title()
                            result.confirmation.page_url = current_url or result.confirmation.page_url
                            result.confirmation.page_title = current_title or result.confirmation.page_title
                        except Exception:
                            pass

                        if not result.confirmation_id:
                            result.confirmation_id = result.confirmation.reference

                        cache_submission(request, result)

                    return result
                except Exception as exc:
                    return SubmissionResult(
                        submitted=False,
                        failure_class="SITE_ERROR",
                        notes=[f"{type(exc).__name__}: {exc}"],
                    )
                finally:
                    try:
                        await browser.kill()
                    except Exception:
                        pass
