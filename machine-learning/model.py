"""Shared preprocessing and architecture for training and serving."""
import os
from pathlib import Path
import torch
from torch import nn
from torchvision.models import efficientnet_b0, EfficientNet_B0_Weights

ROOT = Path(__file__).resolve().parent
CLASSES = ["organic", "paper", "plastic"]
LABELS = {"organic": "Organic Waste", "paper": "Paper Waste", "plastic": "Plastic Waste"}
torch.set_num_threads(min(4, os.cpu_count() or 1))
torch.hub.set_dir(str(ROOT / ".cache/torch"))

def build_model(pretrained=False):
    model = efficientnet_b0(weights=EfficientNet_B0_Weights.IMAGENET1K_V1 if pretrained else None)
    model.classifier = nn.Sequential(nn.Dropout(0.3), nn.Linear(1280, len(CLASSES)))
    return model

def preprocess():
    return EfficientNet_B0_Weights.IMAGENET1K_V1.transforms()

def load_model(path=None):
    artifact = Path(path or ROOT / "artifacts/model.pt")
    checkpoint = torch.load(artifact, map_location="cpu", weights_only=True)
    if checkpoint["classes"] != CLASSES:
        raise ValueError("Model class order does not match API.")
    model = build_model()
    model.load_state_dict(checkpoint["state_dict"])
    model.eval()
    return model, checkpoint
