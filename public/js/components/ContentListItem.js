/**
 * Render a content item in list format with edit/delete buttons
 * @param {object} item - Item data
 * @param {string} type - Item type ('hotel', 'flight', 'activity', 'transfer')
 * @returns {string} HTML string
 */
function renderContentListItem(item, type) {
  const detailPage = {
    'hotel': 'hotel-details.html',
    'flight': 'flight-details.html',
    'activity': 'activity-details.html',
    'transfer': 'transfer-details.html'
  }[type] || '#';
  
  const priceField = type === 'hotel' ? 'price_per_night' : 'price';
  const priceLabel = type === 'hotel' ? '/night' : '';
  const nameField = type === 'activity' ? 'title' : (type === 'flight' ? 'airline' : (type === 'transfer' ? 'service' : 'name'));
  
  const originalPrice = item[priceField] || item.price_per_night || item.price || 0;
  const priceDisplay = item.discount_percent > 0 
    ? `<div class="d-flex align-items-center gap-2">
        <span class="text-decoration-line-through text-muted">${formatPrice(originalPrice)}</span>
        <span class="fw-bold text-success">${formatPrice(item.discounted_price || originalPrice * (1 - item.discount_percent / 100))}</span>
        <span class="badge bg-danger">${formatPercent(item.discount_percent)}% OFF</span>
        ${priceLabel ? `<small class="text-muted">${priceLabel}</small>` : ''}
      </div>`
    : `<span class="fw-bold">${formatPrice(originalPrice)}${priceLabel ? `<small class="text-muted">${priceLabel}</small>` : ''}</span>`;
  
  // Build location/route info
  let locationInfo = '';
  if (type === 'hotel' || type === 'activity') {
    locationInfo = item.city_name ? `${item.city_name}${item.province_name ? ', ' + item.province_name : ''}` : '';
  } else if (type === 'flight' || type === 'transfer') {
    const origin = item.origin_city_name || item.origin || '';
    const destination = item.destination_city_name || item.destination || '';
    locationInfo = origin && destination ? `${origin} → ${destination}` : '';
  }
  
  // Date info
  let dateInfo = '';
  if (type === 'flight' && item.depart_date) {
    dateInfo = formatDate(item.depart_date);
  } else if ((type === 'activity' || type === 'transfer') && item.date) {
    dateInfo = formatDate(item.date);
  }
  
  // Only show image for hotels and activities, not flights and transfers
  const imageHtml = (type === 'hotel' || type === 'activity') 
    ? `<div class="flex-shrink-0">
        <img src="${normalizeImageUrl(item.image_url)}" alt="${item[nameField]}" 
             class="rounded" style="width: 100px; height: 100px; object-fit: cover;" 
             loading="lazy" onerror="const ph=typeof normalizeImageUrl==='function'?normalizeImageUrl('uploads/placeholder.svg'):(window.location.pathname.includes('/travel-booking')?'/travel-booking/uploads/placeholder.svg':'/uploads/placeholder.svg');if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder',ph);this.src=ph;}else{this.onerror=null;this.style.display='none';}">
      </div>`
    : '';
  
  return `
    <div class="list-group-item">
      <div class="d-flex align-items-center gap-3">
        ${imageHtml}
        <div class="flex-grow-1">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <h6 class="mb-1">
                ${item[nameField] || 'Untitled'}
                ${isPromoted(item.ad) ? '<span class="promoted-badge ms-2">Promoted</span>' : ''}
              </h6>
              ${locationInfo ? `<p class="text-muted mb-1 small">${locationInfo}</p>` : ''}
              ${dateInfo ? `<p class="text-muted mb-1 small">${dateInfo}</p>` : ''}
              <div class="mt-2">
                ${priceDisplay}
              </div>
            </div>
            <div class="flex-shrink-0 ms-3">
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editContentItem('${type}', ${item.id})">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10.5a.5.5 0 0 1 .146-.354l6-6z"/>
                  </svg>
                  Edit
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteContentItem('${type}', ${item.id}, '${item[nameField] || 'this item'}')">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}


