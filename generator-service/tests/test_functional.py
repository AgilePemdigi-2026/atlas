from __future__ import annotations

import ast
import importlib.util
import sys
from pathlib import Path
from types import ModuleType

import pytest
from support import load_scenario, scenario_cases

from converter import convert


def _scenario_functions(script: str) -> list[str]:
    tree = ast.parse(script)

    return [
        node.name
        for node in tree.body
        if isinstance(node, ast.FunctionDef)
        and node.name not in {"wait_for", "assert_condition"}
    ]


def _import_script(script: str, tmp_path: Path) -> ModuleType:
    path = tmp_path / "generated_script.py"
    path.write_text(script, encoding="utf-8")

    spec = importlib.util.spec_from_file_location("generated_script", path)
    assert spec is not None and spec.loader is not None

    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module

    try:
        spec.loader.exec_module(module)
    finally:
        sys.modules.pop(spec.name, None)

    return module


@pytest.mark.parametrize(("input_path", "scenario_index"), scenario_cases())
def test_scenario_executes(
    input_path: Path,
    scenario_index: int,
    base_url: str,
    chrome_driver: object,
    tmp_path: Path,
) -> None:
    payload = load_scenario(input_path, base_url)
    result = convert(payload)

    assert result.status == "success", result.error

    script = result.script
    assert script is not None

    functions = _scenario_functions(script)
    function_name = functions[scenario_index]
    module = _import_script(script, tmp_path)
    scenario = getattr(module, function_name)

    if input_path.stem == "failing_assertion":
        with pytest.raises(AssertionError, match="assert_text expected"):
            scenario(chrome_driver)
        return

    scenario(chrome_driver)
