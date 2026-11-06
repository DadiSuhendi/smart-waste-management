import io
from pathlib import Path
import pytest
from fastapi.testclient import TestClient
from api import app
from model import ROOT

@pytest.fixture(scope="module")
def client():
    with TestClient(app) as client:
        yield client

def test_health_has_loaded_model(client):
    assert client.get("/health").json()["status"] == "ready"

def test_real_image_returns_normalized_three_class_prediction(client):
    path = next((ROOT / "data/plastic").glob("*.jpg"))
    result = client.post("/predict", files={"file": ("waste.jpg", path.read_bytes(), "image/jpeg")})
    assert result.status_code == 200
    data = result.json()
    assert data["category"] in {"organic", "paper", "plastic"}
    assert 0 <= data["confidence"] <= 1
    assert abs(sum(data["probabilities"].values()) - 1) < 0.0001
    assert isinstance(data["needs_review"], bool)

def test_invalid_file_is_rejected(client):
    result = client.post("/predict", files={"file": ("fake.jpg", b"not an image", "image/jpeg")})
    assert result.status_code == 422

def test_upload_size_is_bounded(client):
    result = client.post("/predict", files={"file": ("large.jpg", b"x"*(5*1024*1024+1), "image/jpeg")})
    assert result.status_code == 413

def test_api_key_is_enforced_when_configured(client, monkeypatch):
    monkeypatch.setenv("ML_API_KEY", "test-only")
    assert client.post("/predict", files={"file": ("x.jpg", b"x")}).status_code == 401

def test_missing_model_returns_service_unavailable(client):
    model = app.state.model
    try:
        app.state.model = None
        assert client.get("/health").json()["status"] == "model_missing"
        assert client.post("/predict", files={"file": ("x.jpg", b"x")}).status_code == 503
    finally:
        app.state.model = model

def test_missing_photo_is_validation_error(client):
    assert client.post("/predict").status_code == 422
