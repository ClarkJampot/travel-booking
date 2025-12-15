/**
 * PostingModal Field Renderer
 * Handles rendering of individual form fields
 */

/**
 * Render a single form field
 * @param {object} field - Field definition
 * @param {string} modalId - Modal ID prefix
 * @param {object} item - Existing item data (for edit mode)
 * @returns {string} HTML string for the field
 */
function renderPostingField(field, modalId, item = null) {
  const fieldId = `${modalId}-${field.name}`;
  const value = item ? (item[field.name] || '') : '';
  const requiredAttr = field.required ? 'required' : '';
  const requiredStar = field.required ? ' <span class="text-danger">*</span>' : '';
  
  let inputHtml = '';
  
  switch (field.type) {
    case 'text':
      inputHtml = `<input type="text" class="form-control" id="${fieldId}" name="${field.name}" value="${escapeHtml(value)}" ${requiredAttr}>`;
      break;
    case 'number':
      const minAttr = field.min !== undefined ? `min="${field.min}"` : '';
      const maxAttr = field.max !== undefined ? `max="${field.max}"` : '';
      const stepAttr = field.step !== undefined ? `step="${field.step}"` : '';
      inputHtml = `<input type="number" class="form-control" id="${fieldId}" name="${field.name}" value="${value}" ${minAttr} ${maxAttr} ${stepAttr} ${requiredAttr}>`;
      break;
    case 'date':
      inputHtml = `<input type="date" class="form-control" id="${fieldId}" name="${field.name}" value="${value}" ${requiredAttr}>`;
      break;
    case 'time':
      inputHtml = `<input type="time" class="form-control" id="${fieldId}" name="${field.name}" value="${value}" ${requiredAttr}>`;
      break;
    case 'textarea':
      const placeholder = field.placeholder ? `placeholder="${escapeHtml(field.placeholder)}"` : '';
      inputHtml = `<textarea class="form-control" id="${fieldId}" name="${field.name}" rows="${field.rows || 3}" ${placeholder} ${requiredAttr}>${escapeHtml(value)}</textarea>`;
      break;
    case 'fileupload':
      inputHtml = `<div id="${fieldId}-container"></div>`;
      break;
    case 'checkbox':
      const checked = value ? 'checked' : '';
      inputHtml = `<input type="checkbox" class="form-check-input" id="${fieldId}" name="${field.name}" ${checked}>`;
      break;
    case 'select':
      if (field.options === 'provinces' || field.options === 'cities' || field.options === 'destinations') {
        inputHtml = `<select class="form-select" id="${fieldId}" name="${field.name}" ${requiredAttr}></select>`;
      } else if (Array.isArray(field.options)) {
        const options = field.options.map(opt => 
          `<option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');
        inputHtml = `<select class="form-select" id="${fieldId}" name="${field.name}" ${requiredAttr}>${options}</select>`;
      }
      break;
  }
  
  const dependsOnAttr = field.dependsOn ? `data-depends-on="${field.dependsOn}"` : '';
  const onChangeAttr = field.onChange ? `data-on-change="${field.onChange}"` : '';
  
  if (field.type === 'checkbox') {
    return `
      <div class="mb-3">
        <div class="form-check">
          ${inputHtml}
          <label class="form-check-label" for="${fieldId}">
            ${field.label}${requiredStar}
          </label>
        </div>
      </div>
    `;
  }
  
  return `
    <div class="mb-3" ${dependsOnAttr} ${onChangeAttr}>
      <label for="${fieldId}" class="form-label">${field.label}${requiredStar}</label>
      ${inputHtml}
    </div>
  `;
}

