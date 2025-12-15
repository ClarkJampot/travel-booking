/**
 * LoadingSpinner Component - Standardized loading spinner rendering
 */

/**
 * Render loading spinner
 * @param {object} options - Spinner options
 * @param {string} options.size - Spinner size ('sm', 'md', 'lg')
 * @param {string} options.color - Spinner color variant ('primary', 'secondary', 'success', 'danger', 'warning', 'info')
 * @param {string} options.text - Optional text to display below spinner
 * @param {string} options.containerClass - Additional classes for container
 * @returns {string} HTML string
 */
function renderLoadingSpinner({ size = 'md', color = 'primary', text = '', containerClass = '' } = {}) {
  const sizeClass = size === 'sm' ? 'spinner-border-sm' : size === 'lg' ? '' : '';
  const colorClass = `text-${color}`;
  const containerClasses = containerClass ? ` ${containerClass}` : '';
  
  const textHtml = text ? `<div class="mt-2">${escapeHtml(text)}</div>` : '';
  
  return `
    <div class="text-center${containerClasses}">
      <div class="spinner-border ${sizeClass} ${colorClass}" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      ${textHtml}
    </div>
  `;
}

/**
 * Render loading spinner in a container element
 * @param {string} elementId - Element ID to render spinner into
 * @param {object} options - Spinner options
 */
function showLoadingSpinner(elementId, options = {}) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = renderLoadingSpinner(options);
  }
}

