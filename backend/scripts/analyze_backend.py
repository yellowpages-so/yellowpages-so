from __future__ import annotations

import json
import re
import sys
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass
from pathlib import Path


@dataclass
class Finding:
    severity: str
    category: str
    title: str
    evidence: str
    recommendation: str
    file: str | None = None
    line: int | None = None


ORDER = {"critical": 0, "high": 1, "medium": 2, "low": 3, "info": 4}


def text(path: Path) -> str:
    try:
        return path.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        return ""


def rel(path: Path, root: Path) -> str:
    try:
        return str(path.relative_to(root))
    except ValueError:
        return str(path)


def php_files(root: Path, folder: str) -> list[Path]:
    path = root / folder
    return sorted(path.rglob("*.php")) if path.exists() else []


def line_number(source: str, offset: int) -> int:
    return source[:offset].count("\n") + 1


def add(findings, severity, category, title, evidence, recommendation, file=None, line=None):
    findings.append(Finding(severity, category, title, evidence, recommendation, file, line))


def analyze_migrations(root: Path, findings: list[Finding]) -> dict:
    migrations = php_files(root, "database/migrations")
    tables = defaultdict(list)
    schemas = defaultdict(list)

    create_table = re.compile(r"Schema::create\(\s*['\"]([^'\"]+)['\"]")
    create_schema = re.compile(
        r"CREATE\s+SCHEMA\s+(?:IF\s+NOT\s+EXISTS\s+)?([A-Za-z0-9_]+)",
        re.I,
    )

    for path in migrations:
        source = text(path)
        name = rel(path, root)

        for match in create_table.finditer(source):
            tables[match.group(1)].append(name)

        for match in create_schema.finditer(source):
            schemas[match.group(1)].append(name)

        if "Schema::table(" in source and "Schema::create(" not in source:
            add(
                findings,
                "medium",
                "migrations",
                "Migration depends on pre-existing schema",
                name,
                "Document the baseline dependency or consolidate migrations into a deterministic installation sequence.",
                name,
            )

        if "->foreign(" in source and "parent_id" in source:
            add(
                findings,
                "medium",
                "migrations",
                "Self-referencing foreign key requires review",
                name,
                "Verify the referenced column is primary or unique before adding the foreign key.",
                name,
            )

    for table_name, creators in tables.items():
        if len(creators) > 1:
            add(
                findings,
                "high",
                "migrations",
                f"Duplicate table creation: {table_name}",
                ", ".join(creators),
                "Keep one canonical creation migration and convert later copies into alterations.",
            )

    for schema_name, creators in schemas.items():
        if len(creators) > 1:
            add(
                findings,
                "medium",
                "migrations",
                f"Schema created multiple times: {schema_name}",
                ", ".join(creators),
                "Create each PostgreSQL schema once in a baseline migration.",
            )

    return {
        "migration_count": len(migrations),
        "duplicate_tables": {k: v for k, v in tables.items() if len(v) > 1},
        "duplicate_schemas": {k: v for k, v in schemas.items() if len(v) > 1},
    }


def analyze_code(root: Path, findings: list[Finding]) -> dict:
    files = php_files(root, "app")
    large = []
    classes = defaultdict(list)

    for path in files:
        source = text(path)
        name = rel(path, root)
        lines = source.count("\n") + 1

        if lines >= 500:
            severity = "high"
        elif lines >= 300:
            severity = "medium"
        else:
            severity = None

        if severity:
            large.append({"file": name, "lines": lines})
            add(
                findings,
                severity,
                "maintainability",
                f"Large PHP file: {name}",
                f"{lines} lines",
                "Split responsibilities into smaller services, actions, requests, resources, or policies.",
                name,
            )

        class_match = re.search(r"\bclass\s+([A-Za-z_][A-Za-z0-9_]*)", source)
        if class_match:
            classes[class_match.group(1)].append(name)

        for match in re.finditer(r"\b(dd|dump|var_dump|print_r)\s*\(", source):
            add(
                findings,
                "high",
                "quality",
                "Debug output in application code",
                match.group(0),
                "Remove debug output before release.",
                name,
                line_number(source, match.start()),
            )

        for match in re.finditer(r"protected\s+\$guarded\s*=\s*\[\s*\]", source):
            add(
                findings,
                "medium",
                "security",
                "Model allows unrestricted mass assignment",
                match.group(0),
                "Use explicit fillable fields or DTO-based assignment.",
                name,
                line_number(source, match.start()),
            )

        if "Schema::hasTable" in source or "Schema::hasColumn" in source:
            add(
                findings,
                "medium",
                "architecture",
                "Runtime schema compatibility logic",
                name,
                "Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.",
                name,
            )

        if "getMessage()" in source:
            add(
                findings,
                "medium",
                "security",
                "Raw exception message usage",
                name,
                "Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.",
                name,
            )

    for class_name, paths in classes.items():
        if len(paths) > 1:
            add(
                findings,
                "high",
                "maintainability",
                f"Duplicate class name: {class_name}",
                ", ".join(paths),
                "Remove or rename duplicate classes to prevent autoload ambiguity.",
            )

    return {
        "app_php_files": len(files),
        "large_files": large,
        "duplicate_classes": {k: v for k, v in classes.items() if len(v) > 1},
    }


