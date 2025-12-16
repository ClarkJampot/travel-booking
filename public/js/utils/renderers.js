/**
 * Renderer functions for cards and list items
 */

/**
 * Render flight list item
 * @param {object} f - Flight data
 * @returns {string} HTML string
 */
function renderFlightListItem(f) {
  // Use ListItem component if available
  if (typeof renderListItem === 'function') {
    const basePrice = f.price || f.base_price_economy;
    const discountedPrice = f.discount_percent > 0
      ? (f.discounted_price || basePrice * (1 - f.discount_percent / 100))
      : null;
    
    const originDisplay = (f.origin_city_name && f.origin_code)
      ? `${f.origin_city_name} (${f.origin_code})`
      : (f.origin_city_name || f.origin_code || f.origin || '');
    
    const destinationDisplay = (f.destination_city_name && f.destination_code)
      ? `${f.destination_city_name} (${f.destination_code})`
      : (f.destination_city_name || f.destination_code || f.destination || '');
    
    return renderListItem({
      item: f,
      type: 'flight',
      detailUrl: `flight-details.html?id=${escapeHtml(String(f.id))}`,
      originDisplay,
      destinationDisplay,
      basePrice,
      discountedPrice,
      discountPercent: f.discount_percent || 0,
      isPromoted: isPromoted(f.ad),
      extraInfo: f.depart_date ? formatDate(f.depart_date) : ''
    });
  }
  
  // Fallback implementation
  const basePrice = f.price || f.base_price_economy;
  const discountedPrice = f.discount_percent > 0
    ? (f.discounted_price || basePrice * (1 - f.discount_percent / 100))
    : basePrice;
  const originDisplay = escapeHtml((f.origin_city_name && f.origin_code)
    ? `${f.origin_city_name} (${f.origin_code})`
    : (f.origin_city_name || f.origin_code || f.origin || ''));
  const destinationDisplay = escapeHtml((f.destination_city_name && f.destination_code)
    ? `${f.destination_city_name} (${f.destination_code})`
    : (f.destination_city_name || f.destination_code || f.destination || ''));
  return `
    <a href="flight-details.html?id=${escapeHtml(String(f.id))}" class="list-group-item list-group-item-action py-3">
      <div class="d-flex w-100 justify-content-between align-items-center">
        <div class="flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span>${originDisplay} → ${destinationDisplay}</span>
            ${isPromoted(f.ad) ? '<span class="promoted-badge">Promoted</span>' : ''}
            ${f.discount_percent > 0 ? `<span class="badge bg-danger">${escapeHtml(formatPercent(f.discount_percent))}% OFF</span>` : ''}
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

/**
 * Render hotel card (fallback if Card component not available)
 * @param {object} hotel - Hotel data
 * @param {number} index - Card index
 * @param {number} existingCount - Existing card count
 * @param {string} columnsClass - Column class
 * @returns {string} HTML string
 */
function renderHotelCard(hotel, index = 0, existingCount = 0, columnsClass = 'col-md-4') {
  if (typeof renderCard === 'function') {
    return renderCard({ type: 'hotel', data: hotel, columnsClass });
  }
  console.warn('Card component not loaded, using fallback');
  const priceDisplay = hotel.discount_percent > 0
    ? `<div class="price-container">
        <span class="original-price">${formatPrice(hotel.price_per_night)}</span>
        <span class="discounted-price">${formatPrice(hotel.discounted_price || hotel.price_per_night * (1 - hotel.discount_percent / 100))}</span>
        <span class="price-small"> /night</span>
      </div>`
    : `<p class="price">${formatPrice(hotel.price_per_night)}<span class="price-small"> /night</span></p>`;

  const imageUrl = normalizeImageUrl(hotel.image_url || 'uploads/placeholder.svg');
  const placeholderPath = normalizeImageUrl('uploads/placeholder.svg');
  
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${escapeHtml(hotel.name || '')}" loading="lazy" onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${escapeHtml(placeholderPath)}');this.src='${escapeHtml(placeholderPath)}';}else{this.onerror=null;this.style.display='none';}">
          ${hotel.discount_percent > 0 ? `<div class="discount-badge">${formatPercent(hotel.discount_percent)}% OFF</div>` : ''}
          ${isPromoted(hotel.ad) ? `<div class="promoted-badge promoted-badge-absolute">Promoted</div>` : ''}
          <div class="card-overlay">
            <a href="hotel-details.html?id=${escapeHtml(String(hotel.id))}" class="btn btn-primary btn-lg btn-cta">View Details</a>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">${escapeHtml(hotel.name || '')}</h5>
          <p class="card-text text-muted">${escapeHtml(hotel.city_name || '')}${hotel.province_name ? ', ' + escapeHtml(hotel.province_name) : ''}</p>
          ${priceDisplay}
        </div>
      </div>
    </div>
  `;
}

/**
 * Render destination card (fallback if Card component not available)
 * @param {object} dest - Destination data
 * @param {number} index - Card index
 * @param {string} columnsClass - Column class
 * @returns {string} HTML string
 */
