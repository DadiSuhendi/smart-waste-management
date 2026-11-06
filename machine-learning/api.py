"""FastAPI inference service. Bind to loopback or a private network behind Laravel."""
import io
import os
import secrets
from contextlib import asynccontextmanager
from threading import Lock
import torch
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image, ImageOps, UnidentifiedImageError
from starlette.concurrency import run_in_threadpool
from model import CLASSES, LABELS, load_model, preprocess

MAX_BYTES = 5 * 1024 * 1024
Image.MAX_IMAGE_PIXELS = 20_000_000
prediction_lock = Lock()

@asynccontextmanager
async def lifespan(app):
    try:
        app.state.model, app.state.metadata = load_model()
    except FileNotFoundError:
        app.state.model = None
        app.state.metadata = {}
    yield

app = FastAPI(title="Bersih Waste Classifier", version="1.0.0", lifespan=lifespan)

@app.get("/health")
def health():
    return {"status": "ready" if app.state.model is not None else "model_missing", "classes": CLASSES}

def infer(content):
    try:
        with Image.open(io.BytesIO(content)) as image:
            if image.format not in {"JPEG", "PNG", "WEBP"}:
                raise HTTPException(422, "Only JPEG, PNG and WebP images are supported.")
            if image.width * image.height > 20_000_000:
                raise HTTPException(422, "Image dimensions exceed 20 megapixels.")
            image.load()
            tensor = preprocess()(ImageOps.exif_transpose(image).convert("RGB")).unsqueeze(0)
    except (UnidentifiedImageError, OSError, ValueError, Image.DecompressionBombError, Image.DecompressionBombWarning):
        raise HTTPException(422, "The uploaded file is not a valid supported image.")
    with prediction_lock, torch.inference_mode():
        probabilities = app.state.model(tensor).softmax(1)[0]
    index = int(probabilities.argmax())
    confidence = float(probabilities[index])
    return {"category": CLASSES[index], "label": LABELS[CLASSES[index]], "confidence": confidence,
            "probabilities": dict(zip(CLASSES, probabilities.tolist())), "needs_review": confidence < 0.7,
            "model_version": app.state.metadata.get("manifest_sha256", "")[:12]}

@app.post("/predict")
async def predict(file: UploadFile = File(...), x_api_key: str | None = Header(default=None)):
    expected = os.getenv("ML_API_KEY", "")
    if expected and not secrets.compare_digest(x_api_key or "", expected):
        raise HTTPException(401, "Invalid API key.")
    if app.state.model is None:
        raise HTTPException(503, "Model unavailable. Run training first.")
    content = await file.read(MAX_BYTES + 1)
    await file.close()
    if len(content) > MAX_BYTES:
        raise HTTPException(413, "Maximum image size is 5 MB.")
    return await run_in_threadpool(infer, content)
