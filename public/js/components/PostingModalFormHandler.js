/**
 * PostingModal Form Handler
 * Handles form initialization, dropdown loading, and cascading logic
 */

/**
 * Initialize form for PostingModal
 * @param {string} modalId - Modal ID
 * @param {string} type - Entity type
 * @param {object} item - Existing item data
 * @param {object} fileUploadInstance - FileUpload instance reference
 */
async function initPostingForm(modalId, type, item, fileUploadInstance) {
  const form = document.getElementById(`${modalId}-form`);
  if (!form) return;
  
  // Load dropdown data
  await loadPostingDropdownData(modalId, type, item);
  
  // Initialize cascading dropdowns
  initPostingCascadingDropdowns(modalId, item);
  
  // Initialize file upload for images (only for hotels and activities)
  if (type === 'hotel' || type === 'activity') {
    const imagesField = document.getElementById(`${modalId}-images-container`);
    if (imagesField) {
      const initialUrls = item && item.images ? (Array.isArray(item.images) ? item.images : [item.images]) : [];
      const category = type === 'hotel' ? 'hotels' : 'activities';
      
      const fileUpload = new FileUpload(`${modalId}-images-container`, {
        maxFiles: 10,
        category: category,
        initialUrls: initialUrls,
        onChange: (urls) => {
          if (fileUploadInstance) {
            fileUploadInstance.imageUrls = urls;
          }
        }
      });
      fileUpload.render();
      if (fileUploadInstance) {
        fileUploadInstance.fileUploadInstance = fileUpload;
      }
    }
  }
}

/**
 * Load dropdown data for form fields
 * @param {string} modalId - Modal ID
 * @param {string} type - Entity type
 * @param {object} item - Existing item data
 */
async function loadPostingDropdownData(modalId, type, item) {
  const fields = getPostingFormFields(type);
  
  for (const field of fields) {
    if (field.type === 'select' && typeof field.options === 'string') {
      const select = document.getElementById(`${modalId}-${field.name}`);
      if (!select) continue;
      
      try {
        let data = [];
        if (field.options === 'provinces') {
          const result = await apiCall('/provinces');
          data = result.results || [];
        } else if (field.options === 'cities') {
          if (!field.dependsOn) {
            const result = await apiCall('/cities');
            data = result.results || [];
          }
        } else if (field.options === 'destinations') {
          const result = await apiCall('/destinations');
          data = result.results || [];
        } else if (field.options === 'airports') {
          if (!field.dependsOn) {
            const result = await apiCall('/airports');
            data = result.results || [];
          }
        }
        
        select.innerHTML = '<option value="">Select...</option>';
        const itemValue = item ? item[field.name] : null;
        data.forEach(dataItem => {
          const option = document.createElement('option');
          option.value = dataItem.id;
          option.textContent = dataItem.name || dataItem.title || `${dataItem.code} - ${dataItem.name}`;
          if (itemValue != null && itemValue == dataItem.id) {
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

/**
 * Initialize cascading dropdowns
 * @param {string} modalId - Modal ID
 * @param {object} item - Existing item data
 */
function initPostingCascadingDropdowns(modalId, item) {
  const form = document.getElementById(`${modalId}-form`);
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
    
    if (provinceSelect.value) {
      loadPostingCitiesForProvince(provinceSelect.value, citySelect, item);
    }
    
    provinceSelect.addEventListener('change', async () => {
      citySelect.innerHTML = '<option value="">Select City</option>';
      citySelect.disabled = !provinceSelect.value;
      if (provinceSelect.value) {
        await loadPostingCitiesForProvince(provinceSelect.value, citySelect, item);
      }
      
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
    
    if (citySelect.value) {
      loadPostingAirportsForCity(citySelect.value, airportSelect, item);
    }
    
    citySelect.addEventListener('change', async () => {
      airportSelect.innerHTML = '<option value="">Select Airport</option>';
      airportSelect.disabled = !citySelect.value;
      if (citySelect.value) {
        await loadPostingAirportsForCity(citySelect.value, airportSelect, item);
      }
    });
  });
}

/**
 * Load cities for a province
 * @param {string} provinceId - Province ID
 * @param {HTMLElement} citySelect - City select element
 * @param {object} item - Existing item data
 */
async function loadPostingCitiesForProvince(provinceId, citySelect, item) {
  try {
    const cityFieldName = citySelect.name;
    const cities = await apiCall(`/cities?province_id=${provinceId}`);
    citySelect.disabled = false;
    citySelect.innerHTML = '<option value="">Select City</option>';
    if (cities.results && cities.results.length > 0) {
      cities.results.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        const shouldSelect = item && (item[cityFieldName] == city.id || item.city_id == city.id || item.origin_city_id == city.id || item.destination_city_id == city.id);
        if (shouldSelect) {
          option.selected = true;
        }
        citySelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading cities:', error);
  }
}

/**
 * Load airports for a city
 * @param {string} cityId - City ID
 * @param {HTMLElement} airportSelect - Airport select element
 * @param {object} item - Existing item data
 */
async function loadPostingAirportsForCity(cityId, airportSelect, item) {
  try {
    const airports = await apiCall(`/airports?city_id=${cityId}`);
    airportSelect.disabled = false;
    airportSelect.innerHTML = '<option value="">Select Airport</option>';
    if (airports.results && airports.results.length > 0) {
      airports.results.forEach(airport => {
        const option = document.createElement('option');
        option.value = airport.id;
        option.textContent = `${airport.code} - ${airport.name}`;
        if (item && item.origin_airport_id == airport.id) {
          option.selected = true;
        } else if (item && item.destination_airport_id == airport.id) {
          option.selected = true;
        }
        airportSelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading airports:', error);
  }
}

