/**
 * Filter Component - Standardized filter UI for listing pages
 */
class Filter {
  constructor(containerId, options = {}) {
    this.containerId = containerId;
    this.options = {
      showProvince: options.showProvince !== false,
      showCity: options.showCity !== false,
      showKeyword: options.showKeyword !== false,
      showPriceRange: options.showPriceRange !== false,
      showClearButton: options.showClearButton !== false,
      keywordPlaceholder: options.keywordPlaceholder || 'Search...',
      buttonLabel: options.buttonLabel || 'Filter',
      onFilter: options.onFilter || (() => {}),
      onClear: options.onClear || (() => {})
    };
  }
  
  render() {
    const container = document.getElementById(this.containerId);
    if (!container) return;
    
    let filterHtml = '<div class="filter-section"><div class="filter-row">';
    
    if (this.options.showProvince) {
      filterHtml += `
        <div class="filter-field">
          <label for="${this.containerId}-province" class="form-label">Province</label>
          <select class="form-control" id="${this.containerId}-province">
            <option value="">All Provinces</option>
          </select>
        </div>
      `;
    }
    
    if (this.options.showCity) {
      filterHtml += `
        <div class="filter-field">
          <label for="${this.containerId}-city" class="form-label">City</label>
          <select class="form-control" id="${this.containerId}-city">
            <option value="">All Cities</option>
          </select>
        </div>
      `;
    }
    
    if (this.options.showKeyword) {
      filterHtml += `
        <div class="filter-field filter-keyword-field">
          <label for="${this.containerId}-keyword" class="form-label">Keyword Search</label>
          <input type="text" class="form-control" id="${this.containerId}-keyword" placeholder="${escapeHtml(this.options.keywordPlaceholder)}">
        </div>
      `;
    }
    
    if (this.options.showPriceRange) {
      filterHtml += `
        <div class="filter-field">
          <label for="${this.containerId}-minPrice" class="form-label">Price Range</label>
          <input type="number" class="form-control" id="${this.containerId}-minPrice" placeholder="Min" min="0" step="1" inputmode="numeric" pattern="[0-9]*">
        </div>
        <div class="filter-field">
          <label for="${this.containerId}-maxPrice" class="form-label"></label>
          <input type="number" class="form-control" id="${this.containerId}-maxPrice" placeholder="Max" min="0" step="1" inputmode="numeric" pattern="[0-9]*">
        </div>
      `;
    }
    
    filterHtml += `
      <div class="filter-buttons">
        <label class="form-label filter-label-hidden">Actions</label>
        <div class="d-flex gap-2">
          <button class="btn btn-primary btn-sm filter-button" id="${this.containerId}-filterBtn">${escapeHtml(this.options.buttonLabel)}</button>
          ${this.options.showClearButton ? `<button class="btn btn-outline-secondary btn-sm filter-button" id="${this.containerId}-clearBtn">Clear</button>` : ''}
        </div>
      </div>
    `;
    
    filterHtml += '</div></div>';
    
    container.innerHTML = filterHtml;
    
    this.attachEventListeners();
    
    if (this.options.showProvince && this.options.showCity) {
      initCascadingCityDropdown(`${this.containerId}-province`, `${this.containerId}-city`);
    }
    
    if (this.options.showProvince) {
      loadProvinces(`${this.containerId}-province`);
    }
  }
  
  attachEventListeners() {
    const filterBtn = document.getElementById(`${this.containerId}-filterBtn`);
    if (filterBtn) {
      filterBtn.addEventListener('click', () => this.handleFilter());
    }
    
    if (this.options.showClearButton) {
      const clearBtn = document.getElementById(`${this.containerId}-clearBtn`);
      if (clearBtn) {
        clearBtn.addEventListener('click', () => this.handleClear());
      }
    }
  }
  
  getValues() {
    const values = {};
    
    if (this.options.showProvince) {
      const province = document.getElementById(`${this.containerId}-province`);
      if (province && province.value) {
        values.province_id = parseInt(province.value);
      }
    }
    
    if (this.options.showCity) {
      const city = document.getElementById(`${this.containerId}-city`);
      if (city && city.value) {
        values.city_id = parseInt(city.value);
      }
    }
    
    if (this.options.showKeyword) {
      const keyword = document.getElementById(`${this.containerId}-keyword`);
      if (keyword) {
        values.q = keyword.value.trim();
      }
    }
    
    if (this.options.showPriceRange) {
      const minPrice = document.getElementById(`${this.containerId}-minPrice`);
      const maxPrice = document.getElementById(`${this.containerId}-maxPrice`);
      if (minPrice && minPrice.value) {
        values.minPrice = parseFloat(minPrice.value);
      }
      if (maxPrice && maxPrice.value) {
        values.maxPrice = parseFloat(maxPrice.value);
      }
    }
    
    return values;
  }
  
  handleFilter() {
    const values = this.getValues();
    this.options.onFilter(values);
  }
  
  handleClear() {
    if (this.options.showProvince) {
      const province = document.getElementById(`${this.containerId}-province`);
      if (province) {
        province.value = '';
        // Trigger change to reset city dropdown
        province.dispatchEvent(new Event('change'));
      }
    }
    if (this.options.showCity) {
      const city = document.getElementById(`${this.containerId}-city`);
      if (city) {
        city.value = '';
        city.disabled = true;
        city.innerHTML = '<option value="">All Cities</option>';
      }
    }
    if (this.options.showKeyword) {
      const keyword = document.getElementById(`${this.containerId}-keyword`);
      if (keyword) keyword.value = '';
    }
    if (this.options.showPriceRange) {
      const minPrice = document.getElementById(`${this.containerId}-minPrice`);
      const maxPrice = document.getElementById(`${this.containerId}-maxPrice`);
      if (minPrice) minPrice.value = '';
      if (maxPrice) maxPrice.value = '';
    }
    this.options.onClear();
  }
  
  /**
   * Set filter values programmatically (useful for URL parameter restoration)
   */
  setValues(values) {
    if (this.options.showProvince && values.province_id) {
      const province = document.getElementById(`${this.containerId}-province`);
      if (province) {
        province.value = values.province_id;
        province.dispatchEvent(new Event('change'));
      }
    }
    if (this.options.showCity && values.city_id) {
      const city = document.getElementById(`${this.containerId}-city`);
      if (city) {
        // Wait a bit for cities to load if province was set
        setTimeout(() => {
          if (city) city.value = values.city_id;
        }, 300);
      }
    }
    if (this.options.showKeyword && values.q) {
      const keyword = document.getElementById(`${this.containerId}-keyword`);
      if (keyword) keyword.value = values.q;
    }
    if (this.options.showPriceRange) {
      const minPrice = document.getElementById(`${this.containerId}-minPrice`);
      const maxPrice = document.getElementById(`${this.containerId}-maxPrice`);
      if (minPrice && values.minPrice) minPrice.value = values.minPrice;
      if (maxPrice && values.maxPrice) maxPrice.value = values.maxPrice;
    }
  }
}

async function loadProvinces(selectId) {
  try {
    const data = await apiCall('/provinces');
    const select = document.getElementById(selectId);
    if (!select || !data.results) return;
    
    data.results.forEach(province => {
      const option = document.createElement('option');
      option.value = province.id;
      option.textContent = province.name;
      select.appendChild(option);
    });
  } catch (error) {
    console.error('Error loading provinces:', error);
  }
}
