// PostingModal Component
// A reusable modal for creating/editing postings (hotels, flights, activities, transfers)

class PostingModal {
  constructor(type, item = null) {
    this.type = type; // 'hotel', 'flight', 'activity', 'transfer'
    this.item = item; // null for create, object for edit
    this.id = `posting-modal-${Date.now()}`;
    this.modal = null;
    this.hasChanges = false;
    this.originalFormData = null;
  }
  
  // Helper to get plural form of entity type
  getPluralType() {
    const pluralMap = {
      'hotel': 'hotels',
      'activity': 'activities',
      'flight': 'flights',
      'transfer': 'transfers'
    };
    return pluralMap[this.type] || `${this.type}s`;
  }
  
  // Helper to get form fields (delegates to PostingModalFormFields)
  getFormFields() {
    return getPostingFormFields(this.type);
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
    
    // Track form changes
    this.trackFormChanges();
    
    // Handle modal close with confirmation
    let isConfirmingClose = false;
    const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    closeButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        if (this.hasChanges && !isConfirmingClose) {
          e.preventDefault();
          e.stopPropagation();
          isConfirmingClose = true;
          this.confirmClose(() => {
            isConfirmingClose = false;
            this.modal.hide();
          });
        }
      });
    });
    
    // Handle backdrop click and ESC key
    modalElement.addEventListener('hide.bs.modal', (e) => {
      if (this.hasChanges && !isConfirmingClose) {
        e.preventDefault();
        isConfirmingClose = true;
        this.confirmClose(() => {
          isConfirmingClose = false;
          this.modal.hide();
        });
      }
    });
    
    // Cleanup on close
    modalElement.addEventListener('hidden.bs.modal', () => {
      modalElement.remove();
    });
    
    // Show modal
    this.modal.show();
    
    return this.modal;
  }
  
  renderFormFields() {
    const fields = getPostingFormFields(this.type);
    return fields.map(field => renderPostingField(field, this.id, this.item)).join('');
  }
  
  async initForm() {
    await initPostingForm(this.id, this.type, this.item, this);
    
    // Store original form data for change tracking (after form is populated)
    setTimeout(() => {
      this.storeOriginalFormData();
    }, 500);
  }
  
  // Validate form data before submission
  validateFormData(data, type) {
    return validatePostingFormData(data, type);
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
    
    // Collect form data - handle different types differently
    if (this.type === 'flight') {
      // Flight: use /flights/routes endpoint
      const originProvinceId = formData.get('origin_province_id');
      const originCityId = formData.get('origin_city_id');
      const destProvinceId = formData.get('destination_province_id');
      const destCityId = formData.get('destination_city_id');
      const economyPrice = formData.get('base_price_economy');
      const businessPrice = formData.get('base_price_business');
      const firstPrice = formData.get('base_price_first');
      const discount = formData.get('discount_percent');
      
      data.origin_province_id = originProvinceId ? parseInt(originProvinceId) : null;
      data.origin_city_id = originCityId ? parseInt(originCityId) : null;
      data.destination_province_id = destProvinceId ? parseInt(destProvinceId) : null;
      data.destination_city_id = destCityId ? parseInt(destCityId) : null;
      data.base_price_economy = economyPrice ? parseFloat(economyPrice) : null;
      data.base_price_business = businessPrice ? parseFloat(businessPrice) : null;
      data.base_price_first = firstPrice ? parseFloat(firstPrice) : null;
      data.aircraft_type = formData.get('aircraft_type') || null;
      data.departure_time = formData.get('departure_time') || null;
      data.trip_type = formData.get('trip_type') || 'one-way';
      data.discount_percent = discount ? parseFloat(discount) : 0;
      data.ad = document.getElementById(`${this.id}-ad`)?.checked ? 1 : 0;
      
      // Validate
      const errors = this.validateFormData(data, 'flight');
      if (errors.length > 0) {
        showErrorToast(errors.join('; '));
        return;
      }
    } else if (this.type === 'transfer') {
      // Transfer: use /transfers/routes endpoint
      const originCityId = formData.get('origin_city_id');
      const destCityId = formData.get('destination_city_id');
      const basePrice = formData.get('base_price');
      const discount = formData.get('discount_percent');
      
      data.origin_city_id = originCityId ? parseInt(originCityId) : null;
      data.destination_city_id = destCityId ? parseInt(destCityId) : null;
      data.origin_specific = formData.get('origin_specific') || null;
      data.destination_specific = formData.get('destination_specific') || null;
      data.base_price = basePrice ? parseFloat(basePrice) : null;
      data.departure_time = formData.get('departure_time') || null;
      data.discount_percent = discount ? parseFloat(discount) : 0;
      data.ad = document.getElementById(`${this.id}-ad`)?.checked ? 1 : 0;
      
      // Validate
      const errors = this.validateFormData(data, 'transfer');
      if (errors.length > 0) {
        showErrorToast(errors.join('; '));
        return;
      }
    } else {
      // Hotel/Activity: use existing logic
      for (const [key, value] of formData.entries()) {
        if (key === 'images') {
          continue;
        } else if (key === 'ad') {
          data[key] = true;
        } else if (key === 'province_id' || key === 'city_id' || key === 'destination_id' || 
                   key === 'origin_city_id' || key === 'destination_city_id') {
          data[key] = value ? parseInt(value) : null;
        } else if (key.includes('price') || key === 'discount_percent') {
          data[key] = value ? parseFloat(value) : null;
        } else {
          data[key] = value || null;
        }
      }
    }
    
    // Add images from file upload component
    if (this.fileUploadInstance) {
      data.images = this.fileUploadInstance.getUrls();
    } else {
      data.images = [];
    }
    
    // Handle checkbox separately (not included in FormData if unchecked)
    const adCheckbox = document.getElementById(`${this.id}-ad`);
    if (adCheckbox) {
      data.ad = adCheckbox.checked ? 1 : 0;
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
        if (this.type === 'flight') {
          result = await apiCall(`/flights/routes/${this.item.id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
          });
        } else if (this.type === 'transfer') {
          result = await apiCall(`/transfers/routes/${this.item.id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
          });
        } else {
          result = await apiCall(`/${this.getPluralType()}/${this.item.id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
          });
        }
      } else {
        // Create
        if (this.type === 'flight') {
          result = await apiCall('/flights/routes', {
            method: 'POST',
            body: JSON.stringify(data)
          });
          // Schedule and instances are automatically created by the API
        } else if (this.type === 'transfer') {
          result = await apiCall('/transfers/routes', {
            method: 'POST',
            body: JSON.stringify(data)
          });
          // Schedule and instances are automatically created by the API
        } else {
          result = await apiCall(`/${this.getPluralType()}`, {
            method: 'POST',
            body: JSON.stringify(data)
          });
        }
      }
      
      showSuccessToast(this.item ? 'Updated successfully!' : 'Created successfully!');
      this.hasChanges = false; // Reset change tracking
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
  
  storeOriginalFormData() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    const formData = new FormData(form);
    this.originalFormData = {};
    for (const [key, value] of formData.entries()) {
      this.originalFormData[key] = value;
    }
    
    // Also store checkbox states
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      this.originalFormData[checkbox.name] = checkbox.checked;
    });
    
    // Store file upload URLs if present
    if (this.fileUploadInstance) {
      this.originalFormData['images'] = this.fileUploadInstance.getUrls();
    }
  }
  
  trackFormChanges() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    // Track all input changes
    form.addEventListener('input', () => {
      this.checkForChanges();
    });
    
    form.addEventListener('change', () => {
      this.checkForChanges();
    });
  }
  
  checkForChanges() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form || !this.originalFormData) return;
    
    const currentFormData = new FormData(form);
    const current = {};
    for (const [key, value] of currentFormData.entries()) {
      current[key] = value;
    }
    
    // Check checkbox states
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      current[checkbox.name] = checkbox.checked;
    });
    
    // Check file upload changes
    if (this.fileUploadInstance) {
      current['images'] = this.fileUploadInstance.getUrls();
    }
    
    // Compare
    this.hasChanges = JSON.stringify(current) !== JSON.stringify(this.originalFormData);
  }
  
  confirmClose(onConfirm) {
    Modal.confirm({
      title: 'Unsaved Changes',
      message: 'You have unsaved changes. Are you sure you want to close?',
      confirmText: 'Discard Changes',
      confirmClass: 'btn-danger',
      cancelText: 'Cancel',
      onConfirm: () => {
        this.hasChanges = false;
        if (onConfirm) {
          onConfirm();
        } else {
          this.modal.hide();
        }
      }
    });
  }
}

