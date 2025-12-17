# python-ml/app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import numpy as np
import os

app = Flask(__name__)
CORS(app)

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
if not os.path.exists(MODEL_PATH):
    raise RuntimeError("model.pkl not found. Run train.py with dataset.csv to create model.pkl")

with open(MODEL_PATH, "rb") as f:
    model = pickle.load(f)  # pipeline with scaler + classifier

@app.route("/predict", methods=["POST"])
def predict():
    try:
        data = request.get_json(force=True)
        # Extract features from ovarian cancer dataset
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
        
        X = np.array([[age, ca125, he4, ldh, hemoglobin, wbc, platelets, 
                      ovary_size, fatigue, pelvic_pain, abdominal_bloating, 
                      early_satiety, menstrual_irreg, weight_change]])
        pred = model.predict(X)[0]
        # Compute probability
        prob = None
        if hasattr(model, "predict_proba"):
            prob = float(model.predict_proba(X)[0,1])
        return jsonify({"risk": int(pred), "probability": prob})
    except Exception as e:
        return jsonify({"error": str(e)}), 400

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)