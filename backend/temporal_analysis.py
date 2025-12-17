"""
Temporal Analysis Module for OvCare
Provides functions for calculating temporal features and trends
"""

import numpy as np
import pandas as pd
from datetime import datetime, timedelta


def calculate_velocity(current_value, previous_value, time_diff_days):
    """
    Calculate velocity (rate of change per day)
    
    Args:
        current_value: Current biomarker value
        previous_value: Previous biomarker value
        time_diff_days: Time difference in days
        
    Returns:
        Velocity (change per day)
    """
    if time_diff_days == 0 or previous_value is None or current_value is None:
        return 0.0
    return (current_value - previous_value) / time_diff_days


def calculate_acceleration(current_velocity, previous_velocity, time_diff_days):
    """
    Calculate acceleration (rate of change of velocity per day)
    
    Args:
        current_velocity: Current velocity
        previous_velocity: Previous velocity
        time_diff_days: Time difference in days
        
    Returns:
        Acceleration (change in velocity per day)
    """
    if time_diff_days == 0 or previous_velocity is None or current_velocity is None:
        return 0.0
    return (current_velocity - previous_velocity) / time_diff_days


def calculate_moving_average(values, window=7):
    """
    Calculate moving average over a window
    
    Args:
        values: List of values
        window: Window size (default 7 days)
        
    Returns:
        Moving average value
    """
    if not values or len(values) == 0:
        return 0.0
    
    values_to_use = values[-window:] if len(values) >= window else values
    return np.mean(values_to_use)


def calculate_std_dev(values, window=30):
    """
    Calculate standard deviation over a window
    
    Args:
        values: List of values
        window: Window size (default 30 days)
        
    Returns:
        Standard deviation
    """
    if not values or len(values) < 2:
        return 0.0
    
    values_to_use = values[-window:] if len(values) >= window else values
    return np.std(values_to_use)


