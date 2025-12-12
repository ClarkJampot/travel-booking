// API Client - Centralized API call function

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

/**
 * Make an API call
 * @param {string} endpoint API endpoint (e.g., '/hotels' or '/hotels?id=1')
 * @param {object} options Fetch options (method, body, headers, etc.)
 * @returns {Promise<object>} API response data
 */
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

