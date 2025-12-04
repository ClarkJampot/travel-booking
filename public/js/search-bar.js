// Search Bar Functionality

// Debounce function for search
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
  heroSearchBtn.addEventListener('click', function(e) {
    e.preventDefault();
    syncToSticky();
    hideDropdown('hero');
    // Navigate to listing page (same as Enter key)
    navigateToListingPage('hero');
  });
  
  heroSearchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      syncToSticky();
      hideDropdown('hero');
      navigateToListingPage('hero');
    } else if (e.key === 'Escape') {
      hideDropdown('hero');
    }
  });
  
  // Debounced input handler for dropdown
  const heroInputHandler = debounce(() => {
    syncToSticky();
    const query = heroSearchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch('hero');
    } else {
      hideDropdown('hero');
    }
  }, 200);
  
  heroSearchInput.addEventListener('input', function() {
    syncToSticky();
    const query = heroSearchInput.value.trim();
    if (query.length >= 2) {
      heroInputHandler();
    } else {
      hideDropdown('hero');
    }
  });
  
  // Also trigger on focus if there's already text
  heroSearchInput.addEventListener('focus', function() {
    const query = heroSearchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch('hero');
    }
  });
  
  heroContentTypeSelect.addEventListener('change', function() {
    syncToSticky();
    const query = heroSearchInput.value.trim();
    if (query.length >= 2) {
      performDropdownSearch('hero');
    }
  });
  
  // Sticky form event handlers (if exists)
  if (stickySearchBtn && stickySearchInput && stickyContentTypeSelect) {
    stickySearchBtn.addEventListener('click', function(e) {
      e.preventDefault();
      syncToHero();
      hideDropdown('sticky');
      // Navigate to listing page (same as Enter key)
      navigateToListingPage('sticky');
    });
    
    stickySearchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        syncToHero();
        hideDropdown('sticky');
        navigateToListingPage('sticky');
      } else if (e.key === 'Escape') {
        hideDropdown('sticky');
      }
    });
    
    // Debounced input handler for dropdown
    const stickyInputHandler = debounce(() => {
      syncToHero();
      const query = stickySearchInput.value.trim();
      if (query.length >= 2) {
        performDropdownSearch('sticky');
      } else {
        hideDropdown('sticky');
      }
    }, 200);
    
    stickySearchInput.addEventListener('input', function() {
      syncToHero();
      const query = stickySearchInput.value.trim();
      if (query.length >= 2) {
        stickyInputHandler();
      } else {
        hideDropdown('sticky');
      }
    });
    
    // Also trigger on focus if there's already text
    stickySearchInput.addEventListener('focus', function() {
      const query = stickySearchInput.value.trim();
      if (query.length >= 2) {
        performDropdownSearch('sticky');
      }
    });
    
    stickyContentTypeSelect.addEventListener('change', function() {
      syncToHero();
      const query = stickySearchInput.value.trim();
      if (query.length >= 2) {
        performDropdownSearch('sticky');
      }
    });
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
  }
}

// Show dropdown
function showDropdown(formType) {
  const dropdown = formType === 'hero'
    ? document.getElementById('heroSearchDropdown')
    : document.getElementById('stickySearchDropdown');
  if (dropdown) {
    dropdown.classList.add('show');
    dropdown.style.display = 'block';
    dropdown.style.visibility = 'visible';
    dropdown.style.opacity = '1';
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
    
    // Check if API returned data correctly
    if (!data || !data.results) {
      dropdown.innerHTML = '<div class="search-dropdown-empty">Error loading results</div>';
      showDropdown(formType);
      return;
    }
    
    // Filter results to only show the selected type (limit to 5 for dropdown)
    const results = data.results && data.results[type] ? data.results[type].slice(0, 5) : [];
    
    // Debug: Log what we got
    if (results.length === 0) {
      console.log('No results for type:', type);
      console.log('Results object:', data.results);
      console.log('Results keys:', data.results ? Object.keys(data.results) : 'no results object');
      console.log('Results[type]:', data.results ? data.results[type] : 'N/A');
    }
    
    if (results.length === 0) {
      dropdown.innerHTML = `<div class="search-dropdown-empty">No ${type} found matching "${query}"</div>`;
      showDropdown(formType);
      return;
    }
    
    // Render dropdown results
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
    dropdown.innerHTML = `<div class="search-dropdown-empty">Error: ${error.message || 'Please try again'}</div>`;
    showDropdown(formType);
  }
}

// Render dropdown results
function renderDropdownResults(results, type) {
  return results.map(item => renderDropdownItem(item, type)).join('');
}