def analyze_routes(root: Path, findings: list[Finding]) -> dict:
    route_path = root / "storage/audit/raw/routes.json"

    try:
        routes = json.loads(text(route_path))
    except Exception:
        add(
            findings,
            "high",
            "routes",
            "Route list could not be parsed",
            text(route_path)[:800],
            "Fix route registration errors and rerun php artisan route:list --json.",
        )
        return {"route_count": 0}

    names = Counter()
    signatures = Counter()
    public_writes = []

    for route in routes:
        method = route.get("method", "")
        uri = route.get("uri", "")
        name = route.get("name")
        middleware = route.get("middleware", [])
        middleware_text = " ".join(middleware) if isinstance(middleware, list) else str(middleware)

        if name:
            names[name] += 1
        signatures[(method, uri)] += 1

        write_route = any(x in method.split("|") for x in ["POST", "PUT", "PATCH", "DELETE"])
        if write_route and "auth" not in middleware_text and "sanctum" not in middleware_text:
            public_writes.append(f"{method} {uri}")

    for name, count in names.items():
        if count > 1:
            add(
                findings,
                "medium",
                "routes",
                f"Duplicate route name: {name}",
                f"{count} occurrences",
                "Assign unique route names.",
            )

    for signature, count in signatures.items():
        if count > 1:
            add(
                findings,
                "high",
                "routes",
                "Duplicate route method and URI",
                f"{signature[0]} {signature[1]} appears {count} times",
                "Remove duplicate registration or explicitly version the endpoint.",
            )

    for route in public_writes:
        add(
            findings,
            "high",
            "security",
            "Unauthenticated write route requires review",
            route,
            "Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.",
        )

    return {"route_count": len(routes), "public_write_routes": public_writes}


def analyze_configuration(root: Path, findings: list[Finding]) -> dict:
    summary = {}

    for filename in [".env", ".env.testing", ".env.production"]:
        path = root / filename
        summary[filename] = "present" if path.exists() else "missing"

    production = root / ".env.production"
    if not production.exists():
        add(
            findings,
            "high",
            "configuration",
            "Production environment file missing",
            str(production),
            "Create it through deployment automation and store secrets outside Git.",
        )
    else:
        source = text(production)
        if re.search(r"^APP_DEBUG=true$", source, re.M):
            add(
                findings,
                "critical",
                "security",
                "Production debug mode enabled",
                "APP_DEBUG=true",
                "Set APP_DEBUG=false.",
                ".env.production",
            )

        if re.search(r"CHANGE_ME|YOUR_PASSWORD|password123", source):
            add(
                findings,
                "critical",
                "security",
                "Placeholder production secret",
                "Unsafe placeholder detected.",
                "Replace placeholders through a secret manager.",
                ".env.production",
            )

        if re.search(r"^QUEUE_CONNECTION=sync$", source, re.M):
            add(
                findings,
                "high",
                "reliability",
                "Production queue is synchronous",
                "QUEUE_CONNECTION=sync",
                "Use Redis or database queues with supervised workers.",
                ".env.production",
            )

    phpunit = root / "phpunit.xml"
    if phpunit.exists():
        source = text(phpunit)

        if "DB_PASSWORD" in source:
            add(
                findings,
                "high",
                "security",
                "Database password stored in phpunit.xml",
                "DB_PASSWORD is defined in phpunit.xml.",
                "Load the password from .env.testing or CI secrets.",
                "phpunit.xml",
            )

        if "yellowpages_test" not in source:
            add(
                findings,
                "high",
                "testing",
                "Test database isolation is not explicit",
                "yellowpages_test is not referenced in phpunit.xml.",
                "Force PHPUnit to use a dedicated disposable database.",
                "phpunit.xml",
            )

    return summary


