/**
 * Search helper functions for dropdown and full-page search
 */

/**
 * Render dropdown results
 * @param {array} results - Search results
 * @param {string} type - Result type
 * @returns {string} HTML string
 */
function renderDropdownResults(results, type) {
  if (!results || results.length === 0) {
    return '<div class="search-dropdown-empty">No results found</div>';
  }
  
  return results.slice(0, 5).map(item => renderDropdownItem(item, type)).join('');
}

/**
 * Render dropdown item
 * @param {object} item - Item data
 * @param {string} type - Item type
 * @returns {string} HTML string
 */
function renderDropdownItem(item, type) {
  const imageUrl = normalizeImageUrl(item.image_url || 'uploads/placeholder.svg');
  let detailUrl = '';
  let title = '';
  let subtitle = '';
  let price = '';
  
  switch (type) {
    case 'hotels':
      detailUrl = `hotel-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.name || '');
      subtitle = escapeHtml(typeof formatItemLocation === 'function' ? formatItemLocation(item) : `${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`);
      price = item.price_per_night ? formatPrice(item.price_per_night) + ' /night' : '';
      break;
    case 'flights':
      detailUrl = `flight-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.airline || '');
      const flightOrigin = escapeHtml(item.origin_city_name || item.origin || '');
      const flightDestination = escapeHtml(item.destination_city_name || item.destination || '');
      subtitle = `${flightOrigin} → ${flightDestination}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'destinations':
      detailUrl = `destination-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.name || '');
      subtitle = 'Philippines';
      price = '';
      break;
    case 'transfers':
      detailUrl = `transfer-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.service || 'Transfer');
      const transferOrigin = escapeHtml(item.origin_city_name || item.origin_specific || '');
      const transferDestination = escapeHtml(item.destination_city_name || item.destination_specific || '');
      subtitle = `${transferOrigin} → ${transferDestination}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'activities':
      detailUrl = `activity-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.title || 'Activity');
      subtitle = escapeHtml(`${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`);
      price = item.price ? formatPrice(item.price) : '';
      break;
  }
  
  return `
    <div class="search-dropdown-item" data-url="${detailUrl}">
      <img src="${imageUrl}" alt="${title}" class="search-dropdown-item-image" loading="lazy" onerror="const ph=typeof normalizeImageUrl==='function'?normalizeImageUrl('uploads/placeholder.svg'):(window.location.pathname.includes('/travel-booking')?'/travel-booking/uploads/placeholder.svg':'/uploads/placeholder.svg');if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder',ph);this.src=ph;}else{this.onerror=null;this.style.display='none';}">
      <div class="search-dropdown-item-content">
        <div class="search-dropdown-item-title">${title}</div>
        ${subtitle ? `<div class="search-dropdown-item-subtitle">${subtitle}</div>` : ''}
        ${price ? `<div class="search-dropdown-item-price">${price}</div>` : ''}
      </div>
    </div>
  `;
}

/**
 * Render search results in card grid
 * @param {array} results - Search results
 * @param {string} type - Result type
 * @param {string} query - Search query
 * @returns {string} HTML string (cards only, without header)
 */
function renderSearchResults(results, type, query) {
  if (!results || results.length === 0) {
    return `<p class="text-muted">No ${escapeHtml(type)} found matching "${escapeHtml(query)}".</p>`;
  }
  
  return results.map(item => renderResultCard(item, type)).join('');
}

/**
 * Render result card for search results
 * @param {object} item - Item data
 * @param {string} type - Item type
 * @returns {string} HTML string
 */
function renderResultCard(item, type) {
  if (typeof renderCard === 'function') {
    return renderCard({ type: type, data: item, columnsClass: 'col-md-3' });
  }
  
  const imageUrl = normalizeImageUrl(item.image_url || 'uploads/placeholder.svg');
  let detailUrl = '';
  let title = '';
  let subtitle = '';
  let price = '';
  
  switch (type) {
    case 'hotels':
      detailUrl = `hotel-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.name || '');
      subtitle = escapeHtml(typeof formatItemLocation === 'function' ? formatItemLocation(item) : `${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`);
      price = item.price_per_night ? formatPrice(item.price_per_night) + '<span class="price-small"> /night</span>' : '';
      break;
    case 'flights':
      detailUrl = `flight-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.airline || '');
      const flightOrigin = escapeHtml(item.origin_city_name || item.origin || '');
      const flightDestination = escapeHtml(item.destination_city_name || item.destination || '');
      subtitle = `${flightOrigin} → ${flightDestination}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'destinations':
      detailUrl = `destination-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.name || '');
      subtitle = 'Philippines';
      price = '';
      break;
    case 'transfers':
      detailUrl = `transfer-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.service || 'Transfer');
      const transferOrigin = escapeHtml(item.origin_city_name || item.origin_specific || '');
      const transferDestination = escapeHtml(item.destination_city_name || item.destination_specific || '');
      subtitle = `${transferOrigin} → ${transferDestination}`;
      price = item.price ? formatPrice(item.price) : '';
      break;
    case 'activities':
      detailUrl = `activity-details.html?id=${escapeHtml(String(item.id))}`;
      title = escapeHtml(item.title || 'Activity');
      subtitle = escapeHtml(`${item.city_name || ''}${item.province_name ? ', ' + item.province_name : ''}`);
      price = item.price ? formatPrice(item.price) : '';
      break;
  }
  
  return `
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${title}" loading="lazy" onerror="const ph=typeof normalizeImageUrl==='function'?normalizeImageUrl('uploads/placeholder.svg'):(window.location.pathname.includes('/travel-booking')?'/travel-booking/uploads/placeholder.svg':'/uploads/placeholder.svg');if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder',ph);this.src=ph;}else{this.onerror=null;this.style.display='none';}">
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
