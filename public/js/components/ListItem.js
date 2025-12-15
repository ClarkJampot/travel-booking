/**
 * ListItem Component - Standardized list item rendering for flights, transfers, etc.
 */

/**
 * Render list item for flights, transfers, or other route-based items
 * @param {object} options - List item options
 * @param {object} options.item - Item data
 * @param {string} options.type - Item type ('flight', 'transfer')
 * @param {string} options.detailUrl - Detail page URL
 * @param {string} options.originDisplay - Origin display text
 * @param {string} options.destinationDisplay - Destination display text
 * @param {number} options.basePrice - Base price
 * @param {number} options.discountedPrice - Discounted price (optional)
 * @param {number} options.discountPercent - Discount percent (optional)
 * @param {boolean} options.isPromoted - Whether item is promoted
 * @param {string} options.extraInfo - Extra info HTML (optional)
 * @returns {string} HTML string
 */
function renderListItem({
  item,
  type,
  detailUrl,
  originDisplay,
  destinationDisplay,
  basePrice,
  discountedPrice = null,
  discountPercent = 0,
  isPromoted = false,
  extraInfo = ''
} = {}) {
  if (!item || !detailUrl) return '';
  
  const routeDisplay = `${escapeHtml(originDisplay)} → ${escapeHtml(destinationDisplay)}`;
  
  const promotedBadge = isPromoted ? '<span class="promoted-badge">Promoted</span>' : '';
  const discountBadge = discountPercent > 0 
    ? `<span class="badge bg-danger">${escapeHtml(formatPercent(discountPercent))}% OFF</span>` 
    : '';
  
  const priceHtml = discountedPrice
    ? `<div>
        <span class="text-decoration-line-through text-muted small">${formatPrice(basePrice)}</span><br>
        <strong class="text-primary">${formatPrice(discountedPrice)}</strong>
      </div>`
    : `<div><strong class="text-primary">From ${formatPrice(basePrice)}</strong></div>`;
  
  return `
    <a href="${escapeHtml(detailUrl)}" class="list-group-item list-group-item-action py-3">
      <div class="d-flex w-100 justify-content-between align-items-center">
        <div class="flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span>${routeDisplay}</span>
            ${promotedBadge}
            ${discountBadge}
          </div>
          ${extraInfo ? `<div class="text-muted small">${extraInfo}</div>` : ''}
        </div>
        <div class="text-end ms-3">
          ${priceHtml}
        </div>
      </div>
    </a>
  `;
}


