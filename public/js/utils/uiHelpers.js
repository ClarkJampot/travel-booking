/**
 * UI helper functions
 */

/**
 * Render status message HTML
 * @param {string} type - Status type (success, error, info, warning)
 * @param {string} message - Status message
 * @returns {string} HTML string
 */
function renderStatus(type = 'info', message = '') {
  if (!message) return '';
  const variant = {
    success: 'success',
    error: 'danger',
    info: 'info',
    warning: 'warning'
  }[type] || 'info';
  return `<div class="alert alert-${variant}" role="alert">${escapeHtml(message)}</div>`;
}

/**
 * Set status message in an element
 * @param {string} elementId - Element ID
 * @param {string} type - Status type
 * @param {string} message - Status message
 */
function setStatus(elementId, type, message) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.innerHTML = renderStatus(type, message);
}

/**
 * Show loading state
 * @param {string} elementId - Element ID
 * @param {object} options - Loading spinner options
 */
function showLoading(elementId, options = {}) {
  const element = document.getElementById(elementId);
  if (element) {
    // Use LoadingSpinner component if available, otherwise fallback
    if (typeof renderLoadingSpinner === 'function') {
      element.innerHTML = renderLoadingSpinner(options);
    } else {
      // Fallback
      element.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    }
  }
}

/**
 * Show error message
 * @param {string} elementId - Element ID
 * @param {string} message - Error message
 */
function showError(elementId, message) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = `<div class="alert alert-danger" role="alert">${escapeHtml(message)}</div>`;
  }
}

/**
 * Show success message
 * @param {string} elementId - Element ID
 * @param {string} message - Success message
 */
function showSuccess(elementId, message) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = `<div class="alert alert-success" role="alert">${escapeHtml(message)}</div>`;
  }
}

/**
 * Clear status message
 * @param {string} elementId - Element ID
 */
function clearStatus(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = '';
  }
}

/**
 * Toast notification system
 * @param {string} message - Toast message
 * @param {string} type - Toast type (info, success, error, warning)
 * @param {number} duration - Duration in milliseconds
 */
function showToast(message, type = 'info', duration = 3000) {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type} slide-in-down`;
  toast.innerHTML = `
    <div class="toast-content">
      <span class="toast-message">${escapeHtml(message)}</span>
      <button class="toast-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('fade-out');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

function showSuccessToast(message) {
  showToast(message, 'success', 3000);
}

function showErrorToast(message) {
  showToast(message, 'error', 4000);
}

function smoothScrollTo(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
