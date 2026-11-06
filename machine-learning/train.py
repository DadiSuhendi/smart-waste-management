"""Frozen ImageNet EfficientNet-B0 + trained PyTorch head; validation-only selection."""
import argparse
import copy
import csv
import hashlib
import json
import random
from datetime import datetime, timezone
import numpy as np
import torch
from PIL import Image, ImageOps
from torch import nn
from torch.utils.data import DataLoader, Dataset, TensorDataset
from model import ROOT, CLASSES, build_model, preprocess

class Images(Dataset):
    def __init__(self, rows, flip=False):
        self.rows, self.flip, self.transform = rows, flip, preprocess()
    def __len__(self):
        return len(self.rows)
    def __getitem__(self, index):
        row = self.rows[index]
        with Image.open(ROOT / row["path"]) as img:
            img = ImageOps.exif_transpose(img).convert("RGB")
            if self.flip:
                img = ImageOps.mirror(img)
            return self.transform(img), CLASSES.index(row["category"])

def extract(model, rows, flip=False):
    features, labels = [], []
    model.eval()
    with torch.inference_mode():
        for x, y in DataLoader(Images(rows, flip), batch_size=24, shuffle=False):
            features.append(model.avgpool(model.features(x)).flatten(1))
            labels.append(y)
    return torch.cat(features).clone(), torch.cat(labels).clone()

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--epochs", type=int, default=160)
    args = parser.parse_args()
    random.seed(42); np.random.seed(42); torch.manual_seed(42)
    rows = list(csv.DictReader((ROOT / "data/manifest.csv").open(encoding="utf-8")))
    train_rows = [r for r in rows if r["split"] == "train"]
    val_rows = [r for r in rows if r["split"] == "val"]
    if len(train_rows) < 100 or not val_rows:
        raise ValueError("Download and split the dataset first.")
    model = build_model(pretrained=True)
    for parameter in model.features.parameters():
        parameter.requires_grad = False
    print("Extracting train features...", flush=True)
    train_x, train_y = extract(model, train_rows)
    flip_x, flip_y = extract(model, train_rows, flip=True)
    train_x, train_y = torch.cat([train_x, flip_x]), torch.cat([train_y, flip_y])
    print("Extracting validation features...", flush=True)
    val_x, val_y = extract(model, val_rows)
    loader = DataLoader(TensorDataset(train_x, train_y), batch_size=64, shuffle=True)
    optimizer = torch.optim.AdamW(model.classifier.parameters(), lr=0.002, weight_decay=0.02)
    loss_fn = nn.CrossEntropyLoss(label_smoothing=0.05)
    history, best, best_loss, best_epoch = [], None, float("inf"), 0
    for epoch in range(1, args.epochs+1):
        model.classifier.train()
        train_loss = 0.0
        for x, y in loader:
            optimizer.zero_grad()
            loss = loss_fn(model.classifier(x), y)
            loss.backward()
            optimizer.step()
            train_loss += loss.item() * len(y)
        model.classifier.eval()
        with torch.no_grad():
            logits = model.classifier(val_x)
            val_loss = nn.functional.cross_entropy(logits, val_y).item()
            val_acc = (logits.argmax(1) == val_y).float().mean().item()
        history.append({"epoch": epoch, "train_loss": train_loss/len(train_y), "val_loss": val_loss, "val_accuracy": val_acc})
        if val_loss < best_loss:
            best_loss, best_epoch = val_loss, epoch
            best = copy.deepcopy(model.state_dict())
        if epoch % 20 == 0:
            print(f"Epoch {epoch}: val accuracy={val_acc:.4f}, loss={val_loss:.4f}", flush=True)
    artifacts = ROOT / "artifacts"; artifacts.mkdir(exist_ok=True)
    metadata = {"classes": CLASSES, "architecture": "EfficientNet-B0 frozen ImageNet backbone + PyTorch linear head", "seed": 42, "selected_epoch": best_epoch, "epochs": args.epochs, "selection": "minimum validation cross-entropy; test excluded from training and selection", "manifest_sha256": hashlib.sha256((ROOT/"data/manifest.csv").read_bytes()).hexdigest(), "trained_at": datetime.now(timezone.utc).isoformat(), "train_count": len(train_rows), "val_count": len(val_rows)}
    torch.save({"state_dict": best, **metadata}, artifacts/"model.pt")
    (artifacts/"training.json").write_text(json.dumps({"metadata": metadata, "history": history}, indent=2), encoding="utf-8")
    print("Model saved. Run evaluate.py once for the held-out test evaluation.", flush=True)

if __name__ == "__main__":
    main()
