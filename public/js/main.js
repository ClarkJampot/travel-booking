/**
 * Main application script - loads utilities and initializes app
 * All utility functions are in utils/*.js files
 */

// Load all utilities
// Component loading: utils/componentLoader.js
// UI helpers: utils/uiHelpers.js  
// Renderers: utils/renderers.js
// Auth helpers: utils/authHelpers.js
// Location helpers: utils/locationHelpers.js
// Animations: utils/animations.js
// URL helpers: utils/urlHelpers.js

// Initialize app on DOM ready
document.addEventListener('DOMContentLoaded', function() {
  if (typeof updateNavigation === 'function') {
    updateNavigation();
  }
  if (typeof initScrollAnimations === 'function') {
    initScrollAnimations();
  }
  if (typeof initStickyNav === 'function') {
    initStickyNav();
  }
  if (typeof initLazyLoading === 'function') {
    initLazyLoading();
  }
});

// Form validation helpers
function validateRequiredFields(fieldIds = []) {
  const errors = [];
  fieldIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const value = (el.value || '').trim();
    if (!value) {
      errors.push(id);
      el.classList.add('is-invalid');
    } else {
      el.classList.remove('is-invalid');
    }
  });
  return errors;
}

function clearFieldValidation(fieldIds = []) {
  fieldIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('is-invalid');
  });
}
