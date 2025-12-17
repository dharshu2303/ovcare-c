/**
 * API Communication Module for OvCare
 * Handles all API calls to the Flask ML backend
 */

const OvCareAPI = {
    baseURL: 'http://127.0.0.1:5000',
    
    /**
     * Make prediction with biomarker data
     */
    async predict(data) {
        try {
            const response = await fetch(`${this.baseURL}/predict`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Prediction error:', error);
            return { error: error.message };
        }
    },
    
    /**
     * Make prediction with temporal analysis
     */
    async predictTemporal(data) {
        try {
            const response = await fetch(`${this.baseURL}/predict-temporal`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Temporal prediction error:', error);
            return { error: error.message };
        }
    },
    
    /**
     * Get model information
     */
    async getModelInfo() {
        try {
            const response = await fetch(`${this.baseURL}/model-info`, {
                method: 'GET',
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Model info error:', error);
            return { error: error.message };
        }
    },
    
    /**
     * Health check
     */
    async healthCheck() {
        try {
            const response = await fetch(`${this.baseURL}/health`, {
                method: 'GET',
            });
            
            if (!response.ok) {
                return { status: 'unhealthy' };
            }
            
            return await response.json();
        } catch (error) {
            console.error('Health check error:', error);
            return { status: 'unhealthy', error: error.message };
        }
    }
};

/**
 * Helper function to get risk tier color
 */
function getRiskTierColor(tier) {
    const colors = {
        'Low': '#10b981',
        'Moderate': '#f59e0b',
        'High': '#ef4444',
        'Critical': '#dc2626'
    };
    return colors[tier] || '#6b7280';
}

/**
 * Helper function to format probability as percentage
 */
function formatProbability(probability) {
    if (probability === null || probability === undefined) {
        return 'N/A';
    }
    return `${(probability * 100).toFixed(1)}%`;
}

/**
 * Helper function to show loading state
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    }
}

/**
 * Helper function to hide loading state
 */
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '';
    }
}

/**
 * Show error message
 */
function showError(message, elementId = 'error-container') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

/**
 * Show success message
 */
function showSuccess(message, elementId = 'success-container') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

/**
 * Debounce function for input validation
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format date to readable string
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OvCareAPI, getRiskTierColor, formatProbability };
}
