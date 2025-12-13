// PostingModal Component
// A reusable modal for creating/editing postings (hotels, flights, activities, transfers)

class PostingModal {
  constructor(type, item = null) {
    this.type = type; // 'hotel', 'flight', 'activity', 'transfer'
    this.item = item; // null for create, object for edit
    this.id = `posting-modal-${Date.now()}`;
    this.modal = null;
  }
  
  static show(type, item = null) {
    return new PostingModal(type, item).show();
  }
  
  show() {
    // Remove existing modal if any
    const existing = document.getElementById(this.id);
    if (existing) existing.remove();
    
    const isEdit = this.item !== null;
    const title = isEdit ? `Edit ${this.type.charAt(0).toUpperCase() + this.type.slice(1)}` : `Create ${this.type.charAt(0).toUpperCase() + this.type.slice(1)}`;
    
    // Create modal HTML
    const modalHtml = `
      <div class="modal fade" id="${this.id}" tabindex="-1" aria-labelledby="${this.id}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="${this.id}Label">${title}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="${this.id}-form">
                ${this.renderFormFields()}
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="${this.id}-submit">${isEdit ? 'Update' : 'Create'}</button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Append to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Get modal element
    const modalElement = document.getElementById(this.id);
    this.modal = new bootstrap.Modal(modalElement);
    
    // Initialize form
    this.initForm();
    
    // Attach submit handler
    const submitBtn = document.getElementById(`${this.id}-submit`);
    submitBtn.addEventListener('click', () => this.handleSubmit());
    
    // Cleanup on close
    modalElement.addEventListener('hidden.bs.modal', () => {
      modalElement.remove();
    });
    
    // Show modal
    this.modal.show();
    
    return this.modal;
  }
  
  renderFormFields() {
    const fields = this.getFormFields();
    return fields.map(field => this.renderField(field)).join('');
  }
  
  getFormFields() {
    const baseFields = {
      hotel: [
        { name: 'name', label: 'Hotel Name', type: 'text', required: true },
        { name: 'province_id', label: 'Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'city_id', label: 'City', type: 'select', required: true, options: 'cities', dependsOn: 'province_id' },
        { name: 'destination_id', label: 'Destination', type: 'select', required: false, options: 'destinations' },
        { name: 'price_per_night', label: 'Price per Night', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
        { name: 'images', label: 'Image URLs (one per line)', type: 'textarea', required: false, placeholder: 'https://example.com/image1.jpg\nhttps://example.com/image2.jpg' },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      flight: [
        { name: 'airline', label: 'Airline', type: 'text', required: true },
        { name: 'origin', label: 'Origin', type: 'text', required: true },
        { name: 'destination', label: 'Destination', type: 'text', required: true },
        { name: 'origin_city_id', label: 'Origin City', type: 'select', required: false, options: 'cities' },
        { name: 'destination_city_id', label: 'Destination City', type: 'select', required: false, options: 'cities' },
        { name: 'depart_date', label: 'Departure Date', type: 'date', required: true },
        { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'trip_type', label: 'Trip Type', type: 'select', required: false, options: [{ value: 'one-way', label: 'One-way' }, { value: 'round-trip', label: 'Round-trip' }] },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
        { name: 'images', label: 'Image URLs (one per line)', type: 'textarea', required: false },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      activity: [
        { name: 'title', label: 'Activity Title', type: 'text', required: true },
        { name: 'province_id', label: 'Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'city_id', label: 'City', type: 'select', required: true, options: 'cities', dependsOn: 'province_id' },
        { name: 'destination_id', label: 'Destination', type: 'select', required: false, options: 'destinations' },
        { name: 'date', label: 'Date', type: 'date', required: true },
        { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
        { name: 'images', label: 'Image URLs (one per line)', type: 'textarea', required: false },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      transfer: [
        { name: 'service', label: 'Service Name', type: 'text', required: true },
        { name: 'origin', label: 'Origin', type: 'text', required: true },
        { name: 'destination', label: 'Destination', type: 'text', required: true },
        { name: 'origin_city_id', label: 'Origin City', type: 'select', required: false, options: 'cities' },
        { name: 'destination_city_id', label: 'Destination City', type: 'select', required: false, options: 'cities' },
        { name: 'date', label: 'Date', type: 'date', required: true },
        { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
        { name: 'images', label: 'Image URLs (one per line)', type: 'textarea', required: false },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ]
    };
    
    return baseFields[this.type] || [];
  }
  
  renderField(field) {
    const fieldId = `${this.id}-${field.name}`;
    const value = this.item ? (this.item[field.name] || '') : '';
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
      case 'textarea':
        const placeholder = field.placeholder ? `placeholder="${escapeHtml(field.placeholder)}"` : '';
        inputHtml = `<textarea class="form-control" id="${fieldId}" name="${field.name}" rows="${field.rows || 3}" ${placeholder} ${requiredAttr}>${escapeHtml(value)}</textarea>`;
        break;
      case 'checkbox':
        const checked = value ? 'checked' : '';
        inputHtml = `<input type="checkbox" class="form-check-input" id="${fieldId}" name="${field.name}" ${checked}>`;
        break;
      case 'select':
        if (field.options === 'provinces' || field.options === 'cities' || field.options === 'destinations') {
          inputHtml = `<select class="form-control" id="${fieldId}" name="${field.name}" ${requiredAttr}></select>`;
        } else if (Array.isArray(field.options)) {
          const options = field.options.map(opt => 
            `<option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
          ).join('');
          inputHtml = `<select class="form-control" id="${fieldId}" name="${field.name}" ${requiredAttr}>${options}</select>`;
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
  
