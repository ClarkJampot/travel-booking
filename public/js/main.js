// Main.js - Common utilities for travel booking app
// Note: API utilities are in utils/api.js, formatters in utils/formatters.js
// Components are in components/*.js
// These should be loaded before main.js in HTML files

// Shared component loader (header/footer)
const _componentCache = new Map();

async function loadComponent(targetId, componentPath, fallbackPath) {
  const el = document.getElementById(targetId);
  if (!el) return;
  const tryFetch = async (path) => {
    if (_componentCache.has(path)) {
      return _componentCache.get(path);
    }
    const res = await fetch(path);
    if (!res.ok) throw new Error(`Failed to load ${path} (${res.status})`);
    const html = await res.text();
    _componentCache.set(path, html);
    return html;
  };
  try {
    const html = await tryFetch(componentPath);
    el.innerHTML = html;
  } catch (e) {
    if (fallbackPath) {
      try {
        const html = await tryFetch(fallbackPath);
        el.innerHTML = html;
        return;
      } catch (fallbackErr) {
        console.error('Component fallback failed:', fallbackErr);
      }
    }
    console.error('Component load failed:', e);
  }
}

async function loadLayout() {
  await Promise.all([
    loadComponent('header', 'components/header.html', 'includes/header.html'),
    loadComponent('footer', 'components/footer.html', 'includes/footer.html')
  ]);
  // Ensure nav state is correct once header is in DOM
  updateNavigation();
}

// ---------- Reusable UI helpers ----------
function renderStatus(type = 'info', message = '') {
  if (!message) return '';
  const variant = {
    success: 'success',
    error: 'danger',
    info: 'info',
    warning: 'warning'
  }[type] || 'info';
  return `<div class="alert alert-${variant}" role="alert">${message}</div>`;
}

function setStatus(elementId, type, message) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.innerHTML = renderStatus(type, message);
}

// renderEmptyState is now in components/EmptyState.js
// Use renderEmptyState from that file instead

function renderButton({ text, href = '#', variant = 'primary', size = '', block = false, extraClasses = '' } = {}) {
  const sizeClass = size ? ` btn-${size}` : '';
  const blockClass = block ? ' w-100' : '';
  const classes = `btn btn-${variant}${sizeClass}${blockClass} ${extraClasses}`.trim();
  return `<a href="${href}" class="${classes}">${text}</a>`;
}

// Simple form validation helper
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

// Cards / list item renderers
function renderFlightListItem(f) {
  const basePrice = f.price || f.base_price_economy;
  const discountedPrice = f.discount_percent > 0
    ? (f.discounted_price || basePrice * (1 - f.discount_percent / 100))
    : basePrice;
  const originDisplay = (f.origin_city_name && f.origin_code)
    ? `${f.origin_city_name} (${f.origin_code})`
    : (f.origin_city_name || f.origin_code || f.origin || '');
  const destinationDisplay = (f.destination_city_name && f.destination_code)
    ? `${f.destination_city_name} (${f.destination_code})`
    : (f.destination_city_name || f.destination_code || f.destination || '');
  return `
    <a href="flight-details.html?id=${f.id}" class="list-group-item list-group-item-action py-3">
      <div class="d-flex w-100 justify-content-between align-items-center">
        <div class="flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span>${originDisplay} → ${destinationDisplay}</span>
            ${isPromoted(f.ad) ? '<span class="badge bg-warning text-dark">Promoted</span>' : ''}
            ${f.discount_percent > 0 ? `<span class="badge bg-danger">${formatPercent(f.discount_percent)}% OFF</span>` : ''}
          </div>
        </div>
        <div class="text-end ms-3">
          ${f.discount_percent > 0
            ? `<div><span class="text-decoration-line-through text-muted small">${formatPrice(basePrice)}</span><br><strong class="text-primary">${formatPrice(discountedPrice)}</strong></div>`
            : `<div><strong class="text-primary">From ${formatPrice(basePrice)}</strong></div>`
          }
        </div>
      </div>
    </a>
  `;
}

