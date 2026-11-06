"""Download a reproducible, attributed RealWaste subset; deduplicate before splitting."""
import argparse
import csv
import hashlib
import io
import json
import random
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parent
REVISION = "156e8e9154eeb163df30fcfd767f55b19633bb06"
SOURCES = {"organic": "Food Organics", "paper": "Paper", "plastic": "Plastic"}
BASE = f"https://raw.githubusercontent.com/sam-single/realwaste/{REVISION}/"

def fetch(url):
    request = urllib.request.Request(url, headers={"User-Agent": "Bersih-Education/1.0"})
    for attempt in range(3):
        try:
            with urllib.request.urlopen(request, timeout=60) as response:
                return response.read()
        except Exception:
            if attempt == 2:
                raise

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--per-class", type=int, default=400)
    args = parser.parse_args()
    if args.per_class < 50:
        parser.error("Use at least 50 images per class.")
    tree = json.loads(fetch(f"https://api.github.com/repos/sam-single/realwaste/git/trees/{REVISION}?recursive=1"))["tree"]
    source_hashes = {item["path"]: item["sha"] for item in tree if item["type"] == "blob"}
    rows = []
    rng = random.Random(42)
    for category, source in SOURCES.items():
        choices = sorted(x["path"] for x in tree if x["type"] == "blob" and x["path"].startswith(f"RealWaste/{source}/") and x["path"].lower().endswith(".jpg"))
        rng.shuffle(choices)
        choices = choices[:args.per_class]
        def download(path):
            url = BASE + urllib.parse.quote(path)
            target = ROOT / "data" / category / Path(path).name.replace(" ", "_")
            target.parent.mkdir(parents=True, exist_ok=True)
            content = target.read_bytes() if target.exists() else fetch(url)
            def git_blob_hash(data):
                return hashlib.sha1(b"blob " + str(len(data)).encode() + b"\0" + data).hexdigest()
            if git_blob_hash(content) != source_hashes[path]:
                content = fetch(url)
            if git_blob_hash(content) != source_hashes[path]:
                raise ValueError("Downloaded bytes do not match pinned Git source: " + path)
            with Image.open(io.BytesIO(content)) as img:
                img.load()
                rgb = img.convert("RGB")
                digest = hashlib.sha256(rgb.tobytes()).hexdigest()
                pixels = list(rgb.convert("L").resize((9, 8)).get_flattened_data())
                dhash = sum((pixels[y*9+x] > pixels[y*9+x+1]) << (y*8+x) for y in range(8) for x in range(8))
            target.write_bytes(content)
            return {"path": target.relative_to(ROOT).as_posix(), "category": category, "source_url": url, "sha256": hashlib.sha256(content).hexdigest(), "pixel_hash": digest, "dhash": dhash}
        with ThreadPoolExecutor(max_workers=8) as pool:
            downloaded = list(pool.map(download, choices))
        retained = []
        for row in downloaded:
            if any(row["pixel_hash"] == other["pixel_hash"] or (row["dhash"] ^ other["dhash"]).bit_count() <= 5 for other in retained):
                row["split"] = "excluded_duplicate"
            else:
                retained.append(row)
        rng.shuffle(retained)
        train_end, val_end = int(len(retained)*0.7), int(len(retained)*0.85)
        for i, row in enumerate(retained):
            row["split"] = "train" if i < train_end else "val" if i < val_end else "test"
        rows.extend(downloaded)
        print(category, len(downloaded), "downloaded;", len(retained), "unique", flush=True)
    with (ROOT / "data/manifest.csv").open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)
    summary = {"source": "RealWaste — Sam Single et al.", "revision": REVISION, "license": "CC BY-NC-SA 4.0", "seed": 42, "deduplication": "Exact decoded pixels or 64-bit difference hash Hamming distance <= 5; excluded before split.", "downloaded": len(rows), "splits": {s: {c: sum(r["split"] == s and r["category"] == c for r in rows) for c in SOURCES} for s in ["train", "val", "test", "excluded_duplicate"]}}
    (ROOT / "data/dataset_summary.json").write_text(json.dumps(summary, indent=2), encoding="utf-8")
    print(json.dumps(summary, indent=2))

if __name__ == "__main__":
    main()
