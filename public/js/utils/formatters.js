/**
 * Format price as currency (PHP)
 * @param {number|string|null} price - Price to format
 * @returns {string} Formatted price string
 */
function formatPrice(price) {
  if (price == null || price === '') return '₱0';
  const formatted = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(price);
  return formatted.replace(/\.00(?=\s|$)/, '');
}

/**
 * Format percentage (removes trailing zeros)
 * @param {number|string|null} value - Percentage value to format
 * @returns {string} Formatted percentage string
 */
function formatPercent(value) {
  if (value == null || value === '') return '0';
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return num % 1 === 0 ? num.toString() : num.toFixed(2).replace(/\.?0+$/, '');
}

/**
 * Format date string to readable format
 * @param {string|null|undefined} dateString - Date string to format
 * @returns {string} Formatted date string
 */
function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

/**
 * Escape HTML to prevent XSS attacks
 * @param {string|null|undefined} text - Text to escape
 * @returns {string} Escaped HTML-safe text
 */
function escapeHtml(text) {
  if (text == null) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

