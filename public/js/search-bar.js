/**
 * Search Bar Functionality
 * Handles search bar on homepage (hero and sticky)
 */

// Initialize search bar function (only on homepage)
function initSearchBar() {
  // Only initialize on homepage (check for hero banner)
  const heroBanner = document.querySelector('.hero-banner');
  if (!heroBanner) {
    return; // Not on homepage, exit early
  }
  
  const heroSearchInput = document.getElementById('heroSearchInput');
  const heroSearchBtn = document.getElementById('heroSearchBtn');
  const heroContentTypeSelect = document.getElementById('heroContentTypeSelect');
  
  const stickySearchInput = document.getElementById('stickySearchInput');
  const stickySearchBtn = document.getElementById('stickySearchBtn');
  const stickyContentTypeSelect = document.getElementById('stickyContentTypeSelect');
  
  if (!heroSearchInput || !heroSearchBtn || !heroContentTypeSelect) {
    return; // Hero search form not present
  }
  
  // Sync values between hero and sticky forms (bidirectional)
  function syncToSticky() {
    if (stickySearchInput && stickyContentTypeSelect) {
      stickySearchInput.value = heroSearchInput.value;
      stickyContentTypeSelect.value = heroContentTypeSelect.value;
    }
  }
  
  function syncToHero() {
    if (stickySearchInput && stickyContentTypeSelect) {
      heroSearchInput.value = stickySearchInput.value;
      heroContentTypeSelect.value = stickyContentTypeSelect.value;
    }
  }
  
  // Hero form event handlers
  setupSearchFormHandlers('hero', heroSearchInput, heroSearchBtn, heroContentTypeSelect, syncToSticky, navigateToListingPage);
  
  // Sticky form event handlers (if exists)
  if (stickySearchBtn && stickySearchInput && stickyContentTypeSelect) {
    setupSearchFormHandlers('sticky', stickySearchInput, stickySearchBtn, stickyContentTypeSelect, syncToHero, navigateToListingPage);
  }
  
  // Hide dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const heroForm = document.querySelector('.hero-search-form');
    const stickyForm = document.querySelector('.sticky-search-bar-replacement .search-form');
    const heroDropdown = document.getElementById('heroSearchDropdown');
    const stickyDropdown = document.getElementById('stickySearchDropdown');
    const heroInput = document.getElementById('heroSearchInput');
    const stickyInput = document.getElementById('stickySearchInput');
    
    // Don't hide if clicking inside form, dropdown, or input
    const isHeroClick = heroForm && (heroForm.contains(e.target) || (heroDropdown && heroDropdown.contains(e.target)) || e.target === heroInput);
    const isStickyClick = stickyForm && (stickyForm.contains(e.target) || (stickyDropdown && stickyDropdown.contains(e.target)) || e.target === stickyInput);
    
    if (!isHeroClick) {
      hideDropdown('hero');
    }
    if (!isStickyClick) {
      hideDropdown('sticky');
    }
  });
  
  // Initialize scroll detection
  initScrollDetection();
  
  // Update hero dropdown position on window resize/scroll
  let resizeTimeout;
  function updateHeroDropdownPosition() {
    const heroDropdown = document.getElementById('heroSearchDropdown');
    const heroInput = document.getElementById('heroSearchInput');
    if (heroDropdown && heroInput && heroDropdown.classList.contains('show')) {
      const inputRect = heroInput.getBoundingClientRect();
      heroDropdown.style.top = (inputRect.bottom + 5) + 'px';
      heroDropdown.style.left = inputRect.left + 'px';
      heroDropdown.style.width = inputRect.width + 'px';
    }
  }
  
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(updateHeroDropdownPosition, 100);
  });
  
  window.addEventListener('scroll', function() {
    if (document.getElementById('heroSearchDropdown')?.classList.contains('show')) {
      updateHeroDropdownPosition();
    }
  });
}