function renderDestinationCard(dest, index = 0, columnsClass = 'col-md-4') {
  if (typeof renderCard === 'function') {
    return renderCard({ type: 'destination', data: dest, columnsClass });
  }
  console.warn('Card component not loaded, using fallback');
  const imageUrl = normalizeImageUrl(dest.image_url || 'uploads/placeholder.svg');
  const placeholderPath = normalizeImageUrl('uploads/placeholder.svg');
  
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        <div class="card-img-wrapper">
          <img src="${imageUrl}" class="card-img-top" alt="${escapeHtml(dest.name || '')}" loading="lazy" onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${escapeHtml(placeholderPath)}');this.src='${escapeHtml(placeholderPath)}';}else{this.onerror=null;this.style.display='none';}">
          <div class="card-overlay">
            <a href="destination-details.html?id=${escapeHtml(String(dest.id))}" class="btn btn-primary btn-lg btn-cta">View Details</a>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">${escapeHtml(dest.name || '')}</h5>
          <p class="card-text text-muted">${dest.description ? escapeHtml(dest.description).substring(0, 100) + (dest.description.length > 100 ? '...' : '') : 'Philippines'}</p>
        </div>
      </div>
    </div>
  `;
}

/**
 * Render booking card
 * @param {object} booking - Booking data
 * @param {number} index - Card index
 * @returns {string} HTML string
 */
function renderBookingCard(booking, index = 0) {
  const statusClass = booking.status === 'confirmed'
    ? 'success'
    : booking.status === 'cancelled'
      ? 'danger'
      : 'secondary';
  
  const type = booking.type || booking.item_type || 'unknown';
  const itemDetails = booking.item_details || {};
  const itemName = escapeHtml(itemDetails.name || itemDetails.title || itemDetails.airline || itemDetails.service || `Item #${booking.item_id}`);
  
  let detailsHtml = '';
  if (type === 'hotel') {
    const cityName = escapeHtml(itemDetails.city_name || '');
    const provinceName = escapeHtml(itemDetails.province_name || '');
    detailsHtml = `
      <p class="card-text"><strong>Hotel:</strong> ${itemName}</p>
      ${booking.check_in ? `<p class="card-text"><strong>Check-in:</strong> ${escapeHtml(formatDate(booking.check_in))}</p>` : ''}
      ${booking.check_out ? `<p class="card-text"><strong>Check-out:</strong> ${escapeHtml(formatDate(booking.check_out))}</p>` : ''}
      ${booking.guests ? `<p class="card-text"><strong>Guests:</strong> ${escapeHtml(String(booking.guests))}</p>` : ''}
      ${cityName ? `<p class="card-text text-muted">${cityName}${provinceName ? ', ' + provinceName : ''}</p>` : ''}
    `;
  } else if (type === 'flight') {
    const origin = escapeHtml(itemDetails.origin || '');
    const destination = escapeHtml(itemDetails.destination || '');
    const bookingClass = escapeHtml(booking.class || '');
    const isRoundtrip = itemDetails.is_roundtrip || booking.related_booking;
    const returnOrigin = itemDetails.return_origin || (booking.related_booking?.item_details?.origin);
    const returnDestination = itemDetails.return_destination || (booking.related_booking?.item_details?.destination);
    const departureDate = itemDetails.departure_date || booking.related_booking?.departure_date;
    const returnDate = itemDetails.return_date || (booking.related_booking?.item_details?.departure_date);
    
    detailsHtml = `
      <p class="card-text"><strong>Flight:</strong> ${itemName}</p>
      ${bookingClass ? `<p class="card-text"><strong>Class:</strong> ${bookingClass.charAt(0).toUpperCase() + bookingClass.slice(1)}</p>` : ''}
      ${booking.passenger_count ? `<p class="card-text"><strong>Passengers:</strong> ${escapeHtml(String(booking.passenger_count))}</p>` : ''}
      ${isRoundtrip ? `<p class="card-text"><strong>Type:</strong> Round Trip</p>` : ''}
      ${origin && destination ? `<p class="card-text"><strong>Departure:</strong> <span class="text-muted">${origin} → ${destination}</span></p>` : ''}
      ${departureDate ? `<p class="card-text"><strong>Departure Date:</strong> <span class="text-muted">${escapeHtml(formatDate(departureDate))}</span></p>` : ''}
      ${isRoundtrip && returnOrigin && returnDestination ? `<p class="card-text"><strong>Return:</strong> <span class="text-muted">${escapeHtml(returnOrigin)} → ${escapeHtml(returnDestination)}</span></p>` : ''}
      ${isRoundtrip && returnDate ? `<p class="card-text"><strong>Return Date:</strong> <span class="text-muted">${escapeHtml(formatDate(returnDate))}</span></p>` : ''}
    `;
  } else if (type === 'activity') {
    const cityName = escapeHtml(itemDetails.city_name || '');
    const provinceName = escapeHtml(itemDetails.province_name || '');
    detailsHtml = `
      <p class="card-text"><strong>Activity:</strong> ${itemName}</p>
      ${booking.participant_count ? `<p class="card-text"><strong>Participants:</strong> ${escapeHtml(String(booking.participant_count))}</p>` : ''}
      ${cityName ? `<p class="card-text text-muted">${cityName}${provinceName ? ', ' + provinceName : ''}</p>` : ''}
    `;
  } else if (type === 'transfer') {
    const origin = escapeHtml(itemDetails.origin || '');
    const destination = escapeHtml(itemDetails.destination || '');
    detailsHtml = `
      <p class="card-text"><strong>Transfer:</strong> ${itemName}</p>
      ${booking.passenger_count ? `<p class="card-text"><strong>Passengers:</strong> ${escapeHtml(String(booking.passenger_count))}</p>` : ''}
      ${origin && destination ? `<p class="card-text text-muted">${origin} → ${destination}</p>` : ''}
    `;
  } else {
    detailsHtml = `<p class="card-text"><strong>Item:</strong> ${itemName}</p>`;
  }
  
  const statusText = escapeHtml(booking.status || '');
  const typeCapitalized = escapeHtml(type.charAt(0).toUpperCase() + type.slice(1));
  
  return `
    <div class="card mb-3 reveal stagger-${(index % 5) + 1}">
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h5 class="card-title">${typeCapitalized} Booking #${escapeHtml(String(booking.id))}</h5>
            ${detailsHtml}
            <p class="card-text"><strong>Status:</strong> <span class="badge bg-${statusClass}">${statusText.charAt(0).toUpperCase() + statusText.slice(1)}</span></p>
            <p class="card-text text-muted"><small>Booked on: ${escapeHtml(formatDate(booking.booked_at))}</small></p>
            <p class="price mt-2">Total: ${formatPrice(booking.total_price)}</p>
          </div>
          <div class="col-md-4 text-end">
            ${booking.status === 'confirmed' ? `<button class="btn btn-danger" onclick="cancelBooking(${escapeHtml(String(booking.id))})">Cancel Booking</button>` : ''}
            <a href="${escapeHtml(type === 'hotel' ? 'hotel' : type === 'flight' ? 'flight' : type === 'activity' ? 'activity' : 'transfer')}-details.html?id=${escapeHtml(String(booking.item_id))}" class="btn btn-outline-primary mt-2 d-block">View Details</a>
          </div>
        </div>
      </div>
    </div>
  `;
}

