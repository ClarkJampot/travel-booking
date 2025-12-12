// Card Component - Unified card rendering for all card types
// Dependencies: renderImage, renderPrice, renderDiscountBadge, renderPromotedBadge, isPromoted, escapeHtml, formatDate
// These should be loaded from components/Image.js, components/Price.js, components/Badge.js, utils/formatters.js

/**
 * Render a unified card for any entity type
 * @param {object} options Card options
 * @param {string} options.type Card type ('hotel', 'flight', 'destination')
 * @param {object} options.data Entity data
 * @param {string} options.columnsClass Column class (e.g., 'col-md-4')
 * @param {string} options.detailUrl Detail page URL
 * @returns {string} HTML string
 */
function renderCard({ type, data, columnsClass = 'col-md-4', detailUrl = '' }) {
  if (!data || !type) return '';
  
  // Build detail URL if not provided
  if (!detailUrl) {
    const id = data.id;
    detailUrl = `${type}-details.html?id=${id}`;
  }
  
  // Render based on type
  switch (type) {
    case 'hotel':
      return renderHotelCardInternal(data, columnsClass, detailUrl);
    case 'flight':
      return renderFlightCardInternal(data, columnsClass, detailUrl);
    case 'destination':
      return renderDestinationCardInternal(data, columnsClass, detailUrl);
    default:
      return '';
  }
}

/**
 * Render hotel card (internal)
 */
function renderHotelCardInternal(hotel, columnsClass, detailUrl) {
  const imageHtml = renderImage({
    src: (hotel.images && hotel.images[0]) || hotel.image_url,
    alt: hotel.name || 'Hotel',
    className: 'card-img-top'
  });
  
  const priceHtml = renderPrice({
    price: hotel.price_per_night,
    discountedPrice: hotel.discounted_price,
    discountPercent: hotel.discount_percent || 0,
    suffix: ' /night'
  });
  
  const discountBadge = hotel.discount_percent > 0 
    ? renderDiscountBadge(hotel.discount_percent) 
    : '';
  
  const promotedBadge = isPromoted(hotel.ad) ? renderPromotedBadge() : '';
  
  return `
    <div class="${columnsClass}">
      <div class="card h-100${isPromoted(hotel.ad) ? ' promoted-item' : ''}">
        <a href="${detailUrl}" class="text-decoration-none text-reset">
          <div class="card-img-wrapper">
            ${imageHtml}
            ${discountBadge}
          </div>
          <div class="card-body">
            ${promotedBadge}
            <h5 class="card-title">${escapeHtml(hotel.name || 'Hotel')}</h5>
            <p class="card-text text-muted">${escapeHtml((hotel.city_name || '') + (hotel.province_name ? ', ' + hotel.province_name : ''))}</p>
            ${priceHtml}
          </div>
        </a>
      </div>
    </div>
  `;
}

/**
 * Render flight card (internal)
 */
function renderFlightCardInternal(flight, columnsClass, detailUrl) {
  const imageHtml = renderImage({
    src: (flight.images && flight.images[0]) || flight.image_url,
    alt: 'Flight',
    className: 'card-img-top'
  });
  
  const basePrice = flight.price || flight.base_price_economy || 0;
  const discountedPrice = flight.discount_percent > 0
    ? (flight.discounted_price || basePrice * (1 - flight.discount_percent / 100))
    : null;
  
  const priceHtml = renderPrice({
    price: basePrice,
    discountedPrice: discountedPrice,
    discountPercent: flight.discount_percent || 0
  });
  
  const originDisplay = (flight.origin_city_name && flight.origin_code)
    ? `${flight.origin_city_name} (${flight.origin_code})`
    : (flight.origin_city_name || flight.origin_code || '');
  
  const destinationDisplay = (flight.destination_city_name && flight.destination_code)
    ? `${flight.destination_city_name} (${flight.destination_code})`
    : (flight.destination_city_name || flight.destination_code || '');
  
  const discountBadge = flight.discount_percent > 0 
    ? renderDiscountBadge(flight.discount_percent) 
    : '';
  
  const promotedBadge = isPromoted(flight.ad) ? renderPromotedBadge() : '';
  
  return `
    <div class="${columnsClass}">
      <div class="card h-100${isPromoted(flight.ad) ? ' promoted-item' : ''}">
        <a href="${detailUrl}" class="text-decoration-none text-reset">
          <div class="card-img-wrapper">
            ${imageHtml}
            ${discountBadge}
          </div>
          <div class="card-body">
            ${promotedBadge}
            <h5 class="card-title">${escapeHtml(originDisplay)} → ${escapeHtml(destinationDisplay)}</h5>
            <p class="card-text text-muted">${flight.depart_date ? formatDate(flight.depart_date) : ''}</p>
            ${priceHtml}
          </div>
        </a>
      </div>
    </div>
  `;
}

/**
 * Render destination card (internal)
 */
function renderDestinationCardInternal(destination, columnsClass, detailUrl) {
  const imageHtml = renderImage({
    src: (destination.images && destination.images[0]) || destination.image_url,
    alt: destination.name || 'Destination',
    className: 'card-img-top'
  });
  
  return `
    <div class="${columnsClass}">
      <div class="card h-100">
        <a href="${detailUrl}" class="text-decoration-none text-reset">
          <div class="card-img-wrapper">
            ${imageHtml}
          </div>
          <div class="card-body">
            <h5 class="card-title">${escapeHtml(destination.name || 'Destination')}</h5>
            <p class="card-text text-muted">${escapeHtml(destination.description || '').substring(0, 100)}${destination.description && destination.description.length > 100 ? '...' : ''}</p>
          </div>
        </a>
      </div>
    </div>
  `;
}

