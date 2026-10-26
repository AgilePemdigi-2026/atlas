"""Regenerate golden expectations from the current generator.

Review the diff before committing: this overwrites expected scripts and errors.
"""

from __future__ import annotations

import json
from pathlib import Path

from testgen.codegen.selenium_generator import generate_script
from testgen.errors import ConversionError

GOLDEN_DIR = Path(__file__).parent / "fixtures" / "golden"


def main() -> int:
    for path in sorted(GOLDEN_DIR.glob("*.json")):
        if path.name.endswith(".error.json"):
            continue

        payload = json.loads(path.read_text(encoding="utf-8"))

        try:
            script = generate_script(payload)
        except ConversionError as error:
            path.with_name(f"{path.stem}.error.json").write_text(
                json.dumps(error.to_dict(), ensure_ascii=False, indent=2) + "\n",
                encoding="utf-8",
            )
            stale = path.with_suffix(".py")
        else:
            path.with_suffix(".py").write_text(script, encoding="utf-8")
            stale = path.with_name(f"{path.stem}.error.json")

        if stale.exists():
            stale.unlink()

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
