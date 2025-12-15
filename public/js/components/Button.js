/**
 * Button Component - Standardized button rendering
 */

/**
 * Render button
 * @param {object} options - Button options
 * @param {string} options.text - Button text
 * @param {string} options.href - Button href (for anchor buttons)
 * @param {string} options.variant - Button variant ('primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline-primary', etc.)
 * @param {string} options.size - Button size ('sm', 'md', 'lg')
 * @param {boolean} options.block - Full width button
 * @param {string} options.type - Button type ('button', 'submit', 'reset') - if provided, renders as <button> instead of <a>
 * @param {string} options.onclick - onclick handler (for button elements)
 * @param {string} options.extraClasses - Additional CSS classes
 * @param {string} options.id - Button ID
 * @returns {string} HTML string
 */
function renderButton({ 
  text = '', 
  href = '#', 
  variant = 'primary', 
  size = '', 
  block = false, 
  type = '',
  onclick = '',
  extraClasses = '',
  id = ''
} = {}) {
  const sizeClass = size ? ` btn-${size}` : '';
  const blockClass = block ? ' w-100' : '';
  const classes = `btn btn-${variant}${sizeClass}${blockClass} ${extraClasses}`.trim();
  const idAttr = id ? ` id="${escapeHtml(id)}"` : '';
  const onclickAttr = onclick ? ` onclick="${escapeHtml(onclick)}"` : '';
  
  if (type) {
    // Render as <button> element
    return `<button type="${escapeHtml(type)}" class="${classes}"${idAttr}${onclickAttr}>${escapeHtml(text)}</button>`;
  } else {
    // Render as <a> element
    return `<a href="${escapeHtml(href)}" class="${classes}"${idAttr}${onclickAttr}>${escapeHtml(text)}</a>`;
  }
}