def analyze_tests(root: Path, findings: list[Finding]) -> dict:
    output = text(root / "storage/audit/raw/tests.txt")
    green = bool(output) and "FAIL" not in output and "failed" not in output.lower()

    if not green:
        add(
            findings,
            "critical",
            "testing",
            "Backend test suite is not fully green",
            output[-2500:] if output else "No test output captured.",
            "Resolve every test failure before release. Clear production caches before PHPUnit.",
        )

    tests = php_files(root, "tests")
    services = php_files(root, "app/Services")

    if services and len(tests) < max(5, len(services) // 2):
        add(
            findings,
            "medium",
            "testing",
            "Test coverage appears low relative to services",
            f"{len(services)} service files, {len(tests)} test files",
            "Add tests for authorization, validation, idempotency, rollback, duplicate requests, and failure paths.",
        )

    return {
        "test_files": len(tests),
        "service_files": len(services),
        "test_output_looks_green": green,
    }


def analyze_dependencies(root: Path, findings: list[Finding]) -> dict:
    audit = text(root / "storage/audit/raw/composer-audit.txt")
    outdated = text(root / "storage/audit/raw/composer-outdated.txt")

    if audit and "No security vulnerability advisories found" not in audit:
        if "advisories" in audit.lower() or "vulnerab" in audit.lower():
            add(
                findings,
                "critical",
                "dependencies",
                "Composer security advisory detected",
                audit[:2500],
                "Upgrade or replace affected dependencies before release.",
            )

    return {
        "composer_audit_excerpt": audit[:800],
        "composer_outdated_excerpt": outdated[:800],
    }


def write_report(root: Path, report_dir: Path) -> None:
    findings: list[Finding] = []

    summary = {
        "migrations": analyze_migrations(root, findings),
        "code": analyze_code(root, findings),
        "routes": analyze_routes(root, findings),
        "configuration": analyze_configuration(root, findings),
        "tests": analyze_tests(root, findings),
        "dependencies": analyze_dependencies(root, findings),
    }

    findings.sort(key=lambda f: (ORDER.get(f.severity, 99), f.category, f.title))
    counts = Counter(f.severity for f in findings)

    score = max(
        0,
        100
        - counts["critical"] * 20
        - counts["high"] * 8
        - counts["medium"] * 3
        - counts["low"],
    )

    payload = {
        "project": "YellowPages.so backend",
        "audit_score": score,
        "severity_counts": dict(counts),
        "summary": summary,
        "findings": [asdict(f) for f in findings],
    }

    report_dir.mkdir(parents=True, exist_ok=True)

    (report_dir / "backend-audit.json").write_text(
        json.dumps(payload, indent=2),
        encoding="utf-8",
    )

    lines = [
        "# YellowPages.so Backend Audit",
        "",
        f"Audit score: {score}/100",
        "",
        "## Finding counts",
        "",
        f"- Critical: {counts['critical']}",
        f"- High: {counts['high']}",
        f"- Medium: {counts['medium']}",
        f"- Low: {counts['low']}",
        "",
        "## Executive priorities",
        "",
    ]

    top = [f for f in findings if f.severity in {"critical", "high"}][:20]
    if top:
        for index, finding in enumerate(top, 1):
            lines.append(f"{index}. [{finding.severity.upper()}] {finding.title}")
    else:
        lines.append("No critical or high findings detected by the automated scan.")

    grouped = defaultdict(list)
    for finding in findings:
        grouped[finding.category].append(finding)

    for category in sorted(grouped):
        lines.extend(["", f"## {category.replace('-', ' ').title()}", ""])

        for finding in grouped[category]:
            location = ""
            if finding.file:
                location = f" File: `{finding.file}`"
                if finding.line:
                    location += f", line {finding.line}"

            lines.extend([
                f"### {finding.severity.upper()}: {finding.title}",
                "",
                f"Evidence: {finding.evidence}{location}",
                "",
                f"Recommendation: {finding.recommendation}",
                "",
            ])

    lines.extend([
        "## Limitations",
        "",
        "This automated scan does not replace manual code review, penetration testing, load testing, production data-flow review, or third-party integration review.",
        "",
    ])

    (report_dir / "backend-audit.md").write_text(
        "\n".join(lines),
        encoding="utf-8",
    )


if __name__ == "__main__":
    if len(sys.argv) != 3:
        raise SystemExit("Usage: analyze_backend.py BACKEND_ROOT REPORT_DIR")

    write_report(
        Path(sys.argv[1]).resolve(),
        Path(sys.argv[2]).resolve(),
    )
