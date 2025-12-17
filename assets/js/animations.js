/**
 * Animation utilities for OvCare
 * Smooth transitions and interactive animations
 */

/**
 * Initialize AOS (Animate On Scroll) if library is loaded
 */
function initAOS() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    }
}

/**
 * Animate number counter
 */
function animateCounter(elementId, targetValue, duration = 2000, suffix = '') {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const startValue = 0;
    const increment = targetValue / (duration / 16);
    let currentValue = startValue;
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            element.textContent = targetValue.toFixed(1) + suffix;
            clearInterval(timer);
        } else {
            element.textContent = currentValue.toFixed(1) + suffix;
        }
    }, 16);
}

/**
 * Fade in element
 */
function fadeIn(element, duration = 500) {
    element.style.opacity = 0;
    element.style.display = 'block';
    
    let opacity = 0;
    const increment = 16 / duration;
    
    const timer = setInterval(() => {
        opacity += increment;
        if (opacity >= 1) {
            element.style.opacity = 1;
            clearInterval(timer);
        } else {
            element.style.opacity = opacity;
        }
    }, 16);
}

/**
 * Fade out element
 */
function fadeOut(element, duration = 500) {
    let opacity = 1;
    const decrement = 16 / duration;
    
    const timer = setInterval(() => {
        opacity -= decrement;
        if (opacity <= 0) {
            element.style.opacity = 0;
            element.style.display = 'none';
            clearInterval(timer);
        } else {
            element.style.opacity = opacity;
        }
    }, 16);
}

/**
 * Slide down element
 */
function slideDown(element, duration = 500) {
    element.style.height = '0px';
    element.style.overflow = 'hidden';
    element.style.display = 'block';
    
    const targetHeight = element.scrollHeight;
    const increment = targetHeight / (duration / 16);
    let currentHeight = 0;
    
    const timer = setInterval(() => {
        currentHeight += increment;
        if (currentHeight >= targetHeight) {
            element.style.height = 'auto';
            element.style.overflow = 'visible';
            clearInterval(timer);
        } else {
            element.style.height = currentHeight + 'px';
        }
    }, 16);
}

/**
 * Slide up element
 */
function slideUp(element, duration = 500) {
    const startHeight = element.scrollHeight;
    const decrement = startHeight / (duration / 16);
    let currentHeight = startHeight;
    
    element.style.overflow = 'hidden';
    
    const timer = setInterval(() => {
        currentHeight -= decrement;
        if (currentHeight <= 0) {
            element.style.display = 'none';
            element.style.height = 'auto';
            clearInterval(timer);
        } else {
            element.style.height = currentHeight + 'px';
        }
    }, 16);
}

/**
 * Pulse animation for important elements
 */
function pulseElement(elementId, times = 3) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    let count = 0;
    const interval = setInterval(() => {
        element.classList.toggle('pulse');
        count++;
        if (count >= times * 2) {
            clearInterval(interval);
            element.classList.remove('pulse');
        }
    }, 500);
}

/**
 * Shake animation for errors
 */
function shakeElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.style.animation = 'shake 0.5s';
    setTimeout(() => {
        element.style.animation = '';
    }, 500);
}

// Add shake keyframe
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

/**
 * Show toast notification
 */
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `glass-card toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 16px 20px;
        border-radius: 12px;
        min-width: 250px;
        max-width: 400px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease;
    `;
    
    const icon = {
        'success': '<i class="fas fa-check-circle" style="color: #10b981;"></i>',
        'error': '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>',
        'warning': '<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>',
        'info': '<i class="fas fa-info-circle" style="color: #6366f1;"></i>'
    };
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            ${icon[type]}
            <span style="color: #f1f5f9; flex: 1;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #cbd5e1; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Add toast animations
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(toastStyle);

/**
 * Initialize page animations
 */
document.addEventListener('DOMContentLoaded', function() {
    initAOS();
    
    // Add fade-in class to main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
});
