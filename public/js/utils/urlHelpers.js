/**
 * URL and path helper functions
 */

/**
 * Normalize image URLs to work with subdirectory installations
 * @param {string} url - Image URL
 * @returns {string} Normalized URL
 */
function normalizeImageUrl(url) {
  if (!url) {
    const path = window.location.pathname;
    const placeholder = path.includes('/travel-booking') ? '/travel-booking/uploads/placeholder.svg' : '/uploads/placeholder.svg';
    return placeholder;
  }
  
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }
  
  if (url.startsWith('/')) {
    const path = window.location.pathname;
    if (path.includes('/travel-booking')) {
      if (!url.startsWith('/travel-booking')) {
        return '/travel-booking' + url;
      }
    }
    return url;
  }
  
  const path = window.location.pathname;
  let result = '/' + url;
  if (path.includes('/travel-booking')) {
    result = '/travel-booking' + result;
  }
  return result;
}
