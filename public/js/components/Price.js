// Price Component - Standardized price display

/**
 * Render price display with optional discount
 * @param {object} options Price display options
 * @param {number} options.price Original price
 * @param {number} options.discountedPrice Discounted price (optional)
 * @param {number} options.discountPercent Discount percentage (optional)
 * @param {string} options.suffix Price suffix (e.g., '/night', '/person')
 * @param {string} options.size Size class (e.g., 'small', 'large')
 * @returns {string} HTML string
 */
function renderPrice({ price, discountedPrice = null, discountPercent = 0, suffix = '', size = '' }) {
  if (price == null) return '';
  
  const sizeClass = size ? ` price-${size}` : '';
  
  if (discountPercent > 0 || discountedPrice !== null) {
    const finalDiscountedPrice = discountedPrice || (price * (1 - discountPercent / 100));
    return `
      <div class="price-container">
        <span class="original-price">${formatPrice(price)}</span>
        <span class="discounted-price${sizeClass}">${formatPrice(finalDiscountedPrice)}</span>
        ${suffix ? `<span class="price-small">${suffix}</span>` : ''}
      </div>
    `;
  }
  
  return `<p class="price${sizeClass}">${formatPrice(price)}${suffix ? `<span class="price-small">${suffix}</span>` : ''}</p>`;
}

