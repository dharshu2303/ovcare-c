"""
Enhanced Flask API for OvCare
Provides ML predictions with temporal analysis
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import numpy as np
import os
import json
from datetime import datetime
import sys

# Import temporal analysis module
from temporal_analysis import (
    extract_temporal_features,
    adjust_risk_with_temporal_features,
    get_risk_tier
)

app = Flask(__name__)
CORS(app)

# Load model
MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
METADATA_PATH = os.path.join(os.path.dirname(__file__), "model_metadata.json")

if not os.path.exists(MODEL_PATH):
    raise RuntimeError(f"model.pkl not found at {MODEL_PATH}. Run train.py first.")

with open(MODEL_PATH, "rb") as f:
    model = pickle.load(f)

# Load metadata if available
metadata = {}
if os.path.exists(METADATA_PATH):
    with open(METADATA_PATH, 'r') as f:
        metadata = json.load(f)

print("=" * 80)
print("OvCare ML API Server")
print("=" * 80)
print(f"Model loaded from: {MODEL_PATH}")
if metadata:
    print(f"Model version: {metadata.get('model_version', 'Unknown')}")
    print(f"Model type: {metadata.get('model_type', 'Unknown')}")
    print(f"Training date: {metadata.get('training_date', 'Unknown')}")
    metrics = metadata.get('metrics', {})
    if metrics:
        print(f"Model accuracy: {metrics.get('accuracy', 0):.4f}")
print("=" * 80)


def get_default_temporal_features():
    """Get default temporal features when no history is available"""
    return {
        'CA125_velocity': 0.0,
        'HE4_velocity': 0.0,
        'CA125_acceleration': 0.0,
        'HE4_acceleration': 0.0,
        'CA125_HE4_ratio': 0.0,
        'CA125_ma_7d': 0.0,
        'HE4_ma_7d': 0.0,
        'CA125_ma_30d': 0.0,
        'HE4_ma_30d': 0.0,
        'CA125_std_30d': 0.0,
        'HE4_std_30d': 0.0
    }


def extract_feature_importance(top_n=10):
    """Extract top N feature importances"""
    if not metadata or 'feature_importance' not in metadata:
        return []
    
    feature_imp = metadata['feature_importance']
    sorted_features = sorted(feature_imp.items(), key=lambda x: x[1], reverse=True)
    return sorted_features[:top_n]


@app.route("/", methods=["GET"])
def home():
    """API home endpoint"""
    return jsonify({
        "service": "OvCare ML API",
        "version": metadata.get('model_version', '2.0.0'),
        "status": "running",
        "endpoints": {
            "/predict": "POST - Make risk prediction",
            "/predict-temporal": "POST - Make prediction with temporal analysis",
            "/model-info": "GET - Get model information",
            "/health": "GET - Health check"
        }
    })


@app.route("/health", methods=["GET"])
def health():
    """Health check endpoint"""
    return jsonify({"status": "healthy", "timestamp": datetime.now().isoformat()})


@app.route("/model-info", methods=["GET"])
def model_info():
    """Get model information"""
    return jsonify({
        "model_version": metadata.get('model_version', 'Unknown'),
        "model_type": metadata.get('model_type', 'Unknown'),
        "training_date": metadata.get('training_date', 'Unknown'),
        "features": metadata.get('features', []),
        "metrics": metadata.get('metrics', {}),
        "top_features": extract_feature_importance(15)
    })


@app.route("/predict", methods=["POST"])
def predict():
    """
    Basic prediction endpoint (backward compatible)
    Accepts biomarker data and returns risk prediction
    """
    try:
        data = request.get_json(force=True)
        
        # Extract base features
        age = float(data.get("Age", 50))
        ca125 = float(data.get("CA125_Level", 35))
        he4 = float(data.get("HE4_Level", 100))
        ldh = float(data.get("LDH_Level", 180))
        hemoglobin = float(data.get("Hemoglobin", 13))
        wbc = float(data.get("WBC", 7000))
        platelets = float(data.get("Platelets", 250000))
        ovary_size = float(data.get("Ovary_Size", 3.5))
        fatigue = float(data.get("Fatigue_Level", 5))
        pelvic_pain = float(data.get("Pelvic_Pain", 0))
        abdominal_bloating = float(data.get("Abdominal_Bloating", 0))
        early_satiety = float(data.get("Early_Satiety", 0))
        menstrual_irreg = float(data.get("Menstrual_Irregularities", 0))
        weight_change = float(data.get("Weight_Change", 0))
        
        # Get temporal features (with defaults if not provided)
        temporal = get_default_temporal_features()
        if 'temporal_features' in data:
            temporal.update(data['temporal_features'])
        
        # Construct feature vector
        X = np.array([[
            age, ca125, he4, ldh, hemoglobin, wbc, platelets, 
            ovary_size, fatigue, pelvic_pain, abdominal_bloating, 
            early_satiety, menstrual_irreg, weight_change,
            temporal['CA125_velocity'],
            temporal['HE4_velocity'],
            temporal['CA125_acceleration'],
            temporal['HE4_acceleration'],
            temporal['CA125_HE4_ratio'] if temporal['CA125_HE4_ratio'] else (ca125 / (he4 + 1e-6)),
            temporal['CA125_ma_7d'] if temporal['CA125_ma_7d'] else ca125,
            temporal['HE4_ma_7d'] if temporal['HE4_ma_7d'] else he4,
            temporal['CA125_ma_30d'] if temporal['CA125_ma_30d'] else ca125,
            temporal['HE4_ma_30d'] if temporal['HE4_ma_30d'] else he4,
            temporal['CA125_std_30d'],
            temporal['HE4_std_30d']
        ]])
        
        # Make prediction
        pred = model.predict(X)[0]
        prob = None
        confidence = 0.5
        
        if hasattr(model, "predict_proba"):
            proba = model.predict_proba(X)[0]
            prob = float(proba[1])
            confidence = float(max(proba))
        
        # Determine risk tier
        risk_tier = get_risk_tier(prob if prob else 0.5)
        
        # Get top influencing factors
        top_features = extract_feature_importance(5)
        
        return jsonify({
            "risk": int(pred),
            "probability": prob,
            "confidence": confidence,
            "risk_tier": risk_tier,
            "top_features": top_features,
            "model_version": metadata.get('model_version', '2.0.0')
        })
        
    except Exception as e:
        return jsonify({"error": str(e)}), 400


@app.route("/predict-temporal", methods=["POST"])
def predict_temporal():
    """
    Advanced prediction with temporal analysis
    Accepts biomarker data with history and returns enhanced risk prediction
    """
    try:
        data = request.get_json(force=True)
        
        # Extract base features
        age = float(data.get("Age", 50))
        ca125 = float(data.get("CA125_Level", 35))
        he4 = float(data.get("HE4_Level", 100))
        ldh = float(data.get("LDH_Level", 180))
        hemoglobin = float(data.get("Hemoglobin", 13))
        wbc = float(data.get("WBC", 7000))
        platelets = float(data.get("Platelets", 250000))
        ovary_size = float(data.get("Ovary_Size", 3.5))
        fatigue = float(data.get("Fatigue_Level", 5))
        pelvic_pain = float(data.get("Pelvic_Pain", 0))
        abdominal_bloating = float(data.get("Abdominal_Bloating", 0))
        early_satiety = float(data.get("Early_Satiety", 0))
        menstrual_irreg = float(data.get("Menstrual_Irregularities", 0))
        weight_change = float(data.get("Weight_Change", 0))
        
        # Extract temporal features from history
        biomarker_history = data.get('history', [])
        temporal_features = extract_temporal_features(biomarker_history)
        
        # Construct feature vector
        X = np.array([[
            age, ca125, he4, ldh, hemoglobin, wbc, platelets, 
            ovary_size, fatigue, pelvic_pain, abdominal_bloating, 
            early_satiety, menstrual_irreg, weight_change,
            temporal_features['ca125_velocity'],
            temporal_features['he4_velocity'],
            temporal_features['ca125_acceleration'],
            temporal_features['he4_acceleration'],
            temporal_features['ca125_he4_ratio'],
            temporal_features['ca125_ma_7d'],
            temporal_features['he4_ma_7d'],
            temporal_features['ca125_ma_30d'],
            temporal_features['he4_ma_30d'],
            temporal_features['ca125_std_30d'],
            temporal_features['he4_std_30d']
        ]])
        
        # Make base prediction
        pred = model.predict(X)[0]
        base_prob = 0.5
        
        if hasattr(model, "predict_proba"):
            proba = model.predict_proba(X)[0]
            base_prob = float(proba[1])
        
        # Adjust risk with temporal analysis
        adjusted_prob = adjust_risk_with_temporal_features(base_prob, temporal_features)
        
        # Determine risk tier
        risk_tier = get_risk_tier(adjusted_prob)
        
        # Calculate confidence
        confidence = float(max(proba)) if hasattr(model, "predict_proba") else 0.5
        
        # Get top influencing factors
        top_features = extract_feature_importance(5)
        
        return jsonify({
            "risk": int(pred),
            "probability": adjusted_prob,
            "base_probability": base_prob,
            "confidence": confidence,
            "risk_tier": risk_tier,
            "temporal_features": temporal_features,
            "top_features": top_features,
            "model_version": metadata.get('model_version', '2.0.0'),
            "trend_direction": temporal_features['trend_direction']
        })
        
    except Exception as e:
        return jsonify({"error": str(e)}), 400


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port, debug=True)
