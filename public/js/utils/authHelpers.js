/**
 * Authentication helper functions
 */

/**
 * Get current user from localStorage
 * @returns {object|null} User object or null
 */
function getCurrentUser() {
  const userStr = localStorage.getItem('user');
  return userStr ? JSON.parse(userStr) : null;
}

/**
 * Check if user is authenticated
 * @returns {boolean} True if authenticated
 */
function isAuthenticated() {
  return !!localStorage.getItem('token');
}

/**
 * Check if user is a customer
 * @returns {boolean} True if customer
 */
function isCustomer() {
  const user = getCurrentUser();
  return user && user.role === 'customer';
}

/**
 * Show modal requiring customer authentication
 */
function showCustomerRequiredModal() {
  const modal = new Modal({
    title: 'Authentication Required',
    message: 'You must be logged in as a customer to make bookings. Please log in or register.',
    confirmText: 'Go to Login',
    cancelText: 'Cancel',
    onConfirm: () => {
      window.location.href = 'login.html';
    }
  });
  modal.show();
}

/**
 * Update navigation based on authentication state
 */
function updateNavigation() {
  const isAuth = isAuthenticated();
  const user = getCurrentUser();
  const userRole = user ? user.role : null;
  
  const loginBtn = document.getElementById('loginBtn');
  const registerBtn = document.getElementById('registerBtn');
  const dashboardBtn = document.getElementById('dashboardBtn');
  const bookingsBtn = document.getElementById('bookingsBtn');
  const profileBtn = document.getElementById('profileBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  
  if (loginBtn) loginBtn.style.display = isAuth ? 'none' : 'block';
  if (registerBtn) registerBtn.style.display = isAuth ? 'none' : 'block';
  if (dashboardBtn) dashboardBtn.style.display = (userRole === 'owner' || userRole === 'agency') ? 'block' : 'none';
  if (bookingsBtn) bookingsBtn.style.display = (isAuth && userRole === 'customer') ? 'block' : 'none';
  if (profileBtn) profileBtn.style.display = isAuth ? 'block' : 'none';
  if (logoutBtn) logoutBtn.style.display = isAuth ? 'block' : 'none';
}
