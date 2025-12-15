// Image Component - Standardized image handling
// Uses normalizeImageUrl from urlHelpers.js (loaded first)

/**
 * Render image with placeholder fallback
 * @param {object} options Image options
 * @param {string} options.src Image source URL
 * @param {string} options.alt Alt text
 * @param {string} options.className Additional CSS classes
 * @param {boolean} options.lazy Enable lazy loading
 * @returns {string} HTML string
 */
function renderImage({ src, alt = '', className = '', lazy = true }) {
  const normalizedSrc = normalizeImageUrl(src);
  const placeholderPath = normalizeImageUrl('uploads/placeholder.svg');
  const lazyAttr = lazy ? 'loading="lazy"' : '';
  const classAttr = className ? ` class="${className}"` : '';
  const safePlaceholder = escapeHtml(placeholderPath);
  
  return `<img src="${normalizedSrc}" alt="${escapeHtml(alt)}"${classAttr} ${lazyAttr} onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${safePlaceholder}');this.src='${safePlaceholder}';}else{this.onerror=null;this.style.display='none';}">`;
}

