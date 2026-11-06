import csv
import hashlib
import json
from model import ROOT


def test_dataset_is_attributed_disjoint_and_matches_model_manifest():
    manifest = ROOT / "data/manifest.csv"
    rows = list(csv.DictReader(manifest.open(encoding="utf-8")))
    included = [r for r in rows if r["split"] != "excluded_duplicate"]
    assert len(included) >= 150
    assert {r["category"] for r in included} == {"organic", "paper", "plastic"}
    assert len({r["sha256"] for r in included}) == len(included)
    assert len({r["pixel_hash"] for r in included}) == len(included)
    for row in rows:
        assert row["source_url"].startswith("https://raw.githubusercontent.com/sam-single/realwaste/")
        assert hashlib.sha256((ROOT / row["path"]).read_bytes()).hexdigest() == row["sha256"]
    metadata = json.loads((ROOT / "artifacts/training.json").read_text())["metadata"]
    assert hashlib.sha256(manifest.read_bytes()).hexdigest() == metadata["manifest_sha256"]


def test_no_near_duplicate_within_class_crosses_splits():
    rows = [r for r in csv.DictReader((ROOT / "data/manifest.csv").open(encoding="utf-8")) if r["split"] != "excluded_duplicate"]
    for index, row in enumerate(rows):
        for other in rows[index+1:]:
            if row["category"] == other["category"] and row["split"] != other["split"]:
                assert (int(row["dhash"]) ^ int(other["dhash"])).bit_count() > 5
