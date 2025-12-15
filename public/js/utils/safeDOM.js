/**
 * Safe DOM manipulation utilities to prevent XSS
 */

/**
 * Safely set text content of an element
 * @param {string|HTMLElement} element - Element ID or element
 * @param {string} text - Text content (will be escaped)
 */
function setTextContent(element, text) {
  const el = typeof element === 'string' ? document.getElementById(element) : element;
  if (el) {
    el.textContent = text;
  }
}

/**
 * Safely set HTML content with escaped user data
 * @param {string|HTMLElement} element - Element ID or element
 * @param {string} html - HTML string (user data should already be escaped)
 */
function setHTML(element, html) {
  const el = typeof element === 'string' ? document.getElementById(element) : element;
  if (el) {
    el.innerHTML = html;
  }
}

/**
 * Safely append HTML with escaped user data
 * @param {string|HTMLElement} element - Element ID or element
 * @param {string} html - HTML string (user data should already be escaped)
 */
function appendHTML(element, html) {
  const el = typeof element === 'string' ? document.getElementById(element) : element;
  if (el) {
    el.insertAdjacentHTML('beforeend', html);
  }
}

/**
 * Create element with safe text content
 * @param {string} tag - HTML tag name
 * @param {string} text - Text content
 * @param {object} attributes - Element attributes
 * @returns {HTMLElement} Created element
 */
function createElementSafe(tag, text = '', attributes = {}) {
  const el = document.createElement(tag);
  if (text) {
    el.textContent = text;
  }
  Object.entries(attributes).forEach(([key, value]) => {
    if (key === 'class') {
      el.className = value;
    } else if (key === 'style') {
      el.style.cssText = value;
    } else {
      el.setAttribute(key, escapeHtml(String(value)));
    }
  });
  return el;
}
