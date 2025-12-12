// EmptyState Component - Standardized empty state rendering

/**
 * Render empty state message
 * @param {object} options Empty state options
 * @param {string} options.message Main message
 * @param {string} options.ctaText Call-to-action button text
 * @param {string} options.ctaHref Call-to-action button href
 * @param {string} options.icon Icon HTML (optional)
 * @returns {string} HTML string
 */
function renderEmptyState({ message = 'No items found.', ctaText = '', ctaHref = '', icon = '' } = {}) {
  const iconHtml = icon ? `<div class="mb-3">${icon}</div>` : '';
  const ctaHtml = ctaText && ctaHref ? `<a href="${ctaHref}" class="btn btn-primary mt-3">${ctaText}</a>` : '';
  
  return `
    <div class="text-muted text-center py-5">
      ${iconHtml}
      <p class="mb-2">${escapeHtml(message)}</p>
      ${ctaHtml ? `<div>${ctaHtml}</div>` : ''}
    </div>
  `;
}