// Render individual dropdown item
function renderDropdownItem(item, type) {
  const imageUrl = normalizeImageUrl(item.image_url || 'uploads/placeholder.svg');
  let detailUrl = '';
  let title = '';
  let subtitle = '';
  let price = '';
  
  switch (type) {
    case 'hotels':
      detailUrl = `hotel-details.html?id=${item.id}`;
      title = escapeHtml(item.name || '');
      subtitle = escapeHtml(`${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`);
      price = item.price_per_night ? formatPrice(item.price_per_night) + '/night' : '';
      break;
    case 'flights':
      detailUrl = `flight-details.html?id=${item.id}`;
      title = escapeHtml(item.airline || '');
      const flightOrigin = item.origin_city_name || item.origin || '';
      const flightDestination = item.destination_city_name || item.destination || '';
      subtitle = escapeHtml(`${flightOrigin} → ${flightDestination}`);
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'activities':
      detailUrl = `activity-details.html?id=${item.id}`;
      title = escapeHtml(item.title || '');
      subtitle = escapeHtml(item.city_name || '');
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'transfers':
      detailUrl = `transfer-details.html?id=${item.id}`;
      title = escapeHtml(item.service || '');
      const transferOrigin = item.origin_city_name || item.origin || '';
      const transferDestination = item.destination_city_name || item.destination || '';
      subtitle = escapeHtml(`${transferOrigin} → ${transferDestination}`);
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'destinations':
      detailUrl = `destination-details.html?id=${item.id}`;
      title = escapeHtml(item.name || '');
      subtitle = 'Philippines';
      price = '';
      break;
  }
  
  return `
    <div class="search-dropdown-item" data-url="${detailUrl}">
      <img src="${imageUrl}" alt="${title}" class="search-dropdown-item-image" loading="lazy" onerror="this.src='${normalizeImageUrl('uploads/placeholder.svg')}'">
      <div class="search-dropdown-item-content">
        <div class="search-dropdown-item-title">${title}</div>
        ${subtitle ? `<div class="search-dropdown-item-subtitle">${subtitle}</div>` : ''}
        ${price ? `<div class="search-dropdown-item-price">${price}</div>` : ''}
      </div>
    </div>
  `;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
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
      resultsContainer.innerHTML = `<p class="text-muted">No ${type} found matching "${query}".</p>`;
      return;
    }
    
    // Render results
    resultsContainer.innerHTML = renderSearchResults(results, type, query);
    
    // Scroll to results
    setTimeout(() => {
      resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
  } catch (error) {
    console.error('Search error:', error);
    if (resultsContainer) {
      resultsContainer.innerHTML = `<p class="text-danger">Error performing search: ${error.message || 'Please try again.'}</p>`;
    }
  }
}

// Render search results in card grid
function renderSearchResults(results, type, query) {
  return `
    <div class="search-results-header mb-3">
      <h3>Search Results for "${query}"</h3>
      <p class="text-muted">Found ${results.length} ${type}</p>
    </div>
    <div class="row g-4">
      ${results.map(item => renderResultCard(item, type)).join('')}
    </div>
  `;
}

// Render individual result card
function renderResultCard(item, type) {
  const imageUrl = normalizeImageUrl(item.image_url || 'uploads/placeholder.svg');
  let detailUrl = '';
  let title = '';
  let subtitle = '';
  let price = '';
  
  switch (type) {
    case 'hotels':
      detailUrl = `hotel-details.html?id=${item.id}`;
      title = item.name;
      subtitle = `${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`;
      price = item.price_per_night ? formatPrice(item.price_per_night) + '<span class="price-small">/night</span>' : '';
      break;
    case 'flights':
      detailUrl = `flight-details.html?id=${item.id}`;
      title = item.airline;
      const flightOriginFull = item.origin_city_name || item.origin || '';
      const flightDestinationFull = item.destination_city_name || item.destination || '';
      subtitle = `${flightOriginFull} → ${flightDestinationFull}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'activities':
      detailUrl = `activity-details.html?id=${item.id}`;
      title = item.title;
      subtitle = item.city_name || '';
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'transfers':
      detailUrl = `transfer-details.html?id=${item.id}`;
      title = item.service;
      const transferOriginFull = item.origin_city_name || item.origin || '';
      const transferDestinationFull = item.destination_city_name || item.destination || '';
      subtitle = `${transferOriginFull} → ${transferDestinationFull}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'destinations':
      detailUrl = `destination-details.html?id=${item.id}`;
      title = item.name;
      subtitle = 'Philippines';
      price = '';
      break;
  }
  
  return `
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${title}" loading="lazy">
          <div class="card-overlay">
            <a href="${detailUrl}" class="btn btn-primary btn-lg">View Details</a>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">${title}</h5>
          <p class="card-text text-muted">${subtitle}</p>
          ${price ? `<p class="price">${price}</p>` : ''}
        </div>
      </div>
    </div>
  `;
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
    'activities.html': 'activities',
    'transfers.html': 'transfers',
    'destinations.html': 'destinations'
  };
  
  if (typeMap[filename]) {
    contentTypeSelect.value = typeMap[filename];
  }
}
