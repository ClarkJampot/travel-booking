// Main.js - Common utilities for travel booking app

// Determine API base path dynamically based on current location
const API_BASE = (() => {
  const path = window.location.pathname;
  // If we're in /travel-booking/public/ or /travel-booking/, use /travel-booking/api
  if (path.includes('/travel-booking')) {
    return '/travel-booking/api';
  }
  // Otherwise, use /api (for development or different setups)
  return '/api';
})();

// Normalize image URLs to work with subdirectory installations
function normalizeImageUrl(url) {
  if (!url) return 'uploads/placeholder.svg';
  
  // If URL starts with /, make it relative to base path
  if (url.startsWith('/')) {
    const path = window.location.pathname;
    
    // Check if path contains 'travel-booking' (subdirectory installation)
    if (path.includes('/travel-booking')) {
      // If URL doesn't already have the base path, prepend it
      if (!url.startsWith('/travel-booking')) {
        return '/travel-booking' + url;
      }
    }
    // For root installation, return as-is
    return url;
  }
  
  // Already relative, return as-is
  return url;
}

// API helper functions
async function apiCall(endpoint, options = {}) {
  const token = localStorage.getItem('token');
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  try {
    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers
    });
    
    let data;
    try {
      data = await response.json();
    } catch (jsonError) {
      // If response is not JSON, get text
      const text = await response.text();
      throw new Error(`Server returned non-JSON response (${response.status}): ${text.substring(0, 200)}`);
    }
    
    if (!response.ok) {
      const errorMsg = data.error || data.message || `HTTP ${response.status}: ${response.statusText}`;
      throw new Error(errorMsg);
    }
    
    return data;
  } catch (error) {
    console.error('API Error:', error);
    console.error('Endpoint:', `${API_BASE}${endpoint}`);
    throw error;
  }
}

// Get current user
function getCurrentUser() {
  const userStr = localStorage.getItem('user');
  return userStr ? JSON.parse(userStr) : null;
}

// Check if user is authenticated
function isAuthenticated() {
  return !!localStorage.getItem('token');
}

// Show loading state
function showLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
  }
}

// Show error message
function showError(elementId, message) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
  }
}

// Show success message
function showSuccess(elementId, message) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = `<div class="alert alert-success" role="alert">${message}</div>`;
  }
}

// Clear status message
function clearStatus(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = '';
  }
}

// Format price
function formatPrice(price) {
  const formatted = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(price);
  return formatted.replace(/\.00(?=\s|$)/, '');
}

// Format percentage (removes trailing zeros)
function formatPercent(value) {
  if (value == null || value === '') return '0';
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return num % 1 === 0 ? num.toString() : num.toFixed(2).replace(/\.?0+$/, '');
}

// Check if item is promoted (handles SQL Server BIT field formats)
function isPromoted(ad) {
  if (ad == null || ad === undefined) return false;
  // Handle various formats: integer 1, boolean true, string "1", or truthy value
  if (ad === 1 || ad === true || ad === '1') return true;
  if (ad === 0 || ad === false || ad === '0') return false;
  // Convert to number as fallback
  const num = Number(ad);
  return !isNaN(num) && num === 1;
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

// Update navigation based on auth status
function updateNavigation() {
  const user = getCurrentUser();
  const loginBtn = document.getElementById('loginBtn');
  const registerBtn = document.getElementById('registerBtn');
  const profileBtn = document.getElementById('profileBtn');
  const bookingsBtn = document.getElementById('bookingsBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  
  if (user) {
    if (loginBtn) loginBtn.style.display = 'none';
    if (registerBtn) registerBtn.style.display = 'none';
    if (profileBtn) profileBtn.style.display = 'block';
    if (bookingsBtn) bookingsBtn.style.display = 'block';
    if (logoutBtn) logoutBtn.style.display = 'block';
  } else {
    if (loginBtn) loginBtn.style.display = 'block';
    if (registerBtn) registerBtn.style.display = 'block';
    if (profileBtn) profileBtn.style.display = 'none';
    if (bookingsBtn) bookingsBtn.style.display = 'none';
    if (logoutBtn) logoutBtn.style.display = 'none';
  }
}

// Initialize navigation on page load
document.addEventListener('DOMContentLoaded', function() {
  updateNavigation();
  initScrollAnimations();
  initStickyNav();
  initLazyLoading();
});

// Scroll-triggered animations using Intersection Observer
let revealObserver = null;

function initScrollAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
        revealObserver.unobserve(entry.target);
      }
    });
  }, observerOptions);

  // Observe all existing reveal elements
  observeRevealElements();
}

// Function to observe reveal elements (can be called after dynamic content is added)
function observeRevealElements() {
  if (!revealObserver) return;
  
  // Observe all reveal elements that aren't already active
  document.querySelectorAll('.reveal:not(.active)').forEach(el => {
    revealObserver.observe(el);
  });
}

// Sticky navigation with scroll effect
function initStickyNav() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  let lastScroll = 0;
  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
  });
}

// Lazy loading for images
function initLazyLoading() {
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.classList.add('fade-in');
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      }
    });
  });

  document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}

// Toast notification system
function showToast(message, type = 'info', duration = 3000) {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type} slide-in-down`;
  toast.innerHTML = `
    <div class="toast-content">
      <span class="toast-message">${message}</span>
      <button class="toast-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('fade-out');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// Enhanced showSuccess with toast
function showSuccessToast(message) {
  showToast(message, 'success', 3000);
}

// Enhanced showError with toast
function showErrorToast(message) {
  showToast(message, 'error', 4000);
}

// Smooth scroll to element
function smoothScrollTo(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// Debounce function for search/filter
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

// Initialize cascading city dropdown based on province selection
function initCascadingCityDropdown(provinceSelectId, citySelectId) {
  const provinceSelect = document.getElementById(provinceSelectId);
  const citySelect = document.getElementById(citySelectId);
  
  if (!provinceSelect || !citySelect) return;
  
  // Initially disable city dropdown if no province is selected
  const initialProvinceId = provinceSelect.value;
  if (initialProvinceId) {
    citySelect.disabled = false;
    loadCitiesForProvince(initialProvinceId, citySelect);
  } else {
    citySelect.disabled = true;
    citySelect.innerHTML = '<option value="">Select Province First</option>';
  }
  
  provinceSelect.addEventListener('change', async function() {
    const provinceId = this.value;
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    if (!provinceId) {
      // Disable city dropdown if no province selected
      citySelect.disabled = true;
      citySelect.innerHTML = '<option value="">Select Province First</option>';
      return;
    }
    
    // Enable city dropdown and load cities for selected province
    citySelect.disabled = false;
    await loadCitiesForProvince(provinceId, citySelect);
  });
}

// Load cities for a specific province
async function loadCitiesForProvince(provinceId, citySelect) {
  try {
    const cities = await apiCall(`/cities?province_id=${provinceId}`);
    if (cities.results && cities.results.length > 0) {
      cities.results.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading cities:', error);
  }
}

// Load all cities
async function loadAllCities(citySelect) {
  try {
    const cities = await apiCall('/cities');
    if (cities.results && cities.results.length > 0) {
      cities.results.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading cities:', error);
  }
}
