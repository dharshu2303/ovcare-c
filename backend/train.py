"""
Enhanced Training Script for OvCare
Trains Gradient Boosting model with temporal feature engineering
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split, GridSearchCV
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, classification_report, roc_auc_score
import pickle
import sys
import os
from datetime import datetime
import json

# Try to import XGBoost, fallback to GradientBoostingClassifier if not available
try:
    import xgboost as xgb
    USE_XGBOOST = True
except ImportError:
    print("XGBoost not available, using GradientBoostingClassifier instead")
    USE_XGBOOST = False

CSV_PATH = os.path.join(os.path.dirname(__file__), "train.csv")
MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
METADATA_PATH = os.path.join(os.path.dirname(__file__), "model_metadata.json")


def generate_synthetic_temporal_features(df):
    """
    Generate synthetic temporal features from the dataset
    
    NOTE: This is a placeholder for demonstration purposes. In production,
    temporal features should be calculated from actual time-series data.
    Replace this with real temporal sequence analysis when patient history data is available.
    
    For real implementation, calculate velocity as:
    velocity = (current_value - previous_value) / time_difference_in_days
    """
    # Simulate velocity (rate of change) as a function of current values
    # TODO: Replace with actual temporal calculations from patient history
    df['CA125_velocity'] = df['CA125_Level'] * np.random.uniform(-0.1, 0.1, len(df))
    df['HE4_velocity'] = df['HE4_Level'] * np.random.uniform(-0.1, 0.1, len(df))
    
    # Simulate acceleration
    df['CA125_acceleration'] = df['CA125_velocity'] * np.random.uniform(-0.05, 0.05, len(df))
    df['HE4_acceleration'] = df['HE4_velocity'] * np.random.uniform(-0.05, 0.05, len(df))
    
    # Calculate ratio features
    df['CA125_HE4_ratio'] = df['CA125_Level'] / (df['HE4_Level'] + 1e-6)
    
    # Simulate moving averages (as slight variations of current values)
    df['CA125_ma_7d'] = df['CA125_Level'] * np.random.uniform(0.95, 1.05, len(df))
    df['HE4_ma_7d'] = df['HE4_Level'] * np.random.uniform(0.95, 1.05, len(df))
    df['CA125_ma_30d'] = df['CA125_Level'] * np.random.uniform(0.90, 1.10, len(df))
    df['HE4_ma_30d'] = df['HE4_Level'] * np.random.uniform(0.90, 1.10, len(df))
    
    # Simulate standard deviations
    df['CA125_std_30d'] = df['CA125_Level'] * np.random.uniform(0.1, 0.3, len(df))
    df['HE4_std_30d'] = df['HE4_Level'] * np.random.uniform(0.1, 0.3, len(df))
    
    return df


def main():
    print("=" * 80)
    print("Enhanced OvCare Training System - Temporal Analysis")
    print("=" * 80)
    
    if not os.path.exists(CSV_PATH):
        print(f"Error: train.csv not found at {CSV_PATH}")
        return
    
    print(f"\nLoading dataset from {CSV_PATH}...")
    df = pd.read_csv(CSV_PATH)
    print(f"Dataset loaded: {len(df)} records")
    
    # Core features from the dataset
    base_features = ['Age', 'CA125_Level', 'HE4_Level', 'LDH_Level', 'Hemoglobin', 
                     'WBC', 'Platelets', 'Ovary_Size', 'Fatigue_Level', 'Pelvic_Pain',
                     'Abdominal_Bloating', 'Early_Satiety', 'Menstrual_Irregularities', 
                     'Weight_Change']
    
    target_column = 'Probability_of_Cancer'
    
    # Check for missing columns
    missing_cols = [col for col in base_features + [target_column] if col not in df.columns]
    if missing_cols:
        print(f"Error: Missing columns: {missing_cols}")
        return
    
    print("\nGenerating synthetic temporal features...")
    df = generate_synthetic_temporal_features(df)
    
    # Extended features with temporal features
    temporal_features = ['CA125_velocity', 'HE4_velocity', 'CA125_acceleration', 'HE4_acceleration',
                        'CA125_HE4_ratio', 'CA125_ma_7d', 'HE4_ma_7d', 'CA125_ma_30d', 'HE4_ma_30d',
                        'CA125_std_30d', 'HE4_std_30d']
    
    all_features = base_features + temporal_features
    
    # Drop rows with missing values
    df = df.dropna(subset=all_features + [target_column])
    print(f"After cleaning: {len(df)} records")
    
    # Prepare features and target
    X = df[all_features]
    y = (df[target_column] > 0.5).astype(int)  # Binary classification: risk > 50%
    
    print(f"\nClass distribution:")
    print(f"  Class 0 (Low Risk): {(y == 0).sum()} ({(y == 0).sum() / len(y) * 100:.1f}%)")
    print(f"  Class 1 (High Risk): {(y == 1).sum()} ({(y == 1).sum() / len(y) * 100:.1f}%)")
    
    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
    
    print(f"\nTraining set: {len(X_train)} samples")
    print(f"Test set: {len(X_test)} samples")
    
    # Create model pipeline
    print("\nTraining model...")
    
    if USE_XGBOOST:
        print("Using XGBoost Classifier")
        classifier = xgb.XGBClassifier(
            n_estimators=200,
            max_depth=6,
            learning_rate=0.1,
            random_state=42,
            eval_metric='logloss'
        )
    else:
        print("Using Gradient Boosting Classifier")
        classifier = GradientBoostingClassifier(
            n_estimators=200,
            max_depth=6,
            learning_rate=0.1,
            random_state=42
        )
    
    pipe = Pipeline([
        ('scaler', StandardScaler()),
        ('clf', classifier)
    ])
    
    # Train model
    pipe.fit(X_train, y_train)
    
    # Evaluate
    print("\n" + "=" * 80)
    print("Model Evaluation")
    print("=" * 80)
    
    y_pred = pipe.predict(X_test)
    y_pred_proba = pipe.predict_proba(X_test)[:, 1]
    
    accuracy = accuracy_score(y_test, y_pred)
    precision = precision_score(y_test, y_pred, zero_division=0)
    recall = recall_score(y_test, y_pred, zero_division=0)
    f1 = f1_score(y_test, y_pred, zero_division=0)
    roc_auc = roc_auc_score(y_test, y_pred_proba)
    
    print(f"\nAccuracy:  {accuracy:.4f}")
    print(f"Precision: {precision:.4f}")
    print(f"Recall:    {recall:.4f}")
    print(f"F1-Score:  {f1:.4f}")
    print(f"ROC-AUC:   {roc_auc:.4f}")
    
    print("\n" + "-" * 80)
    print("Classification Report:")
    print("-" * 80)
    print(classification_report(y_test, y_pred, zero_division=0, target_names=['Low Risk', 'High Risk']))
    
    # Feature importance
    print("\n" + "=" * 80)
    print("Feature Importance (Top 15)")
    print("=" * 80)
    
    clf = pipe.named_steps['clf']
    if hasattr(clf, 'feature_importances_'):
        importances = clf.feature_importances_
        feature_importance = sorted(
            zip(all_features, importances),
            key=lambda x: x[1],
            reverse=True
        )
        
        for i, (feature, importance) in enumerate(feature_importance[:15], 1):
            print(f"{i:2d}. {feature:30s} {importance:.4f}")
    
    # Save model
    print(f"\n" + "=" * 80)
    print("Saving Model")
    print("=" * 80)
    
    with open(MODEL_PATH, "wb") as f:
        pickle.dump(pipe, f)
    print(f"Model saved to: {MODEL_PATH}")
    
    # Save metadata
    metadata = {
        'model_version': '2.0.0',
        'training_date': datetime.now().isoformat(),
        'features': all_features,
        'base_features': base_features,
        'temporal_features': temporal_features,
        'model_type': 'XGBoost' if USE_XGBOOST else 'GradientBoosting',
        'metrics': {
            'accuracy': float(accuracy),
            'precision': float(precision),
            'recall': float(recall),
            'f1_score': float(f1),
            'roc_auc': float(roc_auc)
        },
        'feature_importance': {feat: float(imp) for feat, imp in feature_importance[:15]} if hasattr(clf, 'feature_importances_') else {}
    }
    
    with open(METADATA_PATH, 'w') as f:
        json.dump(metadata, f, indent=2)
    print(f"Metadata saved to: {METADATA_PATH}")
    
    print("\n" + "=" * 80)
    print("Training Complete!")
    print("=" * 80)


if __name__ == "__main__":
    main()
