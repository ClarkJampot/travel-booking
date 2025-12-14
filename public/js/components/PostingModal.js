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
        { name: 'images', label: 'Images', type: 'fileupload', required: false },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      flight: [
        { name: 'origin_province_id', label: 'Origin Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'origin_city_id', label: 'Origin City', type: 'select', required: true, options: 'cities', dependsOn: 'origin_province_id' },
        { name: 'destination_province_id', label: 'Destination Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'destination_city_id', label: 'Destination City', type: 'select', required: true, options: 'cities', dependsOn: 'destination_province_id' },
        { name: 'base_price_economy', label: 'Economy Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'base_price_business', label: 'Business Price', type: 'number', required: false, min: 0, step: 0.01 },
        { name: 'base_price_first', label: 'First Class Price', type: 'number', required: false, min: 0, step: 0.01 },
        { name: 'aircraft_type', label: 'Aircraft Type', type: 'text', required: false },
        { name: 'departure_time', label: 'Departure Time', type: 'time', required: true },
        { name: 'trip_type', label: 'Trip Type', type: 'select', required: false, options: [{ value: 'one-way', label: 'One-way' }, { value: 'round-trip', label: 'Round-trip' }] },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      activity: [
        { name: 'title', label: 'Activity Title', type: 'text', required: true },
        { name: 'province_id', label: 'Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'city_id', label: 'City', type: 'select', required: true, options: 'cities', dependsOn: 'province_id' },
        { name: 'destination_id', label: 'Destination', type: 'select', required: false, options: 'destinations' },
        { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
        { name: 'images', label: 'Images', type: 'fileupload', required: false },
        { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
        { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
      ],
      transfer: [
        { name: 'origin_province_id', label: 'Origin Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'origin_city_id', label: 'Origin City', type: 'select', required: true, options: 'cities', dependsOn: 'origin_province_id' },
        { name: 'origin_specific', label: 'Origin Specific Location', type: 'text', required: false },
        { name: 'destination_province_id', label: 'Destination Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
        { name: 'destination_city_id', label: 'Destination City', type: 'select', required: true, options: 'cities', dependsOn: 'destination_province_id' },
        { name: 'destination_specific', label: 'Destination Specific Location', type: 'text', required: false },
        { name: 'base_price', label: 'Base Price', type: 'number', required: true, min: 0, step: 0.01 },
        { name: 'departure_time', label: 'Departure Time', type: 'time', required: true },
        { name: 'description', label: 'Description', type: 'textarea', required: false },
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
      case 'time':
        inputHtml = `<input type="time" class="form-control" id="${fieldId}" name="${field.name}" value="${value}" ${requiredAttr}>`;
        break;
      case 'textarea':
        const placeholder = field.placeholder ? `placeholder="${escapeHtml(field.placeholder)}"` : '';
        inputHtml = `<textarea class="form-control" id="${fieldId}" name="${field.name}" rows="${field.rows || 3}" ${placeholder} ${requiredAttr}>${escapeHtml(value)}</textarea>`;
        break;
      case 'fileupload':
        // File upload will be rendered separately
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
  
  async initForm() {
    const form = document.getElementById(`${this.id}-form`);
    if (!form) return;
    
    // Load dropdown data
    await this.loadDropdownData();
    
    // Initialize cascading dropdowns
    this.initCascadingDropdowns();
    
    // Initialize file upload for images (only for hotels and activities)
    if (this.type === 'hotel' || this.type === 'activity') {
      const imagesField = document.getElementById(`${this.id}-images-container`);
      if (imagesField) {
        const initialUrls = this.item && this.item.images ? (Array.isArray(this.item.images) ? this.item.images : [this.item.images]) : [];
        const category = this.type === 'hotel' ? 'hotels' : 'activities';
        
        // Create unique instance name
        const instanceName = `fileUpload_${this.id}_images`;
        window[instanceName] = new FileUpload(`${this.id}-images-container`, {
          maxFiles: 10,
          category: category,
          initialUrls: initialUrls,
          onChange: (urls) => {
            // Store URLs for form submission
            this.imageUrls = urls;
          }
        });
        window[instanceName].render();
        this.fileUploadInstance = window[instanceName];
      }
    }
    
    // Store original form data for change tracking (after form is populated)
    setTimeout(() => {
      this.storeOriginalFormData();
    }, 500);
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
          } else if (field.options === 'airports') {
            // For airports, we'll load them when city is selected (cascading)
            if (!field.dependsOn) {
              const result = await apiCall('/airports');
              data = result.results || [];
            }
          }
          
          // Populate select
          select.innerHTML = '<option value="">Select...</option>';
          data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name || item.title || `${item.code} - ${item.name}`;
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
        citySelect.disabled = !provinceSelect.value;
        if (provinceSelect.value) {
          await this.loadCitiesForProvince(provinceSelect.value, citySelect);
        }
        
        // If city has airport dependency, clear airports
        const airportField = form.querySelector(`[data-depends-on="${citySelect.name}"]`);
        if (airportField) {
          const airportSelect = airportField.querySelector('select');
          if (airportSelect) {
            airportSelect.innerHTML = '<option value="">Select Airport</option>';
            airportSelect.disabled = true;
          }
        }
      });
    });
    
    // Find city-airport pairs
    const cityFields = form.querySelectorAll('[data-on-change="loadAirports"]');
    cityFields.forEach(cityField => {
      const citySelect = cityField.querySelector('select');
      if (!citySelect) return;
      
      const airportField = form.querySelector(`[data-depends-on="${citySelect.name}"]`);
      if (!airportField) return;
      
      const airportSelect = airportField.querySelector('select');
      if (!airportSelect) return;
      
      // Load airports if city is already selected
      if (citySelect.value) {
        this.loadAirportsForCity(citySelect.value, airportSelect);
      }
      
      // Handle city change
      citySelect.addEventListener('change', async () => {
        airportSelect.innerHTML = '<option value="">Select Airport</option>';
        airportSelect.disabled = !citySelect.value;
        if (citySelect.value) {
          await this.loadAirportsForCity(citySelect.value, airportSelect);
        }
      });
    });
  }
  
  async loadAirportsForCity(cityId, airportSelect) {
    try {
      const airports = await apiCall(`/airports?city_id=${cityId}`);
      airportSelect.disabled = false;
      airportSelect.innerHTML = '<option value="">Select Airport</option>';
      if (airports.results && airports.results.length > 0) {
        airports.results.forEach(airport => {
          const option = document.createElement('option');
          option.value = airport.id;
          option.textContent = `${airport.code} - ${airport.name}`;
          if (this.item && this.item.origin_airport_id == airport.id) {
            option.selected = true;
          } else if (this.item && this.item.destination_airport_id == airport.id) {
            option.selected = true;
          }
          airportSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error loading airports:', error);
    }
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
  
  // Validate form data before submission
  validateFormData(data, type) {
    const errors = [];
    
    if (type === 'flight') {
      if (!data.origin_province_id || !data.origin_city_id) {
        errors.push('Origin province and city are required');
      }
      if (!data.destination_province_id || !data.destination_city_id) {
        errors.push('Destination province and city are required');
      }
      if (data.origin_city_id === data.destination_city_id && data.origin_province_id === data.destination_province_id) {
        errors.push('Origin and destination cannot be the same');
      }
      if (!data.base_price_economy || data.base_price_economy <= 0) {
        errors.push('Economy price is required and must be greater than 0');
      }
      if (data.base_price_economy && data.base_price_economy > 99999999.99) {
        errors.push('Economy price is too large (maximum: 99,999,999.99)');
      }
      if (data.base_price_business && data.base_price_business <= 0) {
        errors.push('Business price must be greater than 0');
      }
      if (data.base_price_business && data.base_price_business > 99999999.99) {
        errors.push('Business price is too large (maximum: 99,999,999.99)');
      }
      if (data.base_price_first && data.base_price_first <= 0) {
        errors.push('First class price must be greater than 0');
      }
      if (data.base_price_first && data.base_price_first > 99999999.99) {
        errors.push('First class price is too large (maximum: 99,999,999.99)');
      }
      if (!data.departure_time) {
        errors.push('Departure time is required');
      }
      if (data.discount_percent < 0 || data.discount_percent > 100) {
        errors.push('Discount percent must be between 0 and 100');
      }
    } else if (type === 'transfer') {
      if (!data.origin_city_id) {
        errors.push('Origin city is required');
      }
      if (!data.destination_city_id) {
        errors.push('Destination city is required');
      }
      if (data.origin_city_id === data.destination_city_id && !data.origin_specific && !data.destination_specific) {
        errors.push('Origin and destination cities cannot be the same without specific locations');
      }
      if (!data.base_price || data.base_price <= 0) {
        errors.push('Base price is required and must be greater than 0');
      }
      if (data.base_price && data.base_price > 99999999.99) {
        errors.push('Base price is too large (maximum: 99,999,999.99)');
      }
      if (!data.departure_time) {
        errors.push('Departure time is required');
      }
      if (data.discount_percent < 0 || data.discount_percent > 100) {
        errors.push('Discount percent must be between 0 and 100');
      }
    } else if (type === 'hotel') {
      if (!data.name || data.name.trim() === '') {
        errors.push('Hotel name is required');
      }
      if (!data.province_id || !data.city_id) {
        errors.push('Province and city are required');
      }
      if (!data.price_per_night || data.price_per_night <= 0) {
        errors.push('Price per night is required and must be greater than 0');
      }
      if (data.price_per_night && data.price_per_night > 99999999.99) {
        errors.push('Price per night is too large (maximum: 99,999,999.99)');
      }
      if (data.discount_percent < 0 || data.discount_percent > 100) {
        errors.push('Discount percent must be between 0 and 100');
      }
    } else if (type === 'activity') {
      if (!data.title || data.title.trim() === '') {
        errors.push('Activity title is required');
      }
      if (!data.province_id || !data.city_id) {
        errors.push('Province and city are required');
      }
      if (!data.price || data.price <= 0) {
        errors.push('Price is required and must be greater than 0');
      }
      if (data.price && data.price > 99999999.99) {
        errors.push('Price is too large (maximum: 99,999,999.99)');
      }
      if (data.discount_percent < 0 || data.discount_percent > 100) {
        errors.push('Discount percent must be between 0 and 100');
      }
    }
    
    return errors;
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

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Global functions for ContentListItem
function createPosting() {
  // Get current user and role
  const userStr = localStorage.getItem('user');
  const user = userStr ? JSON.parse(userStr) : null;
  const userRole = user?.role;
  
  // Role-based restrictions
  if (userRole === 'owner') {
    // Owners: Only hotels, no modal - direct to hotel form
    PostingModal.show('hotel');
    return;
  } else if (userRole === 'agency') {
    // Agencies: Flights, Activities, Transfers only (no hotels)
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
    return;
  }
  
  // Default: Show all types (for admin or fallback)
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
  let endpoint;
  if (type === 'flight') {
    endpoint = `/flights/routes/${id}`;
  } else if (type === 'transfer') {
    endpoint = `/transfers/routes/${id}`;
  } else {
    // Use getPluralType to ensure correct pluralization
    const pluralType = type === 'activity' ? 'activities' : `${type}s`;
    endpoint = `/${pluralType}/${id}`;
  }
  
  apiCall(endpoint)
    .then(data => {
      const item = data.route || data[type] || data;
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
        let endpoint;
        if (type === 'flight') {
          endpoint = `/flights/routes/${id}`;
        } else if (type === 'transfer') {
          endpoint = `/transfers/routes/${id}`;
        } else {
          endpoint = `/${type}s/${id}`;
        }
        await apiCall(endpoint, {
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

