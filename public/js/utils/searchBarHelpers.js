/**
 * Search Bar Helper Functions
 * Utility functions for search bar functionality
 */

/**
 * Setup event handlers for a search form
 * @param {string} formType - Form type ('hero' or 'sticky')
 * @param {HTMLElement} searchInput - Search input element
 * @param {HTMLElement} searchBtn - Search button element
 * @param {HTMLElement} contentTypeSelect - Content type select element
 * @param {function} syncFunction - Function to sync values to other form
 * @param {function} navigateFunction - Function to navigate to listing page
 */
function setupSearchFormHandlers(formType, searchInput, searchBtn, contentTypeSelect, syncFunction, navigateFunction) {
  if (!searchInput || !searchBtn || !contentTypeSelect) return;
  
  // Button click handler
  searchBtn.addEventListener('click', function(e) {
    e.preventDefault();
    syncFunction();
    hideDropdown(formType);
    navigateFunction(formType);
  });
  
  // Enter key handler
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      syncFunction();
      hideDropdown(formType);
      navigateFunction(formType);
    } else if (e.key === 'Escape') {
      hideDropdown(formType);
    }
  });
  
  // Debounced input handler for dropdown
  const inputHandler = debounce(() => {
    syncFunction();
    const query = searchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch(formType);
    } else {
      hideDropdown(formType);
    }
  }, 200);
  
  // Input handler
  searchInput.addEventListener('input', function() {
    syncFunction();
    const query = searchInput.value.trim();
    if (query.length >= 2) {
      inputHandler();
    } else {
      hideDropdown(formType);
    }
  });
  
  // Focus handler
  searchInput.addEventListener('focus', function() {
    const query = searchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch(formType);
    }
  });
  
  // Content type change handler
  contentTypeSelect.addEventListener('change', function() {
    syncFunction();
    const query = searchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch(formType);
    }
  });
}
