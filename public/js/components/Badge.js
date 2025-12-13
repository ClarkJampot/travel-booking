// Badge Component - Standardized badge rendering

/**
 * Render discount badge
 * @param {number} discountPercent Discount percentage
 * @returns {string} HTML string
 */
function renderDiscountBadge(discountPercent) {
  if (!discountPercent || discountPercent <= 0) return '';
  return `<div class="discount-badge">${formatPercent(discountPercent)}% OFF</div>`;
}

/**
 * Render promoted badge
 * @returns {string} HTML string
 */
function renderPromotedBadge() {
  return '<span class="promoted-badge">Promoted</span>';
}

/**
 * Render status badge
 * @param {string} status Status text
 * @param {string} variant Badge variant (success, danger, warning, info, primary)
 * @returns {string} HTML string
 */
function renderStatusBadge(status, variant = 'primary') {
  return `<span class="badge bg-${variant}">${escapeHtml(status)}</span>`;
}

/**
 * Check if item is promoted (handles SQL Server BIT field formats)
 * @param {any} ad Promotion flag
 * @returns {boolean} True if promoted
 */
function isPromoted(ad) {
  if (ad == null || ad === undefined) return false;
  // Handle various formats: integer 1, boolean true, string "1", or truthy value
  if (ad === 1 || ad === true || ad === '1') return true;
  if (ad === 0 || ad === false || ad === '0') return false;
  // Convert to number as fallback
  const num = Number(ad);
  return !isNaN(num) && num === 1;
}


