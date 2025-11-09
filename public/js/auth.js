// auth.js - Authentication functions

// Login
async function login(email, password) {
  try {
    const data = await apiCall('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
    
    localStorage.setItem('token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    
    return data;
  } catch (error) {
    throw error;
  }
}

// Register
async function register(email, password, full_name, role = 'customer') {
  try {
    const data = await apiCall('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, password, full_name, role })
    });
    
    localStorage.setItem('token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    
    return data;
  } catch (error) {
    throw error;
  }
}

// Logout
function logout() {
  const token = localStorage.getItem('token');
  
  if (token) {
    // Revoke token on server
    apiCall('/auth/logout', {
      method: 'POST'
    }).catch(err => console.error('Logout error:', err));
  }
  
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = '/index.html';
}

// Get current user from API
async function getCurrentUserFromAPI() {
  try {
    const data = await apiCall('/auth/me');
    if (data.user) {
      localStorage.setItem('user', JSON.stringify(data.user));
      return data.user;
    }
    return null;
  } catch (error) {
    // Token might be invalid, clear it
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    return null;
  }
}

// Refresh token
async function refreshToken() {
  try {
    const data = await apiCall('/auth/refresh', {
      method: 'POST'
    });
    
    localStorage.setItem('token', data.token);
    return data.token;
  } catch (error) {
    // Refresh failed, logout
    logout();
    throw error;
  }
}