/**
 * Render skeleton card for loading states
 * @param {string} columnsClass - Column class
 * @param {boolean} withImage - Include image skeleton
 * @param {number} lines - Number of text lines
 * @returns {string} HTML string
 */
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

/**
 * Render skeleton list
 * @param {number} count - Number of skeleton cards
 * @param {string} columnsClass - Column class
 * @param {boolean} withImage - Include image skeleton
 * @param {number} lines - Number of text lines
 * @returns {string} HTML string
 */
function renderSkeletonList(count = 4, columnsClass = 'col-md-4', withImage = true, lines = 3) {
  return Array.from({ length: count }).map(() => renderSkeletonCard(columnsClass, withImage, lines)).join('');
}

/**
 * Render section header
 * @param {object} options - Header options
 * @param {string} options.title - Header title
 * @param {string} options.subtitle - Header subtitle
 * @param {string} options.actionsHtml - Actions HTML
 * @returns {string} HTML string
 */
function renderSectionHeader({ title = '', subtitle = '', actionsHtml = '' } = {}) {
  return `
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h3 class="mb-1">${escapeHtml(title)}</h3>
        ${subtitle ? `<p class="text-muted mb-0">${escapeHtml(subtitle)}</p>` : ''}
      </div>
      ${actionsHtml ? `<div class="ms-auto">${actionsHtml}</div>` : ''}
    </div>
  `;
}

/**
 * Render form control
 * @param {object} options - Form control options
 * @returns {string} HTML string
 */
function renderFormControl({ id, label = '', type = 'text', placeholder = '', value = '', min, max, step, options = [] } = {}) {
  if (type === 'select') {
    const opts = options.map(opt => `<option value="${escapeHtml(String(opt.value ?? ''))}">${escapeHtml(opt.label ?? '')}</option>`).join('');
    return `
      <div class="mb-3">
        ${label ? `<label class="form-label" for="${escapeHtml(id)}">${escapeHtml(label)}</label>` : ''}
        <select class="form-control" id="${escapeHtml(id)}">
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
      ${label ? `<label class="form-label" for="${escapeHtml(id)}">${escapeHtml(label)}</label>` : ''}
      <input type="${escapeHtml(type)}" class="form-control" id="${escapeHtml(id)}" placeholder="${escapeHtml(placeholder)}" value="${escapeHtml(String(value))}"${minAttr}${maxAttr}${stepAttr}>
    </div>
  `;
}

/**
 * Render button
 * @param {object} options - Button options
 * @returns {string} HTML string
 */
function renderButton({ text, href = '#', variant = 'primary', size = '', block = false, extraClasses = '' } = {}) {
  const sizeClass = size ? ` btn-${size}` : '';
  const blockClass = block ? ' w-100' : '';
  const classes = `btn btn-${variant}${sizeClass}${blockClass} ${extraClasses}`.trim();
  return `<a href="${escapeHtml(href)}" class="${classes}">${escapeHtml(text)}</a>`;
}
