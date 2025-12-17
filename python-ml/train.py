# python-ml/train.py
# Train a RandomForest pipeline from CSV file (dataset.csv) and save model.pkl
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, classification_report
import pickle
import sys
import os

CSV_PATH = os.path.join(os.path.dirname(__file__), "train.csv")  # Put train.csv here
MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")

def main():
    if not os.path.exists(CSV_PATH):
        print("train.csv not found in python-ml/. Please place your CSV named train.csv there.")
        return
    df = pd.read_csv(CSV_PATH)
    # Features selected from the ovarian cancer dataset
    feature_columns = ['Age', 'CA125_Level', 'HE4_Level', 'LDH_Level', 'Hemoglobin', 
                       'WBC', 'Platelets', 'Ovary_Size', 'Fatigue_Level', 'Pelvic_Pain',
                       'Abdominal_Bloating', 'Early_Satiety', 'Menstrual_Irregularities', 'Weight_Change']
    target_column = 'Probability_of_Cancer'
    
    for col in feature_columns + [target_column]:
        if col not in df.columns:
            print(f"Missing column: {col}")
            return
    df = df.dropna(subset=feature_columns + [target_column])
    X = df[feature_columns]
    y = (df[target_column] > 0.5).astype(int)  # Binary classification: risk > 50%

    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

    pipe = Pipeline([
        ('scaler', StandardScaler()),
        ('clf', RandomForestClassifier(n_estimators=200, random_state=42))
    ])

    pipe.fit(X_train, y_train)
    y_pred = pipe.predict(X_test)

    print("Accuracy:", accuracy_score(y_test, y_pred))
    print("Precision:", precision_score(y_test, y_pred, zero_division=0))
    print("Recall:", recall_score(y_test, y_pred, zero_division=0))
    print("F1-Score:", f1_score(y_test, y_pred, zero_division=0))
    print("\nClassification report:\n", classification_report(y_test, y_pred, zero_division=0))

    with open(MODEL_PATH, "wb") as f:
        pickle.dump(pipe, f)
    print(f"Saved model to {MODEL_PATH}")

    clf = pipe.named_steps['clf']
    importances = clf.feature_importances_
    features = feature_columns
    print("\nFeature importances:")
    for f_name, imp in zip(features, importances):
        print(f"{f_name}: {imp:.4f}")

if __name__ == "__main__":
    main()