def extract_temporal_features(biomarker_history):
    """
    Extract temporal features from biomarker history
    
    Args:
        biomarker_history: List of dicts with keys: ca125, he4, recorded_at
        
    Returns:
        Dictionary of temporal features
    """
    features = {
        'ca125_velocity': 0.0,
        'he4_velocity': 0.0,
        'ca125_acceleration': 0.0,
        'he4_acceleration': 0.0,
        'ca125_ma_7d': 0.0,
        'he4_ma_7d': 0.0,
        'ca125_ma_30d': 0.0,
        'he4_ma_30d': 0.0,
        'ca125_std_30d': 0.0,
        'he4_std_30d': 0.0,
        'ca125_he4_ratio': 0.0,
        'trend_direction': 0  # -1: decreasing, 0: stable, 1: increasing
    }
    
    if not biomarker_history or len(biomarker_history) == 0:
        return features
    
    # Sort by date
    history = sorted(biomarker_history, key=lambda x: x['recorded_at'])
    
    # Extract values
    ca125_values = [h['ca125'] for h in history]
    he4_values = [h['he4'] for h in history]
    
    # Current and previous values
    current_ca125 = ca125_values[-1]
    current_he4 = he4_values[-1]
    
    # Calculate CA125/HE4 ratio
    if current_he4 > 0:
        features['ca125_he4_ratio'] = current_ca125 / current_he4
    
    # Calculate moving averages
    features['ca125_ma_7d'] = calculate_moving_average(ca125_values, 7)
    features['he4_ma_7d'] = calculate_moving_average(he4_values, 7)
    features['ca125_ma_30d'] = calculate_moving_average(ca125_values, 30)
    features['he4_ma_30d'] = calculate_moving_average(he4_values, 30)
    
    # Calculate standard deviations
    features['ca125_std_30d'] = calculate_std_dev(ca125_values, 30)
    features['he4_std_30d'] = calculate_std_dev(he4_values, 30)
    
    # Calculate velocity if we have at least 2 data points
    if len(history) >= 2:
        prev_record = history[-2]
        current_record = history[-1]
        
        # Calculate time difference in days
        time_diff = (datetime.fromisoformat(str(current_record['recorded_at'])) - 
                    datetime.fromisoformat(str(prev_record['recorded_at']))).days
        
        if time_diff == 0:
            time_diff = 1  # Minimum 1 day to avoid division by zero
        
        prev_ca125 = prev_record['ca125']
        prev_he4 = prev_record['he4']
        
        features['ca125_velocity'] = calculate_velocity(current_ca125, prev_ca125, time_diff)
        features['he4_velocity'] = calculate_velocity(current_he4, prev_he4, time_diff)
    
    # Calculate acceleration if we have at least 3 data points
    if len(history) >= 3:
        prev2_record = history[-3]
        prev_record = history[-2]
        current_record = history[-1]
        
        # Calculate time differences
        time_diff_1 = (datetime.fromisoformat(str(prev_record['recorded_at'])) - 
                      datetime.fromisoformat(str(prev2_record['recorded_at']))).days
        time_diff_2 = (datetime.fromisoformat(str(current_record['recorded_at'])) - 
                      datetime.fromisoformat(str(prev_record['recorded_at']))).days
        
        if time_diff_1 == 0:
            time_diff_1 = 1
        if time_diff_2 == 0:
            time_diff_2 = 1
        
        # Calculate previous velocity
        prev_ca125_velocity = calculate_velocity(prev_record['ca125'], prev2_record['ca125'], time_diff_1)
        prev_he4_velocity = calculate_velocity(prev_record['he4'], prev2_record['he4'], time_diff_1)
        
        # Calculate acceleration
        features['ca125_acceleration'] = calculate_acceleration(
            features['ca125_velocity'], prev_ca125_velocity, time_diff_2
        )
        features['he4_acceleration'] = calculate_acceleration(
            features['he4_velocity'], prev_he4_velocity, time_diff_2
        )
    
    # Determine trend direction
    if features['ca125_velocity'] > 0.5 or features['he4_velocity'] > 0.5:
        features['trend_direction'] = 1  # Increasing
    elif features['ca125_velocity'] < -0.5 or features['he4_velocity'] < -0.5:
        features['trend_direction'] = -1  # Decreasing
    else:
        features['trend_direction'] = 0  # Stable
    
    return features


def adjust_risk_with_temporal_features(base_risk, temporal_features, velocity_threshold=1.0, accel_threshold=0.5):
    """
    Adjust risk score based on temporal features
    
    Args:
        base_risk: Base risk probability from ML model
        temporal_features: Dictionary of temporal features
        velocity_threshold: Threshold for velocity adjustment
        accel_threshold: Threshold for acceleration adjustment
        
    Returns:
        Adjusted risk probability
    """
    adjusted_risk = base_risk
    
    # Increase risk if rapid increase in biomarkers (high velocity)
    if temporal_features['ca125_velocity'] > velocity_threshold:
        adjusted_risk *= 1.15
    if temporal_features['he4_velocity'] > velocity_threshold:
        adjusted_risk *= 1.15
    
    # Increase risk if accelerating increase (high acceleration)
    if temporal_features['ca125_acceleration'] > accel_threshold:
        adjusted_risk *= 1.20
    if temporal_features['he4_acceleration'] > accel_threshold:
        adjusted_risk *= 1.20
    
    # Increase risk if high standard deviation (unstable)
    if temporal_features['ca125_std_30d'] > 10.0:
        adjusted_risk *= 1.10
    if temporal_features['he4_std_30d'] > 20.0:
        adjusted_risk *= 1.10
    
    # Cap at 100%
    return min(adjusted_risk, 1.0)


def get_risk_tier(probability):
    """
    Get risk tier from probability
    
    Args:
        probability: Risk probability (0-1)
        
    Returns:
        Risk tier string: 'Low', 'Moderate', 'High', or 'Critical'
    """
    if probability < 0.25:
        return 'Low'
    elif probability < 0.50:
        return 'Moderate'
    elif probability < 0.75:
        return 'High'
    else:
        return 'Critical'
