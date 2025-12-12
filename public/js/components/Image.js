// Image Component - Standardized image handling

/**
 * Normalize image URL
 * @param {string} url Image URL
 * @returns {string} Normalized URL
 */
function normalizeImageUrl(url) {
  if (!url) return '/uploads/placeholder.svg';
  
  // Remove leading/trailing whitespace
  url = url.trim();
  
  // If it's already a full URL, return as is
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }
  
  // If it starts with /, it's a relative path
  if (url.startsWith('/')) {
    return url;
  }
  
  // Otherwise, assume it's a relative path and add /
  return '/' + url;
}

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
  const lazyAttr = lazy ? 'loading="lazy"' : '';
  const classAttr = className ? ` class="${className}"` : '';
  
  return `<img src="${normalizedSrc}" alt="${escapeHtml(alt)}"${classAttr} ${lazyAttr} onerror="this.src='/uploads/placeholder.svg'">`;
}

