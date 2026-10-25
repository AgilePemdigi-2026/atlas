"""Regenerate golden expectations from the current converter.

Review the diff before committing: this overwrites expected scripts and errors.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

GOLDEN_DIR = Path(__file__).parent / "golden"

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "src"))

from converter import convert


def main() -> int:
    for path in sorted(GOLDEN_DIR.glob("*.json")):
        if path.name.endswith(".error.json"):
            continue

        payload = json.loads(path.read_text(encoding="utf-8"))
        result = convert(payload)

        if result.status == "success":
            path.with_suffix(".py").write_text(result.script, encoding="utf-8")
            stale = path.with_name(f"{path.stem}.error.json")
        else:
            path.with_name(f"{path.stem}.error.json").write_text(
                json.dumps(result.error.to_dict(), ensure_ascii=False, indent=2) + "\n",
                encoding="utf-8",
            )
            stale = path.with_suffix(".py")

        if stale.exists():
            stale.unlink()

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