// Initialize scroll detection for sticky search bar
function initScrollDetection() {
  const heroBanner = document.querySelector('.hero-banner');
  const stickyBar = document.getElementById('stickySearchBar');
  
  if (!heroBanner || !stickyBar) {
    return;
  }
  
  // Wait a bit for header to load, then initialize observer
  setTimeout(() => {
    const header = document.querySelector('#header');
    
    // Use a small negative rootMargin to trigger slightly before hero leaves viewport
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) {
          // Hero is out of view - show sticky bar, hide header
          stickyBar.classList.add('show');
          if (header && header.innerHTML.trim()) {
            header.style.display = 'none';
          }
        } else {
          // Hero is in view - hide sticky bar, show header
          stickyBar.classList.remove('show');
          if (header) {
            header.style.display = '';
          }
        }
      });
    }, { 
      threshold: 0,
      rootMargin: '-1px 0px 0px 0px' // Trigger when hero starts to leave viewport
    });
    
    observer.observe(heroBanner);
  }, 500); // Wait for header to load
}

// Hide dropdown
function hideDropdown(formType) {
  const dropdown = formType === 'hero'
    ? document.getElementById('heroSearchDropdown')
    : document.getElementById('stickySearchDropdown');
  if (dropdown) {
    dropdown.classList.remove('show');
    dropdown.style.display = 'none';
    dropdown.style.visibility = 'hidden';
    // Reset positioning styles
    dropdown.style.position = '';
    dropdown.style.top = '';
    dropdown.style.left = '';
    dropdown.style.width = '';
    dropdown.style.right = '';
  }
}

// Show dropdown
function showDropdown(formType) {
  const dropdown = formType === 'hero'
    ? document.getElementById('heroSearchDropdown')
    : document.getElementById('stickySearchDropdown');
  const searchInput = formType === 'hero'
    ? document.getElementById('heroSearchInput')
    : document.getElementById('stickySearchInput');
  
  if (dropdown) {
    dropdown.classList.add('show');
    dropdown.style.display = 'block';
    dropdown.style.visibility = 'visible';
    dropdown.style.opacity = '1';
    
    // For hero dropdown, calculate fixed position to escape overflow constraints
    if (formType === 'hero' && searchInput) {
      const inputRect = searchInput.getBoundingClientRect();
      const wrapper = searchInput.closest('.search-input-wrapper');
      if (wrapper) {
        dropdown.style.position = 'fixed';
        dropdown.style.top = (inputRect.bottom + 5) + 'px';
        dropdown.style.left = inputRect.left + 'px';
        dropdown.style.width = inputRect.width + 'px';
        dropdown.style.right = 'auto';
      }
    } else {
      // Reset to absolute for sticky dropdown
      dropdown.style.position = '';
      dropdown.style.top = '';
      dropdown.style.left = '';
      dropdown.style.width = '';
      dropdown.style.right = '';
    }
    
    // Force reflow to ensure display change takes effect
    dropdown.offsetHeight;
  }
}

// Perform dropdown search (autocomplete)
async function performDropdownSearch(formType) {
  const searchInput = formType === 'hero'
    ? document.getElementById('heroSearchInput')
    : document.getElementById('stickySearchInput');
  const contentTypeSelect = formType === 'hero'
    ? document.getElementById('heroContentTypeSelect')
    : document.getElementById('stickyContentTypeSelect');
  const dropdown = formType === 'hero'
    ? document.getElementById('heroSearchDropdown')
    : document.getElementById('stickySearchDropdown');
  const resultsContainer = document.getElementById('searchResults');
  
  if (!searchInput || !contentTypeSelect || !dropdown) return;
  
  const query = searchInput.value.trim();
  const type = contentTypeSelect.value;
  
  if (query.length < 2) {
    hideDropdown(formType);
    // Clear full page results when hiding dropdown
    if (resultsContainer) {
      resultsContainer.innerHTML = '';
    }
    return;
  }
  
  // Hide full page results when showing dropdown
  if (resultsContainer) {
    resultsContainer.innerHTML = '';
  }
  
  // Show loading state immediately
  dropdown.innerHTML = '<div class="search-dropdown-loading">Searching...</div>';
  showDropdown(formType);
  
  try {
    const data = await apiCall(`/search?q=${encodeURIComponent(query)}&type=${type}`);
    
    if (!data || !data.results) {
      dropdown.innerHTML = '<div class="search-dropdown-empty">Error loading results</div>';
      showDropdown(formType);
      return;
    }
    
    // Filter results to only show the selected type (limit to 5 for dropdown)
    const results = data.results && data.results[type] ? data.results[type].slice(0, 5) : [];
    
    if (results.length === 0) {
      dropdown.innerHTML = `<div class="search-dropdown-empty">No ${escapeHtml(type)} found matching "${escapeHtml(query)}"</div>`;
      showDropdown(formType);
      return;
    }
    
    // Render dropdown results (using function from searchHelpers.js)
    dropdown.innerHTML = renderDropdownResults(results, type);
    showDropdown(formType);
    
    // Add click handlers to dropdown items
    dropdown.querySelectorAll('.search-dropdown-item').forEach(item => {
      item.addEventListener('click', function(e) {
        e.stopPropagation();
        const url = this.getAttribute('data-url');
        if (url) {
          window.location.href = url;
        }
      });
    });
  } catch (error) {
    console.error('Dropdown search error:', error);
    dropdown.innerHTML = `<div class="search-dropdown-empty">Error: ${escapeHtml(error.message || 'Please try again')}</div>`;
    showDropdown(formType);
  }
}