  async initForm() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    // Load dropdown data
    await this.loadDropdownData();
    
    // Initialize cascading dropdowns
    this.initCascadingDropdowns();
    
    // Handle images field (convert array to newline-separated string)
    if (this.item && this.item.images && Array.isArray(this.item.images)) {
      const imagesField = document.getElementById(`${this.id}-images`);
      if (imagesField) {
        imagesField.value = this.item.images.join('\n');
      }
    }
  }
  
  async loadDropdownData() {
    const fields = this.getFormFields();
    
    for (const field of fields) {
      if (field.type === 'select' && typeof field.options === 'string') {
        const select = document.getElementById(`${this.id}-${field.name}`);
        if (!select) continue;
        
        try {
          let data = [];
          if (field.options === 'provinces') {
            const result = await apiCall('/provinces');
            data = result.results || [];
          } else if (field.options === 'cities') {
            // For cities, we'll load them when province is selected (cascading)
            if (!field.dependsOn) {
              const result = await apiCall('/cities');
              data = result.results || [];
            }
          } else if (field.options === 'destinations') {
            const result = await apiCall('/destinations');
            data = result.results || [];
          }
          
          // Populate select
          select.innerHTML = '<option value="">Select...</option>';
          data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name || item.title;
            if (this.item && this.item[field.name] == item.id) {
              option.selected = true;
            }
            select.appendChild(option);
          });
        } catch (error) {
          console.error(`Error loading ${field.options}:`, error);
        }
      }
    }
  }
  
  initCascadingDropdowns() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    // Find province-city pairs
    const provinceFields = form.querySelectorAll('[data-on-change="loadCities"]');
    provinceFields.forEach(provinceField => {
      const provinceSelect = provinceField.querySelector('select');
      if (!provinceSelect) return;
      
      const cityField = form.querySelector(`[data-depends-on="${provinceSelect.name}"]`);
      if (!cityField) return;
      
      const citySelect = cityField.querySelector('select');
      if (!citySelect) return;
      
      // Load cities if province is already selected
      if (provinceSelect.value) {
        this.loadCitiesForProvince(provinceSelect.value, citySelect);
      }
      
      // Handle province change
      provinceSelect.addEventListener('change', async () => {
        citySelect.innerHTML = '<option value="">Select City</option>';
        if (provinceSelect.value) {
          await this.loadCitiesForProvince(provinceSelect.value, citySelect);
        } else {
          citySelect.disabled = true;
        }
      });
    });
  }
  
  async loadCitiesForProvince(provinceId, citySelect) {
    try {
      const cities = await apiCall(`/cities?province_id=${provinceId}`);
      citySelect.disabled = false;
      citySelect.innerHTML = '<option value="">Select City</option>';
      if (cities.results && cities.results.length > 0) {
        cities.results.forEach(city => {
          const option = document.createElement('option');
          option.value = city.id;
          option.textContent = city.name;
          if (this.item && this.item.city_id == city.id) {
            option.selected = true;
          }
          citySelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error loading cities:', error);
    }
  }
  
  async handleSubmit() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    
    const formData = new FormData(form);
    const data = {};
    
    // Collect form data
    for (const [key, value] of formData.entries()) {
      if (key === 'images') {
        // Convert newline-separated URLs to array
        data[key] = value.split('\n').map(url => url.trim()).filter(url => url);
      } else if (key === 'ad') {
        data[key] = true; // Checkbox is only included if checked
      } else if (key === 'province_id' || key === 'city_id' || key === 'destination_id' || 
                 key === 'origin_city_id' || key === 'destination_city_id') {
        data[key] = value ? parseInt(value) : null;
      } else if (key.includes('price') || key === 'discount_percent') {
        data[key] = value ? parseFloat(value) : null;
      } else {
        data[key] = value || null;
      }
    }
    
    // Handle checkbox separately (not included in FormData if unchecked)
    const adCheckbox = document.getElementById(`${this.id}-ad`);
    if (adCheckbox) {
      data.ad = adCheckbox.checked;
    }
    
    // Remove empty values
    Object.keys(data).forEach(key => {
      if (data[key] === '' || data[key] === null) {
        delete data[key];
      }
    });
    
    try {
      const submitBtn = document.getElementById(`${this.id}-submit`);
      submitBtn.disabled = true;
      submitBtn.textContent = this.item ? 'Updating...' : 'Creating...';
      
      let result;
      if (this.item) {
        // Update
        result = await apiCall(`/${this.type}s/${this.item.id}`, {
          method: 'PUT',
          body: JSON.stringify(data)
        });
      } else {
        // Create
        result = await apiCall(`/${this.type}s`, {
          method: 'POST',
          body: JSON.stringify(data)
        });
      }
      
      showSuccessToast(this.item ? 'Updated successfully!' : 'Created successfully!');
      this.modal.hide();
      
      // Reload profile
      if (typeof loadProfile === 'function') {
        loadProfile();
      }
    } catch (error) {
      console.error('Error saving:', error);
      showErrorToast(error.message || 'Failed to save. Please try again.');
      const submitBtn = document.getElementById(`${this.id}-submit`);
      submitBtn.disabled = false;
      submitBtn.textContent = this.item ? 'Update' : 'Create';
    }
  }
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Global functions for ContentListItem
function createPosting() {
  // Show type selection modal
  const typeModalId = `type-selector-${Date.now()}`;
  const typeModalHtml = `
    <div class="modal fade" id="${typeModalId}" tabindex="-1" aria-labelledby="${typeModalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="${typeModalId}Label">Create Posting</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-outline-primary" data-type="hotel">Hotel</button>
              <button type="button" class="btn btn-outline-primary" data-type="flight">Flight</button>
              <button type="button" class="btn btn-outline-primary" data-type="activity">Activity</button>
              <button type="button" class="btn btn-outline-primary" data-type="transfer">Transfer</button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  document.body.insertAdjacentHTML('beforeend', typeModalHtml);
  const modalElement = document.getElementById(typeModalId);
  const modal = new bootstrap.Modal(modalElement);
  
  // Attach click handlers
  modalElement.querySelectorAll('button[data-type]').forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.type;
      modal.hide();
      setTimeout(() => {
        PostingModal.show(type);
        modalElement.remove();
      }, 300);
    });
  });
  
  // Cleanup on close
  modalElement.addEventListener('hidden.bs.modal', () => {
    modalElement.remove();
  });
  
  modal.show();
}

function editContentItem(type, id) {
  // Load item data and show edit modal
  apiCall(`/${type}s/${id}`)
    .then(data => {
      const item = data[type] || data;
      PostingModal.show(type, item);
    })
    .catch(error => {
      console.error('Error loading item:', error);
      showErrorToast('Failed to load item data.');
    });
}

function deleteContentItem(type, id, name) {
  Modal.confirm({
    title: 'Delete Item',
    message: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
    confirmText: 'Delete',
    confirmClass: 'btn-danger',
    onConfirm: async () => {
      try {
        await apiCall(`/${type}s/${id}`, {
          method: 'DELETE'
        });
        showSuccessToast('Deleted successfully!');
        if (typeof loadProfile === 'function') {
          loadProfile();
        }
      } catch (error) {
        console.error('Error deleting:', error);
        showErrorToast(error.message || 'Failed to delete. Please try again.');
      }
    }
  });
}