// renderHotelCard - uses new Card component
function renderHotelCard(hotel, index = 0, existingCount = 0, columnsClass = 'col-md-4') {
  // Use the new Card component if available
  if (typeof renderCard === 'function') {
    return renderCard({ type: 'hotel', data: hotel, columnsClass });
  }
  // Fallback if components not loaded (shouldn't happen if scripts loaded correctly)
  console.warn('Card component not loaded, using fallback');
  const priceDisplay = hotel.discount_percent > 0
    ? `<div class="price-container">
        <span class="original-price">${formatPrice(hotel.price_per_night)}</span>
        <span class="discounted-price">${formatPrice(hotel.discounted_price || hotel.price_per_night * (1 - hotel.discount_percent / 100))}</span>
        <span class="price-small"> /night</span>
      </div>`
    : `<p class="price">${formatPrice(hotel.price_per_night)}<span class="price-small"> /night</span></p>`;

  const imageUrl = normalizeImageUrl(hotel.image_url || 'uploads/placeholder.svg');
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${hotel.name}" loading="lazy" onerror="this.onerror=null; this.src='${normalizeImageUrl('uploads/placeholder.svg')}'">
          ${hotel.discount_percent > 0 ? `<div class="discount-badge">${formatPercent(hotel.discount_percent)}% OFF</div>` : ''}
          ${isPromoted(hotel.ad) ? `<div class="promoted-badge" style="position: absolute; top: 10px; left: 10px; z-index: 5;">Promoted</div>` : ''}
          <div class="card-overlay">
            <a href="hotel-details.html?id=${hotel.id}" class="btn btn-primary btn-lg btn-cta">View Details</a>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">${hotel.name}</h5>
          <p class="card-text text-muted">${hotel.city_name || ''}${hotel.province_name ? ', ' + hotel.province_name : ''}</p>
          ${priceDisplay}
        </div>
      </div>
    </div>
  `;
}

// renderDestinationCard - uses new Card component
function renderDestinationCard(dest, index = 0, columnsClass = 'col-md-4') {
  // Use the new Card component if available
  if (typeof renderCard === 'function') {
    return renderCard({ type: 'destination', data: dest, columnsClass });
  }
  // Fallback if components not loaded (shouldn't happen if scripts loaded correctly)
  console.warn('Card component not loaded, using fallback');
  const imageUrl = normalizeImageUrl(dest.image_url || 'uploads/placeholder.svg');
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${dest.name}" loading="lazy" onerror="this.onerror=null; this.src='${normalizeImageUrl('uploads/placeholder.svg')}'">
          <div class="card-overlay">
            <a href="destination-details.html?id=${dest.id}" class="btn btn-primary btn-lg btn-cta">View Details</a>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">${dest.name}</h5>
          <p class="card-text text-muted">${dest.description ? escapeHtml(dest.description).substring(0, 100) + (dest.description.length > 100 ? '...' : '') : 'Philippines'}</p>
        </div>
      </div>
    </div>
  `;
}


// Skeleton helpers
function renderSkeletonCard(columnsClass = 'col-md-4', withImage = true, lines = 3) {
  const linesHtml = Array.from({ length: lines }).map(() => '<div class="skeleton skeleton-line"></div>').join('');
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        ${withImage ? `<div class="card-img-wrapper skeleton skeleton-thumbnail"></div>` : ''}
        <div class="card-body">
          ${linesHtml}
        </div>
      </div>
    </div>
  `;
}

function renderSkeletonList(count = 4, columnsClass = 'col-md-4', withImage = true, lines = 3) {
  return Array.from({ length: count }).map(() => renderSkeletonCard(columnsClass, withImage, lines)).join('');
}

// Section / headers
function renderSectionHeader({ title = '', subtitle = '', actionsHtml = '' } = {}) {
  return `
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h3 class="mb-1">${title}</h3>
        ${subtitle ? `<p class="text-muted mb-0">${subtitle}</p>` : ''}
      </div>
      ${actionsHtml ? `<div class="ms-auto">${actionsHtml}</div>` : ''}
    </div>
  `;
}

// Form controls
function renderFormControl({ id, label = '', type = 'text', placeholder = '', value = '', min, max, step, options = [] } = {}) {
  if (type === 'select') {
    const opts = options.map(opt => `<option value="${opt.value ?? ''}">${opt.label ?? ''}</option>`).join('');
    return `
      <div class="mb-3">
        ${label ? `<label class="form-label" for="${id}">${label}</label>` : ''}
        <select class="form-control" id="${id}">
          ${opts}
        </select>
      </div>
    `;
  }
  const minAttr = min !== undefined ? ` min="${min}"` : '';
  const maxAttr = max !== undefined ? ` max="${max}"` : '';
  const stepAttr = step !== undefined ? ` step="${step}"` : '';
  return `
    <div class="mb-3">
      ${label ? `<label class="form-label" for="${id}">${label}</label>` : ''}
      <input type="${type}" class="form-control" id="${id}" placeholder="${placeholder}" value="${value}"${minAttr}${maxAttr}${stepAttr}>
    </div>
  `;
}

function renderBookingCard(booking, index = 0) {
  const statusClass = booking.status === 'confirmed'
    ? 'success'
    : booking.status === 'cancelled'
      ? 'danger'
      : 'secondary';
  return `
    <div class="card mb-3 reveal stagger-${(index % 5) + 1}" style="opacity: 1; transform: translateY(0);">
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h5 class="card-title">${booking.item_type.charAt(0).toUpperCase() + booking.item_type.slice(1)} Booking</h5>
            <p class="card-text"><strong>Booking ID:</strong> ${booking.id}</p>
            <p class="card-text"><strong>Status:</strong> <span class="badge bg-${statusClass}">${booking.status}</span></p>
            <p class="card-text"><strong>Booked on:</strong> ${formatDate(booking.booked_at)}</p>
            <p class="price">Total: ${formatPrice(booking.total_price)}</p>
          </div>
          <div class="col-md-4 text-end">
            ${booking.status === 'confirmed' ? `<button class="btn btn-danger" onclick="cancelBooking(${booking.id})">Cancel Booking</button>` : ''}
          </div>
        </div>
      </div>
    </div>
  `;
}
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

// apiCall is now in utils/api.js
// Use apiCall from that file instead

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

// formatPrice, formatPercent, formatDate are now in utils/formatters.js
// isPromoted is now in components/Badge.js
// Use those functions from their respective files

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