// Navigate to listing page with search params
function navigateToListingPage(formType) {
  const searchInput = formType === 'hero' 
    ? document.getElementById('heroSearchInput')
    : document.getElementById('stickySearchInput');
  const contentTypeSelect = formType === 'hero'
    ? document.getElementById('heroContentTypeSelect')
    : document.getElementById('stickyContentTypeSelect');
  
  if (!searchInput || !contentTypeSelect) return;
  
  const query = searchInput.value.trim();
  const type = contentTypeSelect.value;
  
  if (!query) {
    // If no query, just go to listing page
    window.location.href = `${type}.html`;
    return;
  }
  
  // Navigate to listing page with search query
  window.location.href = `${type}.html?q=${encodeURIComponent(query)}`;
}

// Perform search and display results on same page (for button click)
async function performSearch(formType) {
  const searchInput = formType === 'hero'
    ? document.getElementById('heroSearchInput')
    : document.getElementById('stickySearchInput');
  const contentTypeSelect = formType === 'hero'
    ? document.getElementById('heroContentTypeSelect')
    : document.getElementById('stickyContentTypeSelect');
  const resultsContainer = document.getElementById('searchResults');
  
  if (!searchInput || !contentTypeSelect) return;
  
  const query = searchInput.value.trim();
  const type = contentTypeSelect.value;
  
  if (!query) {
    if (resultsContainer) {
      resultsContainer.innerHTML = '<p class="text-muted">Please enter a search term.</p>';
    }
    return;
  }
  
  // Hide dropdown when showing full results
  hideDropdown(formType);
  
  // Show loading state
  if (resultsContainer) {
    resultsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
  }
  
  try {
    const data = await apiCall(`/search?q=${encodeURIComponent(query)}&type=${type}`);
    
    if (!resultsContainer) {
      console.error('Search results container not found');
      return;
    }
    
    // Check if API returned data correctly
    if (!data || !data.results) {
      resultsContainer.innerHTML = `<p class="text-danger">Invalid response from server. Please try again.</p>`;
      return;
    }
    
    // Filter results to only show the selected type
    const results = data.results && data.results[type] ? data.results[type] : [];
    
    if (results.length === 0) {
      resultsContainer.innerHTML = `<p class="text-muted">No ${escapeHtml(type)} found matching "${escapeHtml(query)}".</p>`;
      return;
    }
    
    // Render results (using function from searchHelpers.js)
    const resultsHtml = renderSearchResults(results, type, query);
    resultsContainer.innerHTML = `
      <div class="search-results-header mb-3">
        <h3>Search Results for "${escapeHtml(query)}"</h3>
        <p class="text-muted">Found ${results.length} ${escapeHtml(type)}</p>
      </div>
      <div class="row g-4">
        ${resultsHtml}
      </div>
    `;
    
    // Scroll to results
    setTimeout(() => {
      resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
  } catch (error) {
    console.error('Search error:', error);
    if (resultsContainer) {
      resultsContainer.innerHTML = `<p class="text-danger">Error performing search: ${escapeHtml(error.message || 'Please try again.')}</p>`;
    }
  }
}

// Set content type based on current page (for listing pages - legacy function)
function setContentTypeFromPage() {
  const contentTypeSelect = document.getElementById('contentTypeSelect');
  if (!contentTypeSelect) return;
  
  const path = window.location.pathname;
  const filename = path.split('/').pop();
  
  // Map filenames to content types
  const typeMap = {
    'hotels.html': 'hotels',
    'flights.html': 'flights',
    'destinations.html': 'destinations'
  };
  
  if (typeMap[filename]) {
    contentTypeSelect.value = typeMap[filename];
  }
}
