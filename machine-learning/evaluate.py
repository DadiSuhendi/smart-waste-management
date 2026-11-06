"""Evaluate held-out test data, without changing the model or selecting hyperparameters."""
import csv
import hashlib
import json
import os
from pathlib import Path
from datetime import datetime, timezone
os.environ.setdefault("MPLCONFIGDIR", str(Path(__file__).resolve().parent / ".cache/matplotlib"))
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import torch
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, ConfusionMatrixDisplay
from torch.utils.data import DataLoader
from model import ROOT, CLASSES, load_model
from train import Images

def main():
    model, checkpoint = load_model()
    manifest = ROOT / "data/manifest.csv"
    if hashlib.sha256(manifest.read_bytes()).hexdigest() != checkpoint["manifest_sha256"]:
        raise ValueError("Dataset manifest changed since training.")
    rows = [r for r in csv.DictReader(manifest.open(encoding="utf-8")) if r["split"] == "test"]
    truth, predictions = [], []
    with torch.inference_mode():
        for images, labels in DataLoader(Images(rows), batch_size=24):
            truth.extend(labels.tolist())
            predictions.extend(model(images).argmax(1).tolist())
    accuracy = accuracy_score(truth, predictions)
    matrix = confusion_matrix(truth, predictions, labels=range(3))
    report = classification_report(truth, predictions, labels=range(3), target_names=CLASSES, output_dict=True, zero_division=0)
    results = {"accuracy": accuracy, "target_accuracy": 0.8, "target_met": bool(accuracy >= 0.8), "test_count": len(rows), "classes": CLASSES, "confusion_matrix": matrix.tolist(), "classification_report": report, "model_sha256": hashlib.sha256((ROOT/"artifacts/model.pt").read_bytes()).hexdigest(), "manifest_sha256": checkpoint["manifest_sha256"], "evaluated_at": datetime.now(timezone.utc).isoformat()}
    output = ROOT / "artifacts"
    (output/"evaluation.json").write_text(json.dumps(results, indent=2), encoding="utf-8")
    (output/"classification_report.txt").write_text(classification_report(truth, predictions, target_names=CLASSES, digits=4, zero_division=0), encoding="utf-8")
    with (output/"test_predictions.csv").open("w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f); writer.writerow(["path","actual","predicted"])
        writer.writerows((r["path"], CLASSES[t], CLASSES[p]) for r,t,p in zip(rows, truth, predictions))
    fig, ax = plt.subplots(figsize=(6,5))
    ConfusionMatrixDisplay(matrix, display_labels=CLASSES).plot(ax=ax, cmap="Greens", colorbar=False)
    ax.set_title("RealWaste — held-out test set")
    fig.tight_layout(); fig.savefig(output/"confusion_matrix.png", dpi=180); plt.close(fig)
    training = json.loads((output/"training.json").read_text())["history"]
    fig, axes = plt.subplots(1,2,figsize=(10,4))
    axes[0].plot([e["train_loss"] for e in training], label="Train")
    axes[0].plot([e["val_loss"] for e in training], label="Validation"); axes[0].legend(); axes[0].set_title("Loss")
    axes[1].plot([e["val_accuracy"] for e in training], color="#16724b"); axes[1].set_title("Validation accuracy")
    for ax in axes: ax.set_xlabel("Epoch")
    fig.tight_layout(); fig.savefig(output/"training_curves.png", dpi=180); plt.close(fig)
    print(json.dumps(results, indent=2))

if __name__ == "__main__":
    main()
