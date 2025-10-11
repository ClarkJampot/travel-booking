// Main.js - Common JavaScript functions for travel booking app

// Utility function to show loading state
function showLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.textContent = 'Loading...';
  }
}

// Utility function to show error
function showError(elementId, message) {
  const element = document.getElementById(elementId);
  if (element) {
    element.textContent = 'Error: ' + message;
    element.style.color = '#dc3545';
  }
}

// Utility function to clear status
function clearStatus(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.textContent = '';
    element.style.color = '';
  }
}